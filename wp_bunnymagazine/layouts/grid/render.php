<?php
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="wp-block wp-bunnymagazine-layout wp-bunnymagazine-grid" data-wpbm-layout="grid">
	<section class="wpbm-layout wpbm-layout--grid bunny-layout-inner">

		<div class="wpbm-layout__content">

			<?php if (empty($posts)): ?>
				<div class="wpbm-layout__empty">
					<?php echo esc_html__('No posts found for this query.', 'wp_bunnymagazine'); ?>
				</div>
			<?php else: ?>

				<div class="wpbm-grid">

					<?php foreach ($posts as $index => $post): ?>

						<article class="wpbm-grid__card wpbm-card wpbm-grid__card--<?php echo esc_attr($index % 4); ?>">

							<a class="wpbm-grid__media" href="<?php echo esc_url(get_permalink($post)); ?>">

								<?php echo \BunnyMagazine\Core\get_featured_image_markup(
									$post,
									'medium_large',
									'wpbm-grid__image'
								); ?>

								<div class="wpbm-overlay wpbm-grid__overlay">

									<span class="wpbm-layout__eyebrow">
										<?php echo esc_html(get_the_date('', $post)); ?>
									</span>

									<h3 class="wpbm-grid__title">
										<?php echo esc_html(get_the_title($post)); ?>
									</h3>

								</div>

							</a>

						</article>

					<?php endforeach; ?>

				</div>

			<?php endif; ?>

		</div>
	</section>
</div>