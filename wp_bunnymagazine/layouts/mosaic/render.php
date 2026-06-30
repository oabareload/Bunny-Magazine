<?php
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="wp-block wp-bunnymagazine-layout wp-bunnymagazine-mosaic" data-wpbm-layout="mosaic">
	<section class="wpbm-layout wpbm-layout--mosaic bunny-layout-inner">
		<div class="wpbm-layout__content">
			<?php if (empty($posts)): ?>
				<div class="wpbm-layout__empty"><?php echo esc_html__('No posts found for this query.', 'wp_bunnymagazine'); ?></div>
			<?php else: ?>
				<div class="wpbm-mosaic">
					<?php foreach ($posts as $index => $post): ?>
						<?php if ($index === 0): ?>
							<article class="wpbm-mosaic__lead wpbm-card">
								<a class="wpbm-mosaic__lead-media" href="<?php echo esc_url(get_permalink($post)); ?>" aria-label="<?php echo esc_attr(get_the_title($post)); ?>">
									<?php echo \BunnyMagazine\Core\get_featured_image_markup($post, 'large', 'wpbm-mosaic__lead-image'); ?>
									<div class="wpbm-overlay wpbm-mosaic__lead-overlay">
										<span class="wpbm-layout__eyebrow"><?php echo esc_html__('Main feature', 'wp_bunnymagazine'); ?></span>
										<h3 class="wpbm-layout__title"><?php echo esc_html(get_the_title($post)); ?></h3>
										<div class="wpbm-layout__excerpt"><?php echo esc_html(\BunnyMagazine\Core\trim_excerpt_text($post, 26)); ?></div>
									</div>
								</a>
							</article>
						<?php else: ?>
							<article class="wpbm-mosaic__secondary wpbm-card">
								<a class="wpbm-mosaic__secondary-media" href="<?php echo esc_url(get_permalink($post)); ?>" aria-label="<?php echo esc_attr(get_the_title($post)); ?>">
									<?php echo \BunnyMagazine\Core\get_featured_image_markup($post, 'medium', 'wpbm-mosaic__secondary-image'); ?>
									<div class="wpbm-overlay wpbm-mosaic__secondary-overlay">
										<span class="wpbm-layout__eyebrow"><?php echo esc_html(get_the_date('', $post)); ?></span>
										<h4 class="wpbm-mosaic__secondary-title"><?php echo esc_html(get_the_title($post)); ?></h4>
									</div>
								</a>
								<div class="wpbm-mosaic__secondary-content">
									<div class="wpbm-layout__excerpt"><?php echo esc_html(\BunnyMagazine\Core\trim_excerpt_text($post, 16)); ?></div>
								</div>
							</article>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
</div>
