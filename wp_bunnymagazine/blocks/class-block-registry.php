<?php

declare(strict_types=1);

namespace BunnyMagazine\Blocks;

use BunnyMagazine\Core\Cache_Manager;
use BunnyMagazine\Core\Layout_Engine;
use BunnyMagazine\Core\Layout_Registry;
use BunnyMagazine\Core\Query_Manager;
use BunnyMagazine\Core\Utils;

if (!defined('ABSPATH')) {
	exit;
}

final class Block_Registry
{
	private static ?Query_Manager $query_manager = null;
	private static ?Layout_Engine $layout_engine = null;

	public static function boot(Query_Manager $query_manager, Layout_Engine $layout_engine): void
	{
		self::$query_manager = $query_manager;
		self::$layout_engine = $layout_engine;

		add_action('enqueue_block_editor_assets', [self::class, 'register_editor_assets']);
	}

	public static function register_all(): void
	{
		$shared_script       = 'wpbm-block-editor';
		$shared_style        = 'wpbm-block-style';
		$shared_editor_style = 'wpbm-block-editor-style';

		wp_register_script(
			$shared_script,
			WPMB_URL . 'assets/js/editor.js',
			['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-data', 'wp-i18n'],
			WPMB_VERSION,
			true
		);

		// Expose layout definitions to the editor (pre-existing).
		wp_localize_script(
			$shared_script,
			'wpbmEditorLayouts',
			Layout_Registry::editor_layouts()
		);

		// Expose query config: content sources + filter visibility flags (new).
		wp_localize_script(
			$shared_script,
			'wpbmQueryConfig',
			self::query_config_for_editor()
		);

		wp_register_style(
			$shared_style,
			WPMB_URL . 'assets/css/frontend.css',
			[],
			WPMB_VERSION
		);

		wp_register_style(
			$shared_editor_style,
			WPMB_URL . 'assets/css/admin.css',
			[$shared_style],
			WPMB_VERSION
		);

		foreach (Layout_Registry::all() as $slug => $layout) {
			$block             = self::block_meta($layout, $slug);
			$block_name        = 'wp-bunnymagazine/' . $slug;
			$assets            = is_array($layout['assets'] ?? null) ? $layout['assets'] : [];
			$style_file        = 'style.css';
			$editor_style_file = (string) ($assets['editor'] ?? 'editor.css');
			$script_file       = (string) ($assets['script'] ?? '');
			$style_handle      = 'wpbm-layout-' . $slug;
			$editor_style_handle = 'wpbm-layout-' . $slug . '-editor';

			if ($style_file !== '') {
				wp_register_style($style_handle, WPMB_URL . 'layouts/' . $slug . '/' . $style_file, [$shared_style], WPMB_VERSION);
			}

			if ($editor_style_file !== '') {
				wp_register_style($editor_style_handle, WPMB_URL . 'layouts/' . $slug . '/' . $editor_style_file, [$style_handle, $shared_editor_style], WPMB_VERSION);
			}

			if ($script_file !== '') {
				wp_register_script('wpbm-layout-' . $slug, WPMB_URL . 'layouts/' . $slug . '/' . $script_file, [], WPMB_VERSION, true);
			}

			register_block_type(
				$block_name,
				[
					'api_version'     => 2,
					'title'           => $block['title'],
					'category'        => 'widgets',
					'icon'            => 'layout',
					'description'     => $block['description'],
					'textdomain'      => 'wp_bunnymagazine',
					'attributes'      => self::attributes_schema($layout),
					'editor_script'   => $shared_script,
					'style'           => $style_handle,
					'editor_style'    => $editor_style_handle,
					'render_callback' => [self::class, 'render_block'],
				]
			);
		}
	}

	public static function register_editor_assets(): void
	{
		wp_enqueue_style('wpbm-block-editor-style');
	}

	public static function render_block(array $attributes, string $content = '', ?\WP_Block $block = null): string
	{
		if (defined('REST_REQUEST') && REST_REQUEST) {
			@ini_set('display_errors', '0');
			@ini_set('display_startup_errors', '0');
		}

		$layout_slug = self::layout_slug_from_block($block);
		if ($layout_slug === '') {
			return '';
		}

		$layout = Layout_Registry::get($layout_slug);
		if (!$layout || self::$query_manager === null || self::$layout_engine === null) {
			return '';
		}

		$query_config = self::resolve_query_config($layout, $attributes);
		$posts        = self::$query_manager->fetch($query_config);

		if (wp_style_is('wpbm-layout-' . $layout_slug, 'registered')) {
			wp_enqueue_style('wpbm-layout-' . $layout_slug);
		}

		if ((is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) && wp_style_is('wpbm-layout-' . $layout_slug . '-editor', 'registered')) {
			wp_enqueue_style('wpbm-layout-' . $layout_slug . '-editor');
		}

		$assets      = is_array($layout['assets'] ?? null) ? $layout['assets'] : [];
		$script_file = (string) ($assets['script'] ?? '');
		if ($script_file !== '' && wp_script_is('wpbm-layout-' . $layout_slug, 'registered')) {
			wp_enqueue_script('wpbm-layout-' . $layout_slug);
		}

		$attributes['layout'] = $layout_slug;

		return self::$layout_engine->render($layout_slug, $posts, $attributes);
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Build the wpbmQueryConfig object exposed to the editor.
	 *
	 * Contains:
	 *  - sources:        array of { value, label } for the Content Source selector.
	 *  - filtersEnabled: object with boolean flags controlling filter UI visibility.
	 *
	 * The sources list is built from the 'wpbm_editor_content_sources' filter
	 * so third-party providers can register their label without touching
	 * any core plugin file.
	 */
	private static function query_config_for_editor(): array
	{
		// Built-in sources.
		$sources = [
			[
				'value' => Query_Manager::SOURCE_LATEST_POSTS,
				'label' => __('Latest Posts', 'wp_bunnymagazine'),
			],
			[
				'value' => Query_Manager::SOURCE_LATEST_UPDATED_POSTS,
				'label' => __('Latest Updated Posts', 'wp_bunnymagazine'),
			],
			[
				'value' => Query_Manager::SOURCE_RANDOM_POSTS,
				'label' => __('Random Posts', 'wp_bunnymagazine'),
			],
			[
				'value' => Query_Manager::SOURCE_WPAM_TOP_POSTS,
				'label' => __('Affiliate Top Posts', 'wp_bunnymagazine'),
			],
			[
				'value' => Query_Manager::SOURCE_WPAM_TOP_VIEWED_POSTS,
				'label' => __('Affiliate Top Viewed Posts', 'wp_bunnymagazine'),
			],
		];

		/**
		 * Filter: wpbm_editor_content_sources
		 *
		 * Allows third-party modules to add their provider labels to the
		 * editor Content Source selector. Must return an array of
		 * { value: string, label: string } items.
		 *
		 * @param array $sources Registered editor source definitions.
		 * @return array
		 */
		$sources = apply_filters('wpbm_editor_content_sources', $sources);

		return [
			'sources'        => array_values(is_array($sources) ? $sources : []),
			'filtersEnabled' => [
				'category' => Cache_Manager::filter_category_enabled(),
				'tag'      => Cache_Manager::filter_tag_enabled(),
				'author'   => Cache_Manager::filter_author_enabled(),
			],
		];
	}

	private static function layout_slug_from_block(?\WP_Block $block): string
	{
		if (!$block instanceof \WP_Block || empty($block->block_type->name)) {
			return '';
		}

		$name = (string) $block->block_type->name;
		$slug = str_replace('wp-bunnymagazine/', '', $name);

		return sanitize_key($slug);
	}

	private static function block_meta(array $layout, string $slug): array
	{
		$block       = is_array($layout['block'] ?? null) ? $layout['block'] : [];
		$title       = (string) ($block['title'] ?? ucwords(str_replace('-', ' ', $slug)));
		$description = (string) ($block['description'] ?? ($layout['description'] ?? ''));

		return [
			'title'       => $title,
			'description' => $description,
		];
	}

	/**
	 * Build the Gutenberg block attributes schema.
	 * Adds contentSource (new) alongside all pre-existing attributes.
	 */
	private static function attributes_schema(array $layout): array
	{
		$block          = is_array($layout['block'] ?? null) ? $layout['block'] : [];
		$query_defaults = is_array($block['query'] ?? null) ? $block['query'] : [];
		$controls       = is_array($block['controls'] ?? null) ? $block['controls'] : [];

		$schema = [
			// Pre-existing attributes (unchanged).
			'limit' => [
				'type'    => 'integer',
				'default' => max(1, (int) ($query_defaults['limit'] ?? 6)),
			],
			'ctaLabel' => [
				'type'    => 'string',
				'default' => (string) ($block['ctaLabel'] ?? __('Read more', 'wp_bunnymagazine')),
			],
			'categoriesInclude' => [
				'type'    => 'array',
				'default' => [],
				'items'   => ['type' => 'integer'],
			],
			'categoriesExclude' => [
				'type'    => 'array',
				'default' => [],
				'items'   => ['type' => 'integer'],
			],
			'tagsInclude' => [
				'type'    => 'array',
				'default' => [],
				'items'   => ['type' => 'integer'],
			],
			'tagsExclude' => [
				'type'    => 'array',
				'default' => [],
				'items'   => ['type' => 'integer'],
			],
			'authorsInclude' => [
				'type'    => 'array',
				'default' => [],
				'items'   => ['type' => 'integer'],
			],
			'authorsExclude' => [
				'type'    => 'array',
				'default' => [],
				'items'   => ['type' => 'integer'],
			],
			'categoryId' => [
				'type'    => 'integer',
				'default' => 0,
			],
			'sectionTitle' => [
				'type'    => 'string',
				'default' => '',
			],
			// New: content source selector.
			'contentSource' => [
				'type'    => 'string',
				'default' => Query_Manager::SOURCE_LATEST_POSTS,
			],
			// New: period selector (only used by wpam_top_posts).
			'period' => [
				'type'    => 'string',
				'default' => 'week',
			],
		];

		if (empty($controls['categorySelector'])) {
			$schema['categoryId']['default'] = 0;
		}

		if (empty($controls['sectionTitle'])) {
			$schema['sectionTitle']['default'] = '';
		}

		return $schema;
	}

	/**
	 * Translate raw Gutenberg block attributes (camelCase) into the single
	 * canonical Query Object consumed by Query_Manager::fetch().
	 *
	 * Block_Registry is the ONLY place in the plugin allowed to read
	 * camelCase attribute names. Everything this method returns uses the
	 * one internal contract, flat and snake_case:
	 *
	 *   source, limit, period,
	 *   categories_include, categories_exclude,
	 *   tags_include, tags_exclude,
	 *   authors_include, authors_exclude
	 *
	 * Query_Manager never reads block attributes directly and never
	 * reinterprets this shape — it trusts this method completely.
	 *
	 * The legacy single-category attribute ('categoryId') is folded into
	 * categories_include right here. Query_Manager has no knowledge of
	 * 'categoryId' at all; that backward-compat concern stays fully
	 * contained in this one method.
	 */
	private static function resolve_query_config(array $layout, array $attributes): array
	{
		$block       = is_array($layout['block'] ?? null) ? $layout['block'] : [];
		$query_config = is_array($block['query'] ?? null) ? $block['query'] : [];
		$mode        = (string) ($query_config['mode'] ?? 'configurable');
		$min_items   = max(1, (int) ($query_config['minItems'] ?? ($query_config['limit'] ?? 1)));
		$max_items   = max($min_items, (int) ($query_config['maxItems'] ?? ($query_config['limit'] ?? $min_items)));
		$fixed_limit = max(1, (int) ($query_config['limit'] ?? $min_items));
		$limit       = $fixed_limit;

		if ($mode !== 'fixed') {
			$limit = isset($attributes['limit']) ? (int) $attributes['limit'] : $fixed_limit;
			$limit = max($min_items, min($max_items, $limit));
		}

		// Legacy single-category selector folded into categories_include.
		// This merge happens here and only here.
		$categories_include = Utils::sanitize_id_list($attributes['categoriesInclude'] ?? []);
		if (!empty($attributes['categoryId'])) {
			$categories_include = array_values(array_unique(array_merge(
				$categories_include,
				[(int) $attributes['categoryId']]
			)));
			sort($categories_include);
		}

		return [
			'source'             => (string) ($attributes['contentSource'] ?? Query_Manager::SOURCE_LATEST_POSTS),
			'limit'              => $limit,
			'period'             => (string) ($attributes['period'] ?? 'week'),
			'categories_include' => $categories_include,
			'categories_exclude' => Utils::sanitize_id_list($attributes['categoriesExclude'] ?? []),
			'tags_include'       => Utils::sanitize_id_list($attributes['tagsInclude'] ?? []),
			'tags_exclude'       => Utils::sanitize_id_list($attributes['tagsExclude'] ?? []),
			'authors_include'    => Utils::sanitize_author_id_list($attributes['authorsInclude'] ?? []),
			'authors_exclude'    => Utils::sanitize_author_id_list($attributes['authorsExclude'] ?? []),
		];
	}
}
