<?php
if (!defined('ABSPATH')) {
	exit;
}

$section_title = !empty($attributes['sectionTitle']) ? (string) $attributes['sectionTitle'] : __('Latest stories', 'wp_bunnymagazine');
?>
<div class="wp-block wp-bunnymagazine-layout wp-bunnymagazine-carousel" data-wpbm-layout="carousel">
	<section class="wpbm-layout wpbm-layout--carousel bunny-layout-inner" data-wpbm-carousel>

		<div class="wpbm-layout__content">

			<div class="wpbm-carousel__header">
				<div class="wpbm-carousel__controls">
					<button type="button" class="wpbm-carousel__button wpbm-carousel__button--prev"
						data-wpbm-carousel-prev aria-label="Previous">
						‹
					</button>

					<button type="button" class="wpbm-carousel__button wpbm-carousel__button--next"
						data-wpbm-carousel-next aria-label="Next">
						›
					</button>
				</div>
			</div>

			<!-- 🔥 ESTE ES EL FIX CLAVE -->
			<div class="wpbm-carousel__viewport">
				<div class="wpbm-carousel__track" data-wpbm-carousel-track>

					<?php foreach ($posts as $index => $post): ?>
						<article class="wpbm-carousel__card">
							<a class="wpbm-carousel__media" href="<?php echo esc_url(get_permalink($post)); ?>">

								<?php echo \BunnyMagazine\Core\get_featured_image_markup(
									$post,
									'medium_large',
									'wpbm-carousel__image'
								); ?>

								<div class="wpbm-overlay wpbm-carousel__overlay">
									<span class="wpbm-layout__eyebrow">
										<?php echo esc_html(get_the_date('', $post)); ?>
									</span>

									<h3 class="wpbm-carousel__title">
										<?php echo esc_html(get_the_title($post)); ?>
									</h3>
								</div>

							</a>
						</article>
					<?php endforeach; ?>

				</div>
			</div>

		</div>
	</section>
</div>
