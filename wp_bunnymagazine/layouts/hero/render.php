<?php
if (!defined('ABSPATH')) {
	exit;
}

$post = $posts[0] ?? null;
?>
<div class="wp-block wp-bunnymagazine-layout wp-bunnymagazine-hero" data-wpbm-layout="hero">
	<section class="wpbm-layout wpbm-layout--hero bunny-layout-inner">
		<div class="wpbm-layout__content">
			<?php if (!$post instanceof WP_Post): ?>
				<div class="wpbm-layout__empty"><?php echo esc_html__('No posts found for this query.', 'wp_bunnymagazine'); ?></div>
			<?php else: ?>
				<article class="wpbm-hero wpbm-card">
					<a class="wpbm-hero__media" href="<?php echo esc_url(get_permalink($post)); ?>" aria-label="<?php echo esc_attr(get_the_title($post)); ?>">
						<?php echo \BunnyMagazine\Core\get_featured_image_markup($post, 'large', 'wpbm-hero__image'); ?>
						<div class="wpbm-overlay wpbm-hero__scrim"></div>
						<div class="wpbm-hero__content">
							<span class="wpbm-layout__eyebrow"><?php echo esc_html__('Featured story', 'wp_bunnymagazine'); ?></span>
							<h2 class="wpbm-layout__title"><?php echo esc_html(get_the_title($post)); ?></h2>
							<div class="wpbm-layout__excerpt"><?php echo esc_html(\BunnyMagazine\Core\trim_excerpt_text($post, 34)); ?></div>
							<span class="wpbm-layout__button"><?php echo esc_html(\BunnyMagazine\Core\get_post_link_button_label($attributes)); ?></span>
						</div>
					</a>
				</article>
			<?php endif; ?>
		</div>
	</section>
</div>
