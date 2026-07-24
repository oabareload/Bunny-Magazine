<?php

declare(strict_types=1);

namespace BunnyMagazine\Core;

if (!defined('ABSPATH')) {
	exit;
}

function sanitize_id_list($value): array
{
	return Utils::sanitize_id_list($value);
}

function sanitize_author_id_list($value): array
{
	return Utils::sanitize_author_id_list($value);
}

function trim_excerpt_text(\WP_Post $post, int $words = 24): string
{
	$excerpt = get_the_excerpt($post);
	if ($excerpt === '') {
		$excerpt = wp_strip_all_tags($post->post_content);
	}

	return trim(wp_trim_words($excerpt, $words, '...'));
}

function get_featured_image_markup(\WP_Post $post, string $size = 'large', string $class = ''): string
{
	if (!has_post_thumbnail($post)) {
		return '';
	}

	return get_the_post_thumbnail(
		$post,
		$size,
		[
			'class'   => trim('wpbm-post-image ' . $class),
			'loading' => 'lazy',
			'decoding'=> 'async',
		]
	);
}

function get_post_link_button_label(array $attributes): string
{
	return isset($attributes['ctaLabel']) && is_string($attributes['ctaLabel']) && $attributes['ctaLabel'] !== ''
		? $attributes['ctaLabel']
		: __('Read more', 'wp_bunnymagazine');
}

function build_term_label(string $taxonomy, array $ids): string
{
	$names = [];
	foreach ($ids as $id) {
		$term = get_term((int) $id, $taxonomy);
		if ($term instanceof \WP_Term && !is_wp_error($term)) {
			$names[] = $term->name;
		}
	}

	return implode(', ', $names);
}

function get_social_icon_svg(string $profile_type = 'instagram'): string
{
	$clean_type = sanitize_key($profile_type);
	$icon_file  = WPMB_PATH . 'core/icons/' . $clean_type . '.php';

	if (!is_readable($icon_file)) {
		$icon_file = WPMB_PATH . 'core/icons/generic.php';
	}

	if (is_readable($icon_file)) {
		return (string) include $icon_file;
	}

	return '';
}

function wpbm_uninstall_cleanup(): void
{
	delete_option('wpbm_settings');
	delete_option('wpbm_cache_version');
	delete_option('wpbm_layout_manifest');
}

