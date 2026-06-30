<?php

declare(strict_types=1);

namespace BunnyMagazine\Core;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Returns the most recently published posts, ordered by date DESC.
 *
 * Default content source; mirrors the original Query_Manager
 * behaviour exactly so existing blocks are unaffected.
 */
class Query_Provider_Latest_Posts implements Query_Provider_Interface
{
	public function fetch(array $config): array
	{
		$args = $this->build_args($config);
		$query = new \WP_Query($args);
		return $query->posts ?: [];
	}

	protected function build_args(array $config): array
	{
		$args = [
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => (int) $config['limit'],
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		];

		$tax_query = $this->build_tax_query($config);
		if (count($tax_query) > 1) {
			$args['tax_query'] = $tax_query;
		}

		if (!empty($config['authors_include'])) {
			$args['author__in'] = $config['authors_include'];
		}

		if (!empty($config['authors_exclude'])) {
			$args['author__not_in'] = $config['authors_exclude'];
		}

		return $args;
	}

	protected function build_tax_query(array $config): array
	{
		$tax_query = ['relation' => 'AND'];

		if (!empty($config['categories_include'])) {
			$tax_query[] = [
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $config['categories_include'],
				'operator' => 'IN',
			];
		}

		if (!empty($config['categories_exclude'])) {
			$tax_query[] = [
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $config['categories_exclude'],
				'operator' => 'NOT IN',
			];
		}

		if (!empty($config['tags_include'])) {
			$tax_query[] = [
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => $config['tags_include'],
				'operator' => 'IN',
			];
		}

		if (!empty($config['tags_exclude'])) {
			$tax_query[] = [
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => $config['tags_exclude'],
				'operator' => 'NOT IN',
			];
		}

		return $tax_query;
	}
}
