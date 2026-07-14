(function (blocks, element, components, blockEditor, serverSideRender, data, i18n) {
	'use strict';

	var createElement  = element.createElement;
	var useState       = element.useState;
	var useSelect      = data.useSelect;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps     = blockEditor.useBlockProps;
	var ServerSideRender  = serverSideRender;
	var PanelBody      = components.PanelBody;
	var TextControl    = components.TextControl;
	var RangeControl   = components.RangeControl;
	var SelectControl  = components.SelectControl;
	var SearchControl  = components.SearchControl;
	var CheckboxControl = components.CheckboxControl;
	var Button         = components.Button;
	var ComboboxControl = components.ComboboxControl;

	// ── wpbmQueryConfig (injected by Block_Registry via wp_localize_script) ──
	// Shape: { sources: [{value, label}], filtersEnabled: {category, tag, author} }
	var queryConfig     = window.wpbmQueryConfig || {};
	var contentSources  = queryConfig.sources || [
		{ value: 'latest_posts',          label: 'Latest Posts' },
		{ value: 'latest_updated_posts',  label: 'Latest Updated Posts' },
		{ value: 'random_posts',          label: 'Random Posts' },
		{ value: 'wpam_top_posts',        label: 'Affiliate Top Posts' },
		{ value: 'wpam_top_viewed_posts', label: 'Affiliate Top Viewed Posts' },
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
		var selectedIds    = props.selectedIds || [];
		var selectedKey    = selectedIds.join(',');
		var queryKey       = JSON.stringify(props.query || {});
		var selectedQueryKey = JSON.stringify(props.selectedQuery || {});
		var _s             = useState('');
		var search         = _s[0];
		var setSearch      = _s[1];

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
		var _s2          = useState('');
		var search       = _s2[0];
		var setSearch    = _s2[1];
		var selectedValue = parseInt(props.value, 10) || 0;
		var queryKey     = JSON.stringify(props.query || {});

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
	// Renders ONE MultiEntityControl plus an "Exclude selected" checkbox for
	// a given Include/Exclude attribute pair. The two attributes themselves
	// (categoriesInclude/categoriesExclude, tagsInclude/tagsExclude,
	// authorsInclude/authorsExclude) are unchanged — this only changes how
	// the editor reads and writes them.
	//
	// Read (state derivation, also used when opening pre-existing blocks):
	//   - excludeMode = excludeIds.length > 0 (Exclude has data → checkbox ON).
	//   - selectedIds = excludeMode ? excludeIds : includeIds.
	//   - If both attributes happen to have data (legacy/manual edit),
	//     Exclude wins for the initial checkbox state.
	//
	// Write:
	//   - Changing the selection writes only to the currently active
	//     attribute and force-clears the other one, so the two attributes
	//     can never both hold data after a user interaction.
	//   - Toggling the checkbox moves the current selection from one
	//     attribute to the other, so the user's picks aren't lost when
	//     switching between Include and Exclude.
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
			// Normalize: the inactive attribute is always cleared whenever
			// this filter is touched, so Include/Exclude never coexist again.
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

	// ── commonControls ───────────────────────────────────────────────────────

	function commonControls(props, controls) {
		var attributes    = props.attributes;
		var setAttributes = props.setAttributes;

		// Build Content Source select options from wpbmQueryConfig.
		var sourceOptions = contentSources.map(function (src) {
			return { label: src.label, value: src.value };
		});

		return createElement(
			InspectorControls, null,

			// ── Panel: Content ──────────────────────────────────────────────
			createElement(
				PanelBody,
				{ title: i18n.__('Content', 'wp_bunnymagazine'), initialOpen: true },

				// Content Source selector (new).
				createElement(SelectControl, {
					label:    i18n.__('Content source', 'wp_bunnymagazine'),
					value:    attributes.contentSource || 'latest_posts',
					options:  sourceOptions,
					onChange: function (value) { setAttributes({ contentSource: value }); }
				}),

				// Period selector (new) — only shown for WPAM sources.
				(attributes.contentSource === 'wpam_top_posts' || attributes.contentSource === 'wpam_top_viewed_posts') ? createElement(SelectControl, {
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

				// Post limit (pre-existing).
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

				// Single category selector (pre-existing, only if layout declares it).
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

			// ── Panel: Filters ──────────────────────────────────────────────
			// Rendered only when at least one filter type is enabled in settings.
			// Each filter type is a single MultiEntityControl plus an "Exclude
			// selected" checkbox; internally still backed by the same two
			// Include/Exclude attributes as before (see toggleableEntityFilter).
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

			// ── Panel: Layout ───────────────────────────────────────────────
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

	// ── Block attribute schema ────────────────────────────────────────────────

	function blockAttributes(layout) {
		var block    = layout.block || {};
		var query    = block.query || {};
		var controls = block.controls || {};

		var attributes = {
			limit:              { type: 'integer', default: query.limit || 1 },
			// Content source (new).
			contentSource:      { type: 'string',  default: 'latest_posts' },
			// Period (new) — only used by wpam_top_posts.
			period:             { type: 'string',  default: 'week' },
			// Filters (pre-existing).
			categoriesInclude:  { type: 'array', default: [], items: { type: 'integer' } },
			categoriesExclude:  { type: 'array', default: [], items: { type: 'integer' } },
			tagsInclude:        { type: 'array', default: [], items: { type: 'integer' } },
			tagsExclude:        { type: 'array', default: [], items: { type: 'integer' } },
			authorsInclude:     { type: 'array', default: [], items: { type: 'integer' } },
			authorsExclude:     { type: 'array', default: [], items: { type: 'integer' } },
			ctaLabel:           { type: 'string', default: block.ctaLabel || i18n.__('Read more', 'wp_bunnymagazine') },
		};

		if (controls.categorySelector) {
			attributes.categoryId = { type: 'integer', default: 0 };
		}

		if (controls.sectionTitle) {
			attributes.sectionTitle = { type: 'string', default: '' };
		}

		return attributes;
	}

	// ── Edit factory ─────────────────────────────────────────────────────────

	function editFactory(layoutSlug, controls) {
		return function (props) {

			var blockProps = useBlockProps({
				className: 'wpbm-editor-block'
			});

			return createElement(
				'div',
				blockProps,

				commonControls(props, controls),

				createElement(
					'div',
					{
						className: 'wpbm-editor-preview'
					},
					createElement(ServerSideRender, {
						block: 'wp-bunnymagazine/' + layoutSlug,
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
			edit:        editFactory(slug, Object.assign({}, controls, {
				limitMin: query.minItems || 1,
				limitMax: query.maxItems || query.limit || 1,
			})),
			save: function () { return null; }
		});
	});

})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.serverSideRender, window.wp.data, window.wp.i18n);
