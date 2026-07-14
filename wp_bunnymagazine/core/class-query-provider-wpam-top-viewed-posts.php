<?php

declare(strict_types=1);

namespace BunnyMagazine\Core;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Returns the most viewed posts as reported by WP Affiliate Manager.
 *
 * This is a soft (optional) dependency: WP Affiliate Manager may or may
 * not be installed/active. If its API class is unavailable, this provider
 * returns an empty array and never throws, never warns, never breaks the
 * Query_Manager contract.
 *
 * Bunny Magazine does not depend on any WP Affiliate Manager-specific
 * post properties (e.g. wpam_click_count, wpam_thumbnail). It only
 * relies on the standard WP_Post[] return shape, exactly like every
 * other provider.
 */
final class Query_Provider_Wpam_Top_Viewed_Posts implements Query_Provider_Interface
{
	private const VALID_PERIODS = ['day', 'week', 'month', 'total'];

	public function fetch(array $config): array
	{
		if (!self::is_available()) {
			return [];
		}

		$period = isset($config['period']) && in_array($config['period'], self::VALID_PERIODS, true)
			? (string) $config['period']
			: 'week';

		$args = [
			'period'             => $period,
			'limit'              => (int) ($config['limit'] ?? 6),
			'post_type'          => 'post',
			'categories_include' => $config['categories_include'] ?? [],
			'categories_exclude' => $config['categories_exclude'] ?? [],
			'tags_include'       => $config['tags_include']       ?? [],
			'tags_exclude'       => $config['tags_exclude']       ?? [],
			'authors_include'    => $config['authors_include']    ?? [],
			'authors_exclude'    => $config['authors_exclude']    ?? [],
		];

		$posts = \WP_AffiliateManager\API\WPAM_API::get_top_viewed_posts($args);

		return is_array($posts) ? $posts : [];
	}

	/**
	 * Whether WP Affiliate Manager's public API is available.
	 */
	private static function is_available(): bool
	{
		return class_exists('\WP_AffiliateManager\API\WPAM_API')
			&& method_exists('\WP_AffiliateManager\API\WPAM_API', 'get_top_viewed_posts');
	}
}
