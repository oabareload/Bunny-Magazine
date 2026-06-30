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
	 * Both the Cache tab and the Query Settings tab submit to this same
	 * option, so this method must detect whether the *query-relevant*
	 * fields (filter visibility flags) actually changed, and only then
	 * auto-invalidate the cache. This avoids bumping the cache version
	 * every time someone just tweaks the TTL or cache toggle, while still
	 * guaranteeing stale results can't survive a filter setting change.
	 *
	 * @param array $settings Raw POSTed settings.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings(array $settings): array
	{
		$previous = Cache_Manager::settings();

		$sanitized = [
			// Cache settings (pre-existing) — live under the "Cache" tab.
			'cache_enabled'         => !empty($settings['cache_enabled'])        ? 1 : 0,
			'cache_ttl'             => max(5, (int) ($settings['cache_ttl'] ?? 360)),
			'invalidate_on_publish' => !empty($settings['invalidate_on_publish']) ? 1 : 0,
			'invalidate_on_update'  => !empty($settings['invalidate_on_update'])  ? 1 : 0,
			// Filter UI visibility settings (new) — live under the "Query Settings" tab.
			// These control both editor UI visibility AND are enforced
			// server-side in Query_Manager::normalize() — see that class
			// for the enforcement logic.
			'filter_category_enabled' => !empty($settings['filter_category_enabled']) ? 1 : 0,
			'filter_tag_enabled'      => !empty($settings['filter_tag_enabled'])      ? 1 : 0,
			'filter_author_enabled'   => !empty($settings['filter_author_enabled'])   ? 1 : 0,
		];

		if (self::query_settings_changed($previous, $sanitized)) {
			// A filter visibility flag flipped. Any transient cached under
			// the old normalized config could contain results built with
			// filters that are now disabled (or vice versa). Bump the
			// cache version so all such entries are invalidated immediately,
			// the same mechanism used for publish/update invalidation.
			Cache_Manager::clear();
		}

		return $sanitized;
	}

	/**
	 * Whether any of the query/filter-relevant settings differ between
	 * the previously stored settings and the newly sanitized ones.
	 */
	private static function query_settings_changed(array $previous, array $next): bool
	{
		$keys = ['filter_category_enabled', 'filter_tag_enabled', 'filter_author_enabled'];

		foreach ($keys as $key) {
			$old_value = !empty($previous[$key]) ? 1 : 0;
			$new_value = !empty($next[$key]) ? 1 : 0;

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
