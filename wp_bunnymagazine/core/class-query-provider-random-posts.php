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
 * applies, to avoid duplicate queries if the same block renders
 * twice within a single page load.
 */
final class Query_Provider_Random_Posts extends Query_Provider_Latest_Posts
{
	protected function build_args(array $config): array
	{
		$args = parent::build_args($config);
		$args['orderby'] = 'rand';
		unset($args['order']);

		return $args;
	}
}
