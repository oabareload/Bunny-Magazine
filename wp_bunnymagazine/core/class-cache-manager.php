<?php

declare(strict_types=1);

namespace BunnyMagazine\Core;

if (!defined('ABSPATH')) {
	exit;
}

final class Cache_Manager
{
	private const OPTION_KEY  = 'wpbm_settings';
	private const VERSION_KEY = 'wpbm_cache_version';

	public static function bootstrap_defaults(): void
	{
		$settings = get_option(self::OPTION_KEY, []);
		if (!is_array($settings)) {
			$settings = [];
		}

		update_option(
			self::OPTION_KEY,
			wp_parse_args(
				$settings,
				[
					// Cache settings (pre-existing).
					'cache_enabled'          => 1,
					'cache_ttl'              => 360,
					'invalidate_on_publish'  => 1,
					'invalidate_on_update'   => 1,
					// Filter visibility settings (new).
					// category + tag ON by default, author OFF by default.
					'filter_category_enabled' => 1,
					'filter_tag_enabled'      => 1,
					'filter_author_enabled'   => 0,
				]
			)
		);

		if (get_option(self::VERSION_KEY, null) === null) {
			update_option(self::VERSION_KEY, 1);
		}
	}

	public static function settings(): array
	{
		$settings = get_option(self::OPTION_KEY, []);
		return is_array($settings) ? $settings : [];
	}

	public static function enabled(): bool
	{
		$settings = self::settings();
		return !empty($settings['cache_enabled']);
	}

	public static function ttl_minutes(): int
	{
		$settings = self::settings();
		return max(5, (int) ($settings['cache_ttl'] ?? 360));
	}

	public static function invalidate_on_publish(): bool
	{
		$settings = self::settings();
		return !empty($settings['invalidate_on_publish']);
	}

	public static function invalidate_on_update(): bool
	{
		$settings = self::settings();
		return !empty($settings['invalidate_on_update']);
	}

	// -------------------------------------------------------------------------
	// Filter visibility helpers (new)
	// -------------------------------------------------------------------------

	/**
	 * Whether the Category filter UI should be shown in the block editor.
	 */
	public static function filter_category_enabled(): bool
	{
		$settings = self::settings();
		// Default ON if the key has never been saved (existing installs).
		return isset($settings['filter_category_enabled'])
			? !empty($settings['filter_category_enabled'])
			: true;
	}

	/**
	 * Whether the Tag filter UI should be shown in the block editor.
	 */
	public static function filter_tag_enabled(): bool
	{
		$settings = self::settings();
		return isset($settings['filter_tag_enabled'])
			? !empty($settings['filter_tag_enabled'])
			: true;
	}

	/**
	 * Whether the Author filter UI should be shown in the block editor.
	 * Defaults to OFF for new and existing installs.
	 */
	public static function filter_author_enabled(): bool
	{
		$settings = self::settings();
		return !empty($settings['filter_author_enabled']);
	}

	// -------------------------------------------------------------------------
	// Cache key / version helpers (unchanged)
	// -------------------------------------------------------------------------

	public static function version(): int
	{
		return max(1, (int) get_option(self::VERSION_KEY, 1));
	}

	public static function bump_version(): int
	{
		$version = self::version() + 1;
		update_option(self::VERSION_KEY, $version);
		return $version;
	}

	public static function clear(): void
	{
		self::bump_version();
	}

	public static function key(array $config): string
	{
		$normalized = wp_json_encode($config);
		return 'wpbm_' . md5((string) $normalized . '|' . self::version());
	}
}
