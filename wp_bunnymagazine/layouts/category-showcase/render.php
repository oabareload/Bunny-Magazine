<?php
if (!defined('ABSPATH')) {
	exit;
}

$category_id = (int) ($attributes['categoryId'] ?? 0);

/**
 * Resolver categorías:
 * - Si hay categoría seleccionada → solo esa
 * - Si no → todas las categorías (con posts)
 */
if ($category_id > 0) {
	$categories = [get_category($category_id)];
} else {
	$categories = get_categories([
		'hide_empty' => true,
	]);
}

/**
 * URL de archivo solo si hay categoría única seleccionada
 */
$archive_url = $category_id > 0 ? get_category_link($category_id) : '';
?>

<div class="wp-block wp-bunnymagazine-layout wp-bunnymagazine-category-showcase" data-wpbm-layout="category-showcase">
	<section class="wpbm-layout wpbm-layout--category-showcase bunny-layout-inner">

		<div class="wpbm-layout__content">

			<?php if ($archive_url): ?>
				<a class="wpbm-layout__button" href="<?php echo esc_url($archive_url); ?>">
					<?php echo esc_html__('View more', 'wp_bunnymagazine'); ?>
				</a>
			<?php endif; ?>

			<div class="wpbm-showcase__grid">

				<?php foreach ($categories as $cat): ?>

					<?php
					if (!$cat instanceof WP_Term) {
						continue;
					}

					$query = new WP_Query([
						'post_type'      => 'post',
						'posts_per_page' => 1,
						'post_status'    => 'publish',
						'no_found_rows'   => true,
						'tax_query'      => [
							[
								'taxonomy' => 'category',
								'field'    => 'term_id',
								'terms'    => $cat->term_id,
							],
						],
					]);

					if (!$query->have_posts()) {
						wp_reset_postdata();
						continue;
					}

					$query->the_post();
					$post = get_post();
					?>

					<article class="wpbm-showcase__card wpbm-card">
						<a class="wpbm-showcase__media" href="<?php echo esc_url(get_permalink($post)); ?>">

							<?php echo \BunnyMagazine\Core\get_featured_image_markup($post, 'large', 'wpbm-showcase__image'); ?>

							<div class="wpbm-overlay wpbm-showcase__overlay">

								<span class="wpbm-showcase__category-label">
									<?php echo esc_html($cat->name); ?>
								</span>

								<span class="wpbm-layout__eyebrow">
									<?php echo esc_html(get_the_date('', $post)); ?>
								</span>

								<h3 class="wpbm-layout__title">
									<?php echo esc_html(get_the_title($post)); ?>
								</h3>

								<div class="wpbm-layout__excerpt">
									<?php echo esc_html(\BunnyMagazine\Core\trim_excerpt_text($post, 25)); ?>
								</div>

							</div>
						</a>
					</article>

					<?php wp_reset_postdata(); ?>

				<?php endforeach; ?>

			</div>

		</div>
	</section>
</div>