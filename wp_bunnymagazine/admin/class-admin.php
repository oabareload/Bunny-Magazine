<?php

declare(strict_types=1);

namespace BunnyMagazine\Admin;

use BunnyMagazine\Core\Cache_Manager;
use BunnyMagazine\Core\Layout_Registry;

if (!defined('ABSPATH')) {
	exit;
}

final class Admin
{
	/**
	 * Field ownership map, keyed by the value of the hidden
	 * 'wpbm_settings_form' input each admin <form> submits.
	 *
	 * This lets sanitize_settings() distinguish, per field:
	 *  - "this form owns this field and it's absent" → unchecked checkbox,
	 *    must be treated as 0/false, NOT inherited from the previous value.
	 *  - "this form does not own this field" → field belongs to a
	 *    different tab, must be inherited unchanged from the previous value.
	 *
	 * Add a new form_id => [field, ...] entry whenever a new settings
	 * tab/form is introduced.
	 */
	private const FORM_FIELDS = [
		'cache' => [
			'cache_enabled',
			'cache_ttl',
			'invalidate_on_publish',
			'invalidate_on_update',
		],
		'query' => [
			'filter_category_enabled',
			'filter_tag_enabled',
			'filter_author_enabled',
		],
	];

	public static function boot(): void
	{
		add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
	}

	public static function menu(): void
	{
		add_menu_page(
			__('Bunny Magazine', 'wp_bunnymagazine'),
			__('Bunny Magazine', 'wp_bunnymagazine'),
			'manage_options',
			'wpbm-dashboard',
			[self::class, 'render_dashboard'],
			'dashicons-media-document',
			58
		);

		add_submenu_page('wpbm-dashboard', __('Dashboard', 'wp_bunnymagazine'),     __('Dashboard', 'wp_bunnymagazine'),     'manage_options', 'wpbm-dashboard', [self::class, 'render_dashboard']);
		add_submenu_page('wpbm-dashboard', __('Layouts', 'wp_bunnymagazine'),       __('Layouts', 'wp_bunnymagazine'),       'manage_options', 'wpbm-layouts',   [self::class, 'render_layouts']);
		add_submenu_page('wpbm-dashboard', __('Query Settings', 'wp_bunnymagazine'), __('Query Settings', 'wp_bunnymagazine'), 'manage_options', 'wpbm-query',     [self::class, 'render_query']);
		add_submenu_page('wpbm-dashboard', __('Cache', 'wp_bunnymagazine'),         __('Cache', 'wp_bunnymagazine'),         'manage_options', 'wpbm-cache',     [self::class, 'render_cache']);
	}

	public static function register_settings(): void
	{
		register_setting('wpbm_settings_group', 'wpbm_settings', [self::class, 'sanitize_settings']);
	}

	/**
	 * Sanitize and persist wpbm_settings.
	 *
	 * ROOT CAUSE THIS METHOD FIXES:
	 * Cache, Query Settings (and any future tab) all write to the SAME
	 * 'wpbm_settings' option, but each admin screen submits its own
	 * <form> containing only the fields relevant to that screen. The
	 * Settings API passes this method only what was POSTed. Sanitizing
	 * that partial array in isolation — as the previous implementation
	 * did — silently reset every field belonging to OTHER tabs back to
	 * their hardcoded defaults on every single save, in both directions
	 * (saving Cache wiped Query Settings' filter toggles to 0, and saving
	 * Query Settings reset cache_ttl back to 360, etc.).
	 *
	 * FIX STRATEGY:
	 * 1. Identify which form submitted, via the hidden 'wpbm_settings_form'
	 *    field each view now includes.
	 * 2. For fields OWNED by that form: take the submitted value literally,
	 *    including "absent" (which correctly means an unchecked checkbox).
	 * 3. For fields owned by ANY OTHER form: always inherit the existing
	 *    stored value, completely ignoring whatever (if anything) arrived
	 *    in $settings for them.
	 * 4. Sanitize the resulting fully-merged array.
	 *
	 * This guarantees saving one screen can never alter, reset, or wipe
	 * any field that belongs to a different screen — regardless of
	 * whether that field is a checkbox, a number, or anything else.
	 *
	 * @param array $settings Raw POSTed settings (partial — only the
	 *                         submitting form's own fields, per HTML form).
	 * @return array Fully sanitized settings, all known keys present.
	 */
	public static function sanitize_settings(array $settings): array
	{
		$previous = Cache_Manager::settings();
		$form_id  = isset($_POST['wpbm_settings_form']) ? sanitize_key((string) $_POST['wpbm_settings_form']) : '';

		$merged = self::merge_by_ownership($settings, $previous, $form_id);

		$sanitized = [
			// Cache settings — owned by the "Cache" tab form.
			'cache_enabled'         => !empty($merged['cache_enabled'])        ? 1 : 0,
			'cache_ttl'             => max(5, (int) ($merged['cache_ttl'] ?? 360)),
			'invalidate_on_publish' => !empty($merged['invalidate_on_publish']) ? 1 : 0,
			'invalidate_on_update'  => !empty($merged['invalidate_on_update'])  ? 1 : 0,
			// Filter UI visibility settings — owned by the "Query Settings" tab form.
			// These control both editor UI visibility AND are enforced
			// server-side in Query_Manager::normalize() — see that class
			// for the enforcement logic.
			'filter_category_enabled' => !empty($merged['filter_category_enabled']) ? 1 : 0,
			'filter_tag_enabled'      => !empty($merged['filter_tag_enabled'])      ? 1 : 0,
			'filter_author_enabled'   => !empty($merged['filter_author_enabled'])   ? 1 : 0,
		];

		if (self::query_settings_changed($previous, $sanitized)) {
			// A filter visibility flag actually changed value (not just
			// re-saved, and not just because a sibling form was saved).
			// Any transient cached under the old normalized config could
			// contain results built with filters that are now disabled
			// (or vice versa). Bump the cache version so all such entries
			// are invalidated immediately — same mechanism used for
			// publish/update invalidation.
			Cache_Manager::clear();
		}

		return $sanitized;
	}

	/**
	 * Merge submitted settings on top of previously stored settings,
	 * respecting field ownership so unrelated tabs are never touched.
	 *
	 * - Fields owned by $form_id: taken from $settings as-is (including
	 *   "missing" → caller will treat as falsy/0 downstream, which is the
	 *   correct interpretation for an unchecked checkbox on its own form).
	 * - Fields owned by any other known form, or $form_id unrecognised:
	 *   always taken from $previous, ignoring $settings entirely for them.
	 *
	 * @param array  $settings Raw POSTed settings (partial).
	 * @param array  $previous Previously persisted settings.
	 * @param string $form_id  Value of the submitting form's 'wpbm_settings_form' field.
	 * @return array Merged settings, respecting per-form field ownership.
	 */
	private static function merge_by_ownership(array $settings, array $previous, string $form_id): array
	{
		// Unknown/missing form id: be conservative and keep everything as
		// it was, only allowing keys that are already present in $settings
		// to override — this should not normally happen via the plugin's
		// own UI, but guards against a malformed or unexpected POST.
		if (!isset(self::FORM_FIELDS[$form_id])) {
			return wp_parse_args($settings, $previous);
		}

		$owned_fields = self::FORM_FIELDS[$form_id];
		$merged       = $previous;

		foreach ($owned_fields as $field) {
			// Literal take from $settings, including "not set" (unchecked
			// checkbox case). Do NOT fall back to $previous for these.
			$merged[$field] = $settings[$field] ?? null;
		}

		return $merged;
	}

	/**
	 * Whether any of the query/filter-relevant settings differ between
	 * the previously stored settings and the newly sanitized ones.
	 */
	private static function query_settings_changed(array $previous, array $sanitized): bool
	{
		$keys = ['filter_category_enabled', 'filter_tag_enabled', 'filter_author_enabled'];

		foreach ($keys as $key) {
			$old_value = !empty($previous[$key]) ? 1 : 0;
			$new_value = !empty($sanitized[$key]) ? 1 : 0;

			if ($old_value !== $new_value) {
				return true;
			}
		}

		return false;
	}

	public static function enqueue_assets(string $hook): void
	{
		if (strpos($hook, 'wpbm-') === false) {
			return;
		}

		wp_enqueue_style('wpbm-bunny-admin', WPMB_URL . 'assets/css/bunny-admin.css', [], WPMB_VERSION);
		wp_enqueue_style('wpbm-admin', WPMB_URL . 'assets/css/admin.css', ['wpbm-bunny-admin'], WPMB_VERSION);
	}

	public static function render_dashboard(): void
	{
		$layout_count  = Layout_Registry::detect_count();
		$cache_version = Cache_Manager::version();
		$current_tab   = 'wpbm-dashboard';
		$page_label    = __('Dashboard', 'wp_bunnymagazine');
		include WPMB_PATH . 'admin/views/dashboard.php';
	}

	public static function render_layouts(): void
	{
		$layouts         = Layout_Registry::all();
		$last_scanned_at = Layout_Registry::last_scanned_at();
		$current_tab     = 'wpbm-layouts';
		$page_label      = __('Layouts', 'wp_bunnymagazine');
		include WPMB_PATH . 'admin/views/layouts.php';
	}

	/**
	 * Query Settings tab — content source / filter visibility configuration.
	 * Separate from Cache to keep query-related settings semantically grouped
	 * and to leave room for future provider-specific settings (WooCommerce,
	 * CPTs, etc.) without crowding the Cache tab.
	 */
	public static function render_query(): void
	{
		$settings    = Cache_Manager::settings();
		$current_tab = 'wpbm-query';
		$page_label  = __('Query Settings', 'wp_bunnymagazine');
		include WPMB_PATH . 'admin/views/query.php';
	}

	public static function render_cache(): void
	{
		$settings    = Cache_Manager::settings();
		$current_tab = 'wpbm-cache';
		$page_label  = __('Cache', 'wp_bunnymagazine');
		include WPMB_PATH . 'admin/views/cache.php';
	}

	public static function handle_rescan(): void
	{
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Unauthorized', 'wp_bunnymagazine'));
		}

		check_admin_referer('wpbm_rescan_layouts');
		Layout_Registry::clear_cache();
		Layout_Registry::refresh_manifest();
		wp_safe_redirect(add_query_arg(['page' => 'wpbm-layouts', 'wpbm_notice' => 'rescanned'], admin_url('admin.php')));
		exit;
	}

	public static function handle_clear_cache(): void
	{
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Unauthorized', 'wp_bunnymagazine'));
		}

		check_admin_referer('wpbm_clear_cache');
		Cache_Manager::clear();
		wp_safe_redirect(add_query_arg(['page' => 'wpbm-cache', 'wpbm_notice' => 'cleared'], admin_url('admin.php')));
		exit;
	}
}
