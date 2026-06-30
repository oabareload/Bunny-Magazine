<?php

declare(strict_types=1);

namespace BunnyMagazine\Core;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Contract for all content source providers.
 *
 * Every provider receives a normalized config array and returns
 * an array of WP_Post objects. Providers must not know anything
 * about layouts, blocks, or the editor.
 *
 * Normalized config shape:
 * [
 *   'limit'               => int,
 *   'categories_include'  => int[],
 *   'categories_exclude'  => int[],
 *   'tags_include'        => int[],
 *   'tags_exclude'        => int[],
 *   'authors_include'     => int[],
 *   'authors_exclude'     => int[],
 * ]
 *
 * Third-party providers (WooCommerce, CPTs, affiliates, etc.)
 * implement this interface and register via the
 * 'wpbm_content_sources' filter in Query_Manager.
 *
 * File lives flat in core/ (no sub-namespace) so the existing
 * single-level autoloader (BunnyMagazine\{Namespace}\Class_Name
 * → {namespace}/class-{class-name}.php) requires no changes.
 */
interface Query_Provider_Interface
{
	/**
	 * Fetch posts for the given normalized query config.
	 *
	 * @param array $config Normalized query configuration.
	 * @return \WP_Post[]
	 */
	public function fetch(array $config): array;
}
