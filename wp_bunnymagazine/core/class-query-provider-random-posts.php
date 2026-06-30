<?php

declare(strict_types=1);

namespace BunnyMagazine\Core;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Returns a random selection of published posts.
 *
 * Cache note: Query_Manager never persists this provider's results
 * to the transient cache. Randomness is regenerated on every
 * uncached PHP request by design; only the in-memory request cache
 * applies.
 */
final class Query_Provider_Random_Posts extends Query_Provider_Latest_Posts
{
	protected function build_args(array $config): array
	{
		$args = parent::build_args($config);

		// Random order
		$args['orderby'] = 'rand';

		// IMPORTANTE:
		// WP_Query ignora ORDER cuando es rand, pero lo dejamos limpio
		unset($args['order']);

		/**
		 * HARD FIX IMPORTANTE:
		 * Evita edge cases donde WP intenta optimizar queries rand
		 * con no_found_rows mal aplicado en algunos servidores.
		 */
		$args['no_found_rows'] = true;

		return $args;
	}
}