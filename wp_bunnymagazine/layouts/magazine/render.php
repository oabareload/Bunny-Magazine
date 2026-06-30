<?php
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="wp-block wp-bunnymagazine-layout wp-bunnymagazine-magazine" data-wpbm-layout="magazine">
	<section class="wpbm-layout wpbm-layout--magazine bunny-layout-inner">
		<div class="wpbm-layout__content">
			<?php if (empty($posts)): ?>
				<div class="wpbm-layout__empty"><?php echo esc_html__('No posts found for this query.', 'wp_bunnymagazine'); ?></div>
			<?php else: ?>
				<div class="wpbm-magazine">
					<?php foreach ($posts as $index => $post): ?>
						<?php $is_even = $index % 2 === 1; ?>
						<article class="wpbm-magazine__story wpbm-card <?php echo $is_even ? 'wpbm-magazine__story--reverse' : ''; ?>">
							<a class="wpbm-magazine__media" href="<?php echo esc_url(get_permalink($post)); ?>" aria-label="<?php echo esc_attr(get_the_title($post)); ?>">
								<?php echo \BunnyMagazine\Core\get_featured_image_markup($post, 'large', 'wpbm-magazine__image'); ?>
								<div class="wpbm-overlay wpbm-magazine__overlay">
									<span class="wpbm-layout__eyebrow"><?php echo esc_html(get_the_date('', $post)); ?></span>
									<h3 class="wpbm-layout__title"><?php echo esc_html(get_the_title($post)); ?></h3>
									<div class="wpbm-layout__excerpt"><?php echo esc_html(\BunnyMagazine\Core\trim_excerpt_text($post, 28)); ?></div>
								</div>
							</a>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
</div>
