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
		{ value: 'latest_posts',         label: 'Latest Posts' },
		{ value: 'latest_updated_posts', label: 'Latest Updated Posts' },
		{ value: 'random_posts',         label: 'Random Posts' },
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

	// ── commonControls ───────────────────────────────────────────────────────

	function commonControls(props, controls) {
		var attributes    = props.attributes;
		var setAttributes = props.setAttributes;

		// Category filter IDs.
		var categoryIds         = attributes.categoriesInclude || [];
		var excludedCategoryIds = attributes.categoriesExclude || [];

		// Tag filter IDs.
		var tagIds         = attributes.tagsInclude || [];
		var excludedTagIds = attributes.tagsExclude || [];

		// Author filter IDs.
		var authorIds         = attributes.authorsInclude || [];
		var excludedAuthorIds = attributes.authorsExclude || [];

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
			(filtersEnabled.category || filtersEnabled.tag || filtersEnabled.author)
				? createElement(
					PanelBody,
					{ title: i18n.__('Filters', 'wp_bunnymagazine'), initialOpen: false },

					// Category filters — visible only if enabled in settings.
					filtersEnabled.category ? createElement(MultiEntityControl, {
						label:       i18n.__('Include categories', 'wp_bunnymagazine'),
						placeholder: i18n.__('Search categories', 'wp_bunnymagazine'),
						entityKind:  'taxonomy',
						entityName:  'category',
						selectedIds: categoryIds,
						onChange:    function (value) { setAttributes({ categoriesInclude: value }); },
						query:       { hide_empty: false },
						emptyText:   i18n.__('No matching categories found.', 'wp_bunnymagazine')
					}) : null,

					filtersEnabled.category ? createElement(MultiEntityControl, {
						label:       i18n.__('Exclude categories', 'wp_bunnymagazine'),
						placeholder: i18n.__('Search categories', 'wp_bunnymagazine'),
						entityKind:  'taxonomy',
						entityName:  'category',
						selectedIds: excludedCategoryIds,
						onChange:    function (value) { setAttributes({ categoriesExclude: value }); },
						query:       { hide_empty: false },
						emptyText:   i18n.__('No matching categories found.', 'wp_bunnymagazine')
					}) : null,

					// Tag filters — visible only if enabled in settings.
					filtersEnabled.tag ? createElement(MultiEntityControl, {
						label:       i18n.__('Include tags', 'wp_bunnymagazine'),
						placeholder: i18n.__('Search tags', 'wp_bunnymagazine'),
						entityKind:  'taxonomy',
						entityName:  'post_tag',
						selectedIds: tagIds,
						onChange:    function (value) { setAttributes({ tagsInclude: value }); },
						query:       { hide_empty: false },
						emptyText:   i18n.__('No matching tags found.', 'wp_bunnymagazine')
					}) : null,

					filtersEnabled.tag ? createElement(MultiEntityControl, {
						label:       i18n.__('Exclude tags', 'wp_bunnymagazine'),
						placeholder: i18n.__('Search tags', 'wp_bunnymagazine'),
						entityKind:  'taxonomy',
						entityName:  'post_tag',
						selectedIds: excludedTagIds,
						onChange:    function (value) { setAttributes({ tagsExclude: value }); },
						query:       { hide_empty: false },
						emptyText:   i18n.__('No matching tags found.', 'wp_bunnymagazine')
					}) : null,

					// Author filters — visible only if enabled in settings (off by default).
					filtersEnabled.author ? createElement(MultiEntityControl, {
						label:         i18n.__('Include authors', 'wp_bunnymagazine'),
						placeholder:   i18n.__('Search authors', 'wp_bunnymagazine'),
						entityKind:    'root',
						entityName:    'user',
						selectedIds:   authorIds,
						onChange:      function (value) { setAttributes({ authorsInclude: value }); },
						query:         { who: 'authors' },
						selectedQuery: { who: 'authors' },
						emptyText:     i18n.__('No matching authors found.', 'wp_bunnymagazine')
					}) : null,

					filtersEnabled.author ? createElement(MultiEntityControl, {
						label:         i18n.__('Exclude authors', 'wp_bunnymagazine'),
						placeholder:   i18n.__('Search authors', 'wp_bunnymagazine'),
						entityKind:    'root',
						entityName:    'user',
						selectedIds:   excludedAuthorIds,
						onChange:      function (value) { setAttributes({ authorsExclude: value }); },
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
