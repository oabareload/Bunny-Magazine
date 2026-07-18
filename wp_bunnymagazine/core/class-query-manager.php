<?php

declare(strict_types=1);

namespace BunnyMagazine\Core;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Central query facade for BunnyMagazine.
 *
 * Responsibilities:
 *  1. Maintain a registry of content source providers (built-in + filtered).
 *  2. Resolve the correct provider from the canonical 'source' key,
 *     with a safe fallback to latest_posts for invalid or legacy values.
 *  3. Normalize the canonical Query Object received from Block_Registry,
 *     enforcing global filter settings server-side (not just hiding UI).
 *  4. Manage request-level and transient cache (transient skipped for
 *     random_posts — see cache strategy note below).
 *  5. Return WP_Post[] to callers — layouts never touch WP_Query directly.
 *
 * This class remains the ONLY public entry point used by Block_Registry
 * and any other plugin code.
 *
 * Single internal contract:
 *  Query_Manager assumes every config it receives is already the
 *  canonical Query Object produced by Block_Registry::resolve_query_config().
 *  That shape is flat and snake_case:
 *
 *    source, limit, period,
 *    categories_include, categories_exclude,
 *    tags_include, tags_exclude,
 *    authors_include, authors_exclude
 *
 *  Block_Registry is the only caller allowed to read camelCase block
 *  attributes or fold legacy attributes (like 'categoryId') into this
 *  shape. Query_Manager never reads camelCase keys, never reads a nested
 *  'query.*' shape, and never reinterprets the input — it only validates
 *  and sanitizes the canonical fields it expects.
 *
 * Extensibility:
 *  Third-party modules register new providers via the
 *  'wpbm_content_sources' filter. The filter receives the full
 *  provider map (key => Query_Provider_Interface instance) and must
 *  return it with any additions:
 *
 *  add_filter('wpbm_content_sources', function (array $providers): array {
 *      $providers['woocommerce_products'] = new My_WC_Provider();
 *      return $providers;
 *  });
 *
 *  To also surface a new source in the block editor's Content Source
 *  selector, hook 'wpbm_editor_content_sources':
 *
 *  add_filter('wpbm_editor_content_sources', function (array $sources): array {
 *      $sources[] = ['value' => 'woocommerce_products', 'label' => 'WooCommerce Products'];
 *      return $sources;
 *  });
 *
 * Fallback safety:
 *  Any unrecognised or missing 'source' (legacy blocks saved before
 *  this system existed, tampered attributes, third-party filters that
 *  return malformed data) always resolves to latest_posts. The system
 *  never throws or returns an empty result purely due to an invalid
 *  source key.
 *
 * Filter enforcement:
 *  The "Enable Category/Tag/Author Filters" settings in Query Settings are
 *  not just UI toggles. normalize() strips the corresponding filter values
 *  server-side whenever a filter type is globally disabled, regardless of
 *  what a block's saved attributes contain. This closes the gap between
 *  "hidden in the editor" and "actually applied in the query" — a stale
 *  block attribute or a manually crafted request cannot bypass a disabled
 *  filter.
 *
 * Cache strategy for random_posts:
 *  Random results are intentionally NOT written to the transient cache.
 *  Caching a "random" provider would freeze the same set of posts for
 *  the entire TTL window (potentially hours), which defeats the purpose
 *  of randomness. Only the in-memory, per-request cache applies, so a
 *  block rendered twice in the same page load doesn't run WP_Query twice,
 *  but each new HTTP request gets a fresh random set.
 */
final class Query_Manager
{
	/** @var array<string, \WP_Post[]> Per-request in-memory cache. */
	private static array $request_cache = [];

	public const SOURCE_LATEST_POSTS         = 'latest_posts';
	public const SOURCE_LATEST_UPDATED_POSTS = 'latest_updated_posts';
	public const SOURCE_RANDOM_POSTS         = 'random_posts';
	public const SOURCE_WPAM_TOP_POSTS         = 'wpam_top_posts';
	public const SOURCE_WPAM_TOP_VIEWED_POSTS  = 'wpam_top_viewed_posts';
	public const SOURCE_WPAM_TOP_SCORED_POSTS  = 'wpam_top_scored_posts';

	/** @var string[] Valid values for the 'period' config key (wpam_top_posts only). */
	private const VALID_PERIODS = ['day', 'week', 'month', 'total'];

	/**
	 * Fetch posts for the given canonical Query Object.
	 *
	 * @param array $config Canonical Query Object from Block_Registry::resolve_query_config().
	 * @return \WP_Post[]
	 */
	public function fetch(array $config): array
	{
		$normalized = $this->normalize($config);
		$source     = (string) $normalized['source'];
		$cache_key  = Cache_Manager::key($normalized);

		// 1. Request-level cache always applies (cheap, avoids duplicate
		//    WP_Query calls within the same page load regardless of source).
		if (isset(self::$request_cache[$cache_key])) {
			return self::$request_cache[$cache_key];
		}

		$is_random = ($source === self::SOURCE_RANDOM_POSTS);

		// 2. Transient cache — skipped entirely for random_posts.
		if (!$is_random && Cache_Manager::enabled()) {
			$cached = get_transient($cache_key);
			if (is_array($cached)) {
				self::$request_cache[$cache_key] = $cached;
				return $cached;
			}
		}

		// 3. Resolve provider and execute query.
		$provider = $this->resolve_provider($source);
		$posts    = $provider->fetch($normalized);

		// 4. Persist to in-memory cache always; transient only when allowed.
		self::$request_cache[$cache_key] = $posts;

		if (!$is_random && Cache_Manager::enabled()) {
			set_transient($cache_key, $posts, MINUTE_IN_SECONDS * Cache_Manager::ttl_minutes());
		}

		return $posts;
	}

	/**
	 * Normalize the canonical Query Object (produced exclusively by
	 * Block_Registry::resolve_query_config()) into its final, sanitized
	 * shape:
	 * [
	 *   'source'              => string,
	 *   'limit'               => int,
	 *   'categories_include'  => int[],
	 *   'categories_exclude'  => int[],
	 *   'tags_include'        => int[],
	 *   'tags_exclude'        => int[],
	 *   'authors_include'     => int[],
	 *   'authors_exclude'     => int[],
	 *   'period'              => string (only present for wpam_top_posts),
	 * ]
	 *
	 * This method does NOT accept alternate formats (no camelCase keys,
	 * no nested 'query.*' shape, no legacy 'categoryId'/'category_id').
	 * It trusts that Block_Registry has already produced the canonical
	 * shape; it only validates, sanitizes, and enforces global filter
	 * settings.
	 *
	 * Kept public so external code can build normalized configs directly,
	 * as long as it already supplies the canonical shape.
	 *
	 * @param array $config Canonical Query Object.
	 * @return array Normalized config.
	 */
	public function normalize(array $config): array
	{
		$normalized = [
			'source'             => $this->resolve_source_key($config),
			'limit'              => max(1, (int) ($config['limit'] ?? 6)),
			'categories_include' => Utils::sanitize_id_list($config['categories_include'] ?? []),
			'categories_exclude' => Utils::sanitize_id_list($config['categories_exclude'] ?? []),
			'tags_include'       => Utils::sanitize_id_list($config['tags_include'] ?? []),
			'tags_exclude'       => Utils::sanitize_id_list($config['tags_exclude'] ?? []),
			'authors_include'    => Utils::sanitize_author_id_list($config['authors_include'] ?? []),
			'authors_exclude'    => Utils::sanitize_author_id_list($config['authors_exclude'] ?? []),
		];

		// Enforce global filter settings server-side. Hiding a filter in the
		// editor UI is not, on its own, a guarantee it can't reach the query —
		// a block saved before the setting was disabled, a manually crafted
		// REST request, or stale attributes could still carry filter data.
		// Strip any filter type that is globally disabled so it can never
		// reach a provider, regardless of what the caller sent.
		if (!Cache_Manager::filter_category_enabled()) {
			$normalized['categories_include'] = [];
			$normalized['categories_exclude'] = [];
		}

		if (!Cache_Manager::filter_tag_enabled()) {
			$normalized['tags_include'] = [];
			$normalized['tags_exclude'] = [];
		}

		if (!Cache_Manager::filter_author_enabled()) {
			$normalized['authors_include'] = [];
			$normalized['authors_exclude'] = [];
		}

		// 'period' is only meaningful for wpam_top_posts. Keeping it out of the
		// normalized shape for every other source avoids changing their cache
		// keys (and therefore avoids invalidating existing cached results).
		if (
			$normalized['source'] === self::SOURCE_WPAM_TOP_POSTS ||
			$normalized['source'] === self::SOURCE_WPAM_TOP_VIEWED_POSTS ||
			$normalized['source'] === self::SOURCE_WPAM_TOP_SCORED_POSTS
		) {
			$normalized['period'] = $this->resolve_period($config);
		}

		ksort($normalized);

		return $normalized;
	}

	/**
	 * Extract and validate the 'period' value for wpam_top_posts.
	 * Reads only the canonical top-level 'period' key. Any invalid or
	 * missing value falls back to 'week'.
	 */
	private function resolve_period(array $config): string
	{
		$raw = $config['period'] ?? 'week';
		$raw = is_string($raw) ? trim($raw) : '';

		return in_array($raw, self::VALID_PERIODS, true) ? $raw : 'week';
	}

	// -------------------------------------------------------------------------
	// Provider registry
	// -------------------------------------------------------------------------

	/**
	 * Build the provider map and allow third-party extensions via filter.
	 *
	 * @return array<string, Query_Provider_Interface>
	 */
	private function providers(): array
	{
		$providers = [
			self::SOURCE_LATEST_POSTS         => new Query_Provider_Latest_Posts(),
			self::SOURCE_LATEST_UPDATED_POSTS => new Query_Provider_Latest_Updated_Posts(),
			self::SOURCE_RANDOM_POSTS         => new Query_Provider_Random_Posts(),
			self::SOURCE_WPAM_TOP_POSTS        => new Query_Provider_Wpam_Top_Posts(),
			self::SOURCE_WPAM_TOP_VIEWED_POSTS => new Query_Provider_Wpam_Top_Viewed_Posts(),
			self::SOURCE_WPAM_TOP_SCORED_POSTS => new Query_Provider_Wpam_Top_Scored_Posts(),
		];

		/**
		 * Filter: wpbm_content_sources
		 *
		 * @param array<string, Query_Provider_Interface> $providers Registered providers.
		 * @return array<string, Query_Provider_Interface>
		 */
		$filtered = apply_filters('wpbm_content_sources', $providers);

		return is_array($filtered) ? $filtered : $providers;
	}

	/**
	 * Return the provider for the given source key.
	 * Falls back to Latest_Posts if the key is unrecognised.
	 */
	private function resolve_provider(string $source): Query_Provider_Interface
	{
		$map = $this->providers();

		if (isset($map[$source]) && $map[$source] instanceof Query_Provider_Interface) {
			return $map[$source];
		}

		return new Query_Provider_Latest_Posts();
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Extract and validate the content source key from the canonical
	 * Query Object's 'source' field.
	 *
	 * Safe fallback: any value that isn't a string, is empty, or doesn't
	 * match a registered provider key resolves to SOURCE_LATEST_POSTS.
	 * This method never returns an unregistered key.
	 */
	private function resolve_source_key(array $config): string
	{
		$raw = $config['source'] ?? self::SOURCE_LATEST_POSTS;
		$key = is_string($raw) ? trim($raw) : '';

		if ($key === '') {
			return self::SOURCE_LATEST_POSTS;
		}

		$valid_keys = array_keys($this->providers());

		return in_array($key, $valid_keys, true) ? $key : self::SOURCE_LATEST_POSTS;
	}
}
