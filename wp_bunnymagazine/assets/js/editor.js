(function (blocks, element, components, blockEditor, serverSideRender, data, i18n) {
	'use strict';

	var createElement    = element.createElement;
	var useState         = element.useState;
	var useSelect        = data.useSelect;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps     = blockEditor.useBlockProps;
	var ServerSideRender  = serverSideRender;
	var PanelBody        = components.PanelBody;
	var TextControl      = components.TextControl;
	var TextareaControl  = components.TextareaControl || components.TextControl;
	var ToggleControl    = components.ToggleControl || components.CheckboxControl;
	var RangeControl     = components.RangeControl;
	var SelectControl    = components.SelectControl;
	var SearchControl    = components.SearchControl;
	var CheckboxControl  = components.CheckboxControl;
	var Button           = components.Button;
	var ComboboxControl  = components.ComboboxControl;
	var URLInput         = (blockEditor && blockEditor.URLInput) ? blockEditor.URLInput : components.TextControl;
	var MediaUpload      = blockEditor.MediaUpload;
	var MediaUploadCheck = blockEditor.MediaUploadCheck;

	// ── wpbmQueryConfig (injected by Block_Registry via wp_localize_script) ──
	var queryConfig     = window.wpbmQueryConfig || {};
	var contentSources  = queryConfig.sources || [
		{ value: 'latest_posts',           label: 'Latest Posts' },
		{ value: 'latest_updated_posts',   label: 'Latest Updated Posts' },
		{ value: 'random_posts',           label: 'Random Posts' },
		{ value: 'wpam_top_posts',         label: 'Affiliate Top Posts' },
		{ value: 'wpam_top_viewed_posts',  label: 'Affiliate Top Viewed Posts' },
		{ value: 'wpam_top_scored_posts',  label: 'Affiliate Top Scored Posts' },
	];
	var filtersEnabled  = queryConfig.filtersEnabled || { category: true, tag: true, author: false };

	// ── Shared helpers ────────────────────────────────────────────────────────

	function getRecordLabel(record, entityName) {
		if (!record) { return ''; }
		return record.name || record.slug || String(record.id);
	}

	function normalizeSelection(ids, records, entityName) {
		var map = {};
		(records || []).forEach(function (record) {
			map[record.id] = getRecordLabel(record, entityName);
		});
		return (ids || []).map(function (id) {
			return { id: id, label: map[id] || String(id) };
		});
	}

	// ── MultiEntityControl ───────────────────────────────────────────────────

	function MultiEntityControl(props) {
		var selectedIds      = props.selectedIds || [];
		var selectedKey      = selectedIds.join(',');
		var queryKey         = JSON.stringify(props.query || {});
		var selectedQueryKey = JSON.stringify(props.selectedQuery || {});
		var _s               = useState('');
		var search           = _s[0];
		var setSearch        = _s[1];

		var records = useSelect(function (select) {
			var q = Object.assign({ per_page: 20, hide_empty: false }, props.query || {});
			if (search) { q.search = search; } else { delete q.search; }
			return select('core').getEntityRecords(props.entityKind, props.entityName, q) || [];
		}, [props.entityKind, props.entityName, search, queryKey]);

		var selectedRecords = useSelect(function (select) {
			if (!selectedIds.length) { return []; }
			var q = Object.assign({
				include:    selectedIds,
				per_page:   selectedIds.length,
				hide_empty: false
			}, props.selectedQuery || {});
			return select('core').getEntityRecords(props.entityKind, props.entityName, q) || [];
		}, [props.entityKind, props.entityName, selectedKey, selectedQueryKey]);

		var displayedSelected = normalizeSelection(selectedIds, selectedRecords, props.entityName);

		function toggle(id) {
			id = parseInt(id, 10);
			if (!id) { return; }
			var next  = selectedIds.slice();
			var index = next.indexOf(id);
			if (index === -1) { next.push(id); } else { next.splice(index, 1); }
			props.onChange(next);
		}

		return createElement(
			'div', { className: 'wpbm-entity-control' },
			createElement(
				'div', { className: 'wpbm-entity-control__header' },
				createElement('strong', null, props.label),
				createElement('span', { className: 'wpbm-entity-control__count' }, String(selectedIds.length))
			),
			createElement(SearchControl, {
				value: search,
				placeholder: props.placeholder,
				onChange: function (value) { setSearch(value); }
			}),
			selectedIds.length ? createElement(
				'div', { className: 'wpbm-entity-control__chips' },
				displayedSelected.map(function (item) {
					return createElement(Button, {
						key:     item.id,
						variant: 'secondary',
						isSmall: true,
						onClick: function () { toggle(item.id); }
					}, item.label + ' ×');
				})
			) : null,
			createElement(
				'div', { className: 'wpbm-entity-control__results' },
				(records || []).length
					? records.map(function (record) {
						var checked = selectedIds.indexOf(record.id) !== -1;
						return createElement(CheckboxControl, {
							key:      record.id,
							label:    getRecordLabel(record, props.entityName),
							checked:  checked,
							onChange: function () { toggle(record.id); }
						});
					})
					: createElement('p', { className: 'wpbm-entity-control__empty' }, props.emptyText)
			)
		);
	}

	// ── SingleEntityControl ──────────────────────────────────────────────────

	function SingleEntityControl(props) {
		var _s2           = useState('');
		var search        = _s2[0];
		var setSearch     = _s2[1];
		var selectedValue = parseInt(props.value, 10) || 0;
		var queryKey      = JSON.stringify(props.query || {});

		var records = useSelect(function (select) {
			var q = Object.assign({ per_page: 20, hide_empty: false }, props.query || {});
			if (search) { q.search = search; } else { delete q.search; }
			return select('core').getEntityRecords(props.entityKind, props.entityName, q) || [];
		}, [props.entityKind, props.entityName, search, queryKey]);

		var selectedRecord = useSelect(function (select) {
			if (!selectedValue) { return []; }
			return select('core').getEntityRecords(props.entityKind, props.entityName, {
				include: [selectedValue], per_page: 1, hide_empty: false
			}) || [];
		}, [props.entityKind, props.entityName, selectedValue]);

		var options = [{ label: props.placeholder, value: 0 }].concat(
			(selectedRecord || []).concat(records || []).reduce(function (carry, record) {
				var exists = carry.some(function (item) { return String(item.value) === String(record.id); });
				if (!exists) { carry.push({ label: getRecordLabel(record, props.entityName), value: String(record.id) }); }
				return carry;
			}, [])
		);

		return createElement(ComboboxControl, {
			label:   props.label,
			value:   String(props.value || 0),
			options: options,
			onChange: function (value) { props.onChange(parseInt(value, 10) || 0); },
			onFilterValueChange: function (value) { setSearch(value); },
			placeholder: props.placeholder
		});
	}

	// ── toggleableEntityFilter ───────────────────────────────────────────────

	function toggleableEntityFilter(props, config) {
		var attributes    = props.attributes;
		var setAttributes = props.setAttributes;

		var includeIds  = attributes[config.includeKey] || [];
		var excludeIds  = attributes[config.excludeKey] || [];
		var excludeMode = excludeIds.length > 0;
		var selectedIds = excludeMode ? excludeIds : includeIds;

		function applySelection(nextIds) {
			var update = {};
			update[excludeMode ? config.excludeKey : config.includeKey] = nextIds;
			update[excludeMode ? config.includeKey : config.excludeKey] = [];
			setAttributes(update);
		}

		function toggleExclude(checked) {
			var update = {};
			update[config.includeKey] = checked ? [] : selectedIds;
			update[config.excludeKey] = checked ? selectedIds : [];
			setAttributes(update);
		}

		return createElement(
			'div', { className: 'wpbm-toggleable-entity-filter' },
			createElement(MultiEntityControl, {
				label:         config.label,
				placeholder:   config.placeholder,
				entityKind:    config.entityKind,
				entityName:    config.entityName,
				selectedIds:   selectedIds,
				onChange:      applySelection,
				query:         config.query,
				selectedQuery: config.selectedQuery,
				emptyText:     config.emptyText
			}),
			createElement(CheckboxControl, {
				label:    i18n.__('Exclude selected', 'wp_bunnymagazine'),
				checked:  excludeMode,
				onChange: toggleExclude
			})
		);
	}

	// ── commonControls (for post-query layouts) ──────────────────────────────

	function commonControls(props, controls) {
		var attributes    = props.attributes;
		var setAttributes = props.setAttributes;

		var sourceOptions = contentSources.map(function (src) {
			return { label: src.label, value: src.value };
		});

		return createElement(
			InspectorControls, null,

			createElement(
				PanelBody,
				{ title: i18n.__('Content', 'wp_bunnymagazine'), initialOpen: true },

				createElement(SelectControl, {
					label:    i18n.__('Content source', 'wp_bunnymagazine'),
					value:    attributes.contentSource || 'latest_posts',
					options:  sourceOptions,
					onChange: function (value) { setAttributes({ contentSource: value }); }
				}),

				(attributes.contentSource === 'wpam_top_posts' || attributes.contentSource === 'wpam_top_viewed_posts' || attributes.contentSource === 'wpam_top_scored_posts') ? createElement(SelectControl, {
					label:    i18n.__('Period', 'wp_bunnymagazine'),
					value:    attributes.period || 'week',
					options:  [
						{ label: i18n.__('Today', 'wp_bunnymagazine'),     value: 'day' },
						{ label: i18n.__('This Week', 'wp_bunnymagazine'), value: 'week' },
						{ label: i18n.__('This Month', 'wp_bunnymagazine'), value: 'month' },
						{ label: i18n.__('All Time', 'wp_bunnymagazine'),  value: 'total' }
					],
					onChange: function (value) { setAttributes({ period: value }); }
				}) : null,

				controls.limitControl ? createElement(RangeControl, {
					label:    i18n.__('Post limit', 'wp_bunnymagazine'),
					value:    attributes.limit,
					min:      controls.limitMin,
					max:      controls.limitMax,
					onChange: function (value) {
						var next = parseInt(value, 10) || controls.limitMin;
						next = Math.max(controls.limitMin, Math.min(controls.limitMax, next));
						setAttributes({ limit: next });
					}
				}) : null,

				controls.categorySelector ? createElement(SingleEntityControl, {
					label:       i18n.__('Category', 'wp_bunnymagazine'),
					placeholder: i18n.__('Search categories', 'wp_bunnymagazine'),
					entityKind:  'taxonomy',
					entityName:  'category',
					value:       attributes.categoryId || 0,
					onChange:    function (value) { setAttributes({ categoryId: value }); },
					query:       { hide_empty: false },
					emptyText:   i18n.__('No categories found.', 'wp_bunnymagazine')
				}) : null
			),

			(filtersEnabled.category || filtersEnabled.tag || filtersEnabled.author)
				? createElement(
					PanelBody,
					{ title: i18n.__('Filters', 'wp_bunnymagazine'), initialOpen: false },

					filtersEnabled.category ? toggleableEntityFilter(props, {
						label:       i18n.__('Categories', 'wp_bunnymagazine'),
						placeholder: i18n.__('Search categories', 'wp_bunnymagazine'),
						entityKind:  'taxonomy',
						entityName:  'category',
						includeKey:  'categoriesInclude',
						excludeKey:  'categoriesExclude',
						query:       { hide_empty: false },
						emptyText:   i18n.__('No matching categories found.', 'wp_bunnymagazine')
					}) : null,

					filtersEnabled.tag ? toggleableEntityFilter(props, {
						label:       i18n.__('Tags', 'wp_bunnymagazine'),
						placeholder: i18n.__('Search tags', 'wp_bunnymagazine'),
						entityKind:  'taxonomy',
						entityName:  'post_tag',
						includeKey:  'tagsInclude',
						excludeKey:  'tagsExclude',
						query:       { hide_empty: false },
						emptyText:   i18n.__('No matching tags found.', 'wp_bunnymagazine')
					}) : null,

					filtersEnabled.author ? toggleableEntityFilter(props, {
						label:         i18n.__('Authors', 'wp_bunnymagazine'),
						placeholder:   i18n.__('Search authors', 'wp_bunnymagazine'),
						entityKind:    'root',
						entityName:    'user',
						includeKey:    'authorsInclude',
						excludeKey:    'authorsExclude',
						query:         { who: 'authors' },
						selectedQuery: { who: 'authors' },
						emptyText:     i18n.__('No matching authors found.', 'wp_bunnymagazine')
					}) : null
				)
				: null,

			createElement(
				PanelBody,
				{ title: i18n.__('Layout', 'wp_bunnymagazine'), initialOpen: false },
				controls.sectionTitle ? createElement(TextControl, {
					label:    i18n.__('Section title', 'wp_bunnymagazine'),
					value:    attributes.sectionTitle || '',
					onChange: function (value) { setAttributes({ sectionTitle: value }); }
				}) : null,
				controls.ctaLabel ? createElement(TextControl, {
					label:    i18n.__('CTA label', 'wp_bunnymagazine'),
					value:    attributes.ctaLabel || '',
					onChange: function (value) { setAttributes({ ctaLabel: value }); }
				}) : null
			)
		);
	}

	// ── socialProfileControls (for Profile Card static component) ───────────

	function socialProfileControls(props) {
		var attributes    = props.attributes;
		var setAttributes = props.setAttributes;

		var defaultButtonLabels = {
			instagram: 'Seguir en Instagram',
			facebook:  'Seguir en Facebook',
			youtube:   'Ver canal de YouTube',
			tiktok:    'Seguir en TikTok',
			x:         'Seguir en X',
			discord:   'Unirse a Discord',
			patreon:   'Apoyar en Patreon',
			kofi:      'Invitar un Ko-fi',
			website:   'Visitar sitio web',
			generic:   'Ver perfil'
		};

		function handleProfileTypeChange(newType) {
			var update = { profileType: newType };
			var currentLabel = attributes.buttonText || '';
			var isDefault = !currentLabel || Object.values(defaultButtonLabels).indexOf(currentLabel) !== -1;
			if (isDefault && defaultButtonLabels[newType]) {
				update.buttonText = defaultButtonLabels[newType];
			}
			setAttributes(update);
		}

		return createElement(
			InspectorControls, null,

			// Panel: Perfil
			createElement(
				PanelBody,
				{ title: i18n.__('Configuración del Perfil', 'wp_bunnymagazine'), initialOpen: true },

				createElement(SelectControl, {
					label:    i18n.__('Icono / Tipo de Perfil', 'wp_bunnymagazine'),
					value:    attributes.profileType || 'instagram',
					options:  [
						{ label: 'Instagram', value: 'instagram' },
						{ label: 'Facebook',  value: 'facebook' },
						{ label: 'YouTube',   value: 'youtube' },
						{ label: 'TikTok',    value: 'tiktok' },
						{ label: 'X',         value: 'x' },
						{ label: 'Discord',   value: 'discord' },
						{ label: 'Patreon',   value: 'patreon' },
						{ label: 'Ko-fi',     value: 'kofi' },
						{ label: 'Sitio Web', value: 'website' },
						{ label: 'Genérico',  value: 'generic' }
					],
					onChange: handleProfileTypeChange
				}),

				createElement(TextControl, {
					label:    i18n.__('Nombre', 'wp_bunnymagazine'),
					value:    attributes.name || '',
					onChange: function (val) { setAttributes({ name: val }); }
				}),

				createElement(ToggleControl, {
					label:    i18n.__('Mostrar nombre', 'wp_bunnymagazine'),
					checked:  attributes.showName !== false,
					onChange: function (val) { setAttributes({ showName: val }); }
				}),

				createElement(TextControl, {
					label:    i18n.__('Usuario / ID', 'wp_bunnymagazine'),
					value:    attributes.username || '',
					onChange: function (val) { setAttributes({ username: val }); }
				}),

				createElement(ToggleControl, {
					label:    i18n.__('Mostrar usuario', 'wp_bunnymagazine'),
					checked:  attributes.showUsername !== false,
					onChange: function (val) { setAttributes({ showUsername: val }); }
				}),

				createElement(TextareaControl, {
					label:    i18n.__('Descripción', 'wp_bunnymagazine'),
					value:    attributes.description || '',
					onChange: function (val) { setAttributes({ description: val }); }
				}),

				createElement(ToggleControl, {
					label:    i18n.__('Mostrar descripción', 'wp_bunnymagazine'),
					checked:  attributes.showDescription !== false,
					onChange: function (val) { setAttributes({ showDescription: val }); }
				})
			),

			// Panel: Estilo y Alineación
			createElement(
				PanelBody,
				{ title: i18n.__('Estilo y Alineación', 'wp_bunnymagazine'), initialOpen: false },

				createElement(SelectControl, {
					label:    i18n.__('Modo de Estilo', 'wp_bunnymagazine'),
					value:    attributes.styleVariant || 'brand',
					options:  [
						{ label: i18n.__('Colores Oficiales (Brand)', 'wp_bunnymagazine'), value: 'brand' },
						{ label: i18n.__('Estilo del Tema (Theme)', 'wp_bunnymagazine'),   value: 'theme' }
					],
					onChange: function (val) { setAttributes({ styleVariant: val }); }
				}),

				createElement(SelectControl, {
					label:    i18n.__('Alineación', 'wp_bunnymagazine'),
					value:    attributes.alignment || 'left',
					options:  [
						{ label: i18n.__('Izquierda', 'wp_bunnymagazine'), value: 'left' },
						{ label: i18n.__('Centro', 'wp_bunnymagazine'),    value: 'center' }
					],
					onChange: function (val) { setAttributes({ alignment: val }); }
				})
			),

			// Panel: Publicaciones / Placeholder
			createElement(
				PanelBody,
				{ title: i18n.__('Publicaciones / Placeholder', 'wp_bunnymagazine'), initialOpen: false },

				createElement(SelectControl, {
					label:    i18n.__('Tipo de Placeholder', 'wp_bunnymagazine'),
					value:    attributes.placeholderType || 'none',
					options:  [
						{ label: i18n.__('Ninguno', 'wp_bunnymagazine'), value: 'none' },
						{ label: i18n.__('Grid', 'wp_bunnymagazine'),    value: 'grid' },
						{ label: i18n.__('Imagen', 'wp_bunnymagazine'),  value: 'image' }
					],
					onChange: function (val) { setAttributes({ placeholderType: val }); }
				}),

				(attributes.placeholderType === 'grid') ? createElement(SelectControl, {
					label:    i18n.__('Tamaño de Cuadrícula', 'wp_bunnymagazine'),
					value:    attributes.placeholderGridSize || '2x3',
					options:  [
						{ label: '2 × 2 (4 publicaciones)',  value: '2x2' },
						{ label: '2 × 3 (6 publicaciones)',  value: '2x3' },
						{ label: '3 × 3 (9 publicaciones)',  value: '3x3' },
						{ label: '3 × 4 (12 publicaciones)', value: '3x4' },
						{ label: '3 × 5 (15 publicaciones)', value: '3x5' }
					],
					onChange: function (val) { setAttributes({ placeholderGridSize: val }); }
				}) : null,

				(attributes.placeholderType === 'grid') ? createElement(SelectControl, {
					label:    i18n.__('Posición del Placeholder', 'wp_bunnymagazine'),
					value:    attributes.placeholderPosition || 'after_description',
					options:  [
						{ label: i18n.__('Arriba (antes de la descripción)', 'wp_bunnymagazine'), value: 'before_description' },
						{ label: i18n.__('Abajo (después de la descripción)', 'wp_bunnymagazine'), value: 'after_description' }
					],
					onChange: function (val) { setAttributes({ placeholderPosition: val }); }
				}) : null,

				// ── Image mode ─────────────────────────────────────────────
				(attributes.placeholderType === 'image') ? createElement(SelectControl, {
					label:    i18n.__('Posición de la Imagen', 'wp_bunnymagazine'),
					value:    attributes.placeholderPosition || 'after_description',
					options:  [
						{ label: i18n.__('Arriba (antes de la descripción)', 'wp_bunnymagazine'), value: 'before_description' },
						{ label: i18n.__('Abajo (después de la descripción)', 'wp_bunnymagazine'), value: 'after_description' }
					],
					onChange: function (val) { setAttributes({ placeholderPosition: val }); }
				}) : null,

				(attributes.placeholderType === 'image') ? createElement(
					MediaUploadCheck, null,
					createElement(
						MediaUpload,
						{
							onSelect: function (media) { setAttributes({ placeholderImage: media.url }); },
							allowedTypes: ['image'],
							value:    attributes.placeholderImage || '',
							render: function (ref) {
								var open = ref.open;
								return createElement(
									'div', { className: 'wpbm-media-upload-wrap' },
									attributes.placeholderImage
										? createElement(
											'div', { className: 'wpbm-media-preview' },
											createElement('img', {
												src:   attributes.placeholderImage,
												alt:   '',
												style: { width: '100%', height: '80px', objectFit: 'cover', borderRadius: '6px', marginBottom: '8px', display: 'block' }
											}),
											createElement(Button, { variant: 'secondary', isSmall: true, onClick: open }, i18n.__('Reemplazar imagen', 'wp_bunnymagazine')),
											createElement('span', { style: { margin: '0 6px' } }, ''),
											createElement(Button, { variant: 'tertiary', isSmall: true, isDestructive: true, onClick: function () { setAttributes({ placeholderImage: '' }); } }, i18n.__('Eliminar', 'wp_bunnymagazine'))
										)
										: createElement(Button, { variant: 'secondary', onClick: open }, i18n.__('Seleccionar imagen', 'wp_bunnymagazine'))
								);
							}
						}
					)
				) : null
			),

			// Panel: Botón y Enlace
			createElement(
				PanelBody,
				{ title: i18n.__('Botón y Enlace', 'wp_bunnymagazine'), initialOpen: false },

				createElement(ToggleControl, {
					label:    i18n.__('Mostrar botón', 'wp_bunnymagazine'),
					checked:  attributes.showButton !== false,
					onChange: function (val) { setAttributes({ showButton: val }); }
				}),

				attributes.showButton !== false ? createElement(TextControl, {
					label:    i18n.__('Texto del botón', 'wp_bunnymagazine'),
					value:    attributes.buttonText || '',
					onChange: function (val) { setAttributes({ buttonText: val }); }
				}) : null,

				attributes.showButton !== false ? createElement(
					'div',
					{ className: 'components-base-control' },
					createElement('label', { className: 'components-base-control__label', style: { display: 'block', marginBottom: '4px' } }, i18n.__('URL de destino', 'wp_bunnymagazine')),
					createElement(URLInput, {
						value:    attributes.url || '',
						onChange: function (val) { setAttributes({ url: val }); }
					})
				) : null,

				attributes.showButton !== false ? createElement(ToggleControl, {
					label:    i18n.__('Abrir en nueva pestaña', 'wp_bunnymagazine'),
					checked:  attributes.openInNewTab !== false,
					onChange: function (val) { setAttributes({ openInNewTab: val }); }
				}) : null
			)
		);
	}

	// ── Block attribute schema ────────────────────────────────────────────────

	function blockAttributes(layout) {
		// Static layouts declare their own attributes in layout.json.
		// Skip all query-related attributes so the schema matches PHP exactly.
		if (layout && layout.type === 'static') {
			var staticAttrs = {};
			if (layout.attributes && typeof layout.attributes === 'object') {
				Object.assign(staticAttrs, layout.attributes);
			}
			return staticAttrs;
		}

		var block    = layout.block || {};
		var query    = block.query || {};
		var controls = block.controls || {};

		var attributes = {
			limit:              { type: 'integer', default: query.limit || 1 },
			contentSource:      { type: 'string',  default: 'latest_posts' },
			period:             { type: 'string',  default: 'week' },
			categoriesInclude:  { type: 'array', default: [], items: { type: 'integer' } },
			categoriesExclude:  { type: 'array', default: [], items: { type: 'integer' } },
			tagsInclude:        { type: 'array', default: [], items: { type: 'integer' } },
			tagsExclude:        { type: 'array', default: [], items: { type: 'integer' } },
			authorsInclude:     { type: 'array', default: [], items: { type: 'integer' } },
			authorsExclude:     { type: 'array', default: [], items: { type: 'integer' } },
			ctaLabel:           { type: 'string', default: block.ctaLabel || i18n.__('Read more', 'wp_bunnymagazine') },
		};

		if (layout.attributes) {
			Object.assign(attributes, layout.attributes);
		}

		if (controls.categorySelector) {
			attributes.categoryId = { type: 'integer', default: 0 };
		}

		if (controls.sectionTitle) {
			attributes.sectionTitle = { type: 'string', default: '' };
		}

		return attributes;
	}

	// ── Edit factory ─────────────────────────────────────────────────────────

	function editFactory(slug, layout, controls) {
		return function (props) {
			var blockProps = useBlockProps({
				className: 'wpbm-editor-block'
			});

			var isStatic = (layout && layout.type === 'static') || slug === 'social-profile';
			var controlsElement = isStatic
				? socialProfileControls(props)
				: commonControls(props, controls);

			return createElement(
				'div',
				blockProps,
				controlsElement,
				createElement(
					'div',
					{ className: 'wpbm-editor-preview' },
					createElement(ServerSideRender, {
						block: 'wp-bunnymagazine/' + slug,
						attributes: props.attributes
					})
				)
			);
		};
	}

	// ── Block registration ────────────────────────────────────────────────────

	var editorLayouts = window.wpbmEditorLayouts || {};
	Object.keys(editorLayouts).forEach(function (slug) {
		var layout   = editorLayouts[slug] || {};
		var block    = layout.block || {};
		var controls = block.controls || {};
		var query    = block.query || {};

		blocks.registerBlockType('wp-bunnymagazine/' + slug, {
			apiVersion:  2,
			title:       block.title || layout.title || slug,
			description: block.description || layout.description || '',
			category:    'widgets',
			icon:        'layout',
			supports:    { html: false },
			attributes:  blockAttributes(layout),
			edit:        editFactory(slug, layout, Object.assign({}, controls, {
				limitMin: query.minItems || 1,
				limitMax: query.maxItems || query.limit || 1,
			})),
			save: function () { return null; }
		});
	});

})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.serverSideRender, window.wp.data, window.wp.i18n);
