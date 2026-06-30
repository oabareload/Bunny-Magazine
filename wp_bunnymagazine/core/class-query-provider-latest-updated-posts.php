<?php

declare(strict_types=1);

namespace BunnyMagazine\Core;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Returns posts ordered by their last-modified date DESC.
 *
 * Useful for "recently updated" editorial sections where
 * freshness of content matters more than publish date.
 */
final class Query_Provider_Latest_Updated_Posts extends Query_Provider_Latest_Posts
{
	protected function build_args(array $config): array
	{
		$args = parent::build_args($config);
		$args['orderby'] = 'modified';
		$args['order']   = 'DESC';

		return $args;
	}
}
