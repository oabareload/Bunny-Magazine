<?php

declare(strict_types=1);

namespace BunnyMagazine\Core;

if (!defined('ABSPATH')) {
	exit;
}

final class Layout_Registry
{
	private const MANIFEST_KEY = 'wpbm_layout_manifest';
	private const SCANNED_AT_KEY = 'wpbm_layout_manifest_scanned_at';
	private static ?array $cache = null;
	private static int $cache_scanned_at = 0;

	public static function all(bool $refresh = false): array
	{
		if ($refresh) {
			self::$cache = null;
			self::$cache_scanned_at = 0;
		}

		if (self::$cache !== null) {
			return self::$cache;
		}

		$stored = get_transient(self::MANIFEST_KEY);
		if (is_array($stored)) {
			self::$cache = $stored;
			self::$cache_scanned_at = (int) get_option(self::SCANNED_AT_KEY, 0);
			return self::$cache;
		}

		self::$cache = self::scan();
		self::$cache_scanned_at = time();
		set_transient(self::MANIFEST_KEY, self::$cache, DAY_IN_SECONDS);
		update_option(self::SCANNED_AT_KEY, self::$cache_scanned_at, false);

		return self::$cache;
	}

	public static function refresh_manifest(): array
	{
		self::$cache = self::scan();
		self::$cache_scanned_at = time();
		set_transient(self::MANIFEST_KEY, self::$cache, DAY_IN_SECONDS);
		update_option(self::SCANNED_AT_KEY, self::$cache_scanned_at, false);
		return self::$cache;
	}

	public static function get(string $slug): ?array
	{
		$layouts = self::all();
		return $layouts[$slug] ?? null;
	}

	public static function editor_layouts(): array
	{
		$layouts = [];

		foreach (self::all() as $slug => $layout) {
			$block = is_array($layout['block'] ?? null) ? $layout['block'] : [];
			$query = is_array($block['query'] ?? null) ? $block['query'] : [];
			$controls = is_array($block['controls'] ?? null) ? $block['controls'] : [];
			$assets = is_array($layout['assets'] ?? null) ? $layout['assets'] : [];
			$layouts[$slug] = [
				'slug' => $slug,
				'title' => (string) ($layout['title'] ?? $slug),
				'description' => (string) ($layout['description'] ?? ''),
				'block' => [
					'title' => (string) ($block['title'] ?? $layout['title'] ?? $slug),
					'description' => (string) ($block['description'] ?? ($layout['description'] ?? '')),
					'ctaLabel' => (string) ($block['ctaLabel'] ?? ''),
					'query' => [
						'mode' => (string) ($query['mode'] ?? 'configurable'),
						'limit' => (int) ($query['limit'] ?? 6),
						'minItems' => (int) ($query['minItems'] ?? 1),
						'maxItems' => (int) ($query['maxItems'] ?? max(1, (int) ($query['limit'] ?? 1))),
					],
					'controls' => [
						'limitControl' => !empty($controls['limitControl']),
						'sectionTitle' => !empty($controls['sectionTitle']),
						'ctaLabel' => !empty($controls['ctaLabel']),
						'categorySelector' => !empty($controls['categorySelector']),
					],
				],
				'assets' => [
					'style' => (string) ($assets['style'] ?? ''),
					'editor' => (string) ($assets['editor'] ?? ''),
					'script' => (string) ($assets['script'] ?? ''),
				],
			];
		}

		return $layouts;
	}

	public static function detect_count(): int
	{
		return count(self::all());
	}

	public static function last_scanned_at(): int
	{
		if (self::$cache_scanned_at > 0) {
			return self::$cache_scanned_at;
		}

		self::$cache_scanned_at = max(0, (int) get_option(self::SCANNED_AT_KEY, 0));
		return self::$cache_scanned_at;
	}

	public static function clear_cache(): void
	{
		delete_transient(self::MANIFEST_KEY);
		self::$cache = null;
	}

	private static function scan(): array
	{
		$base_dir = trailingslashit(WPMB_PATH . 'layouts');
		$layouts = [];

		foreach (glob($base_dir . '*', GLOB_ONLYDIR) ?: [] as $dir) {
			$layout_file = $dir . DIRECTORY_SEPARATOR . 'layout.json';
			$render_file = $dir . DIRECTORY_SEPARATOR . 'render.php';

			if (!is_readable($layout_file) || !is_readable($render_file)) {
				continue;
			}

			$config = json_decode((string) file_get_contents($layout_file), true);
			if (!is_array($config)) {
				continue;
			}

			$slug = sanitize_key(basename($dir));
			$layouts[$slug] = wp_parse_args(
				$config,
				[
					'slug' => $slug,
					'title' => ucfirst(str_replace('-', ' ', $slug)),
					'description' => '',
					'path' => $dir,
					'default_query' => [],
					'block' => [],
					'assets' => [],
					'schema' => [],
				]
			);
			$layouts[$slug]['slug'] = $slug;
			$layouts[$slug]['path'] = $dir;
		}

		ksort($layouts);

		return $layouts;
	}
}
