<?php
if (!defined('ABSPATH')) {
	exit;
}

$profile_type          = sanitize_key($attributes['profileType'] ?? 'instagram');
$name                  = isset($attributes['name']) ? (string) $attributes['name'] : 'BunnyChase';
$show_name             = isset($attributes['showName']) ? (bool) $attributes['showName'] : true;
$username              = isset($attributes['username']) ? (string) $attributes['username'] : '@bunnychase.net';
$show_username         = isset($attributes['showUsername']) ? (bool) $attributes['showUsername'] : true;
$description           = isset($attributes['description']) ? (string) $attributes['description'] : '';
$show_description      = isset($attributes['showDescription']) ? (bool) $attributes['showDescription'] : true;
$style_variant         = sanitize_key($attributes['styleVariant'] ?? 'brand');
$alignment             = sanitize_key($attributes['alignment'] ?? 'left');
$placeholder_type      = sanitize_key($attributes['placeholderType'] ?? 'none');
$placeholder_grid_size = sanitize_key($attributes['placeholderGridSize'] ?? '2x3');
$placeholder_position  = sanitize_key($attributes['placeholderPosition'] ?? 'after_description');
$show_button           = isset($attributes['showButton']) ? (bool) $attributes['showButton'] : true;
$button_text           = isset($attributes['buttonText']) ? (string) $attributes['buttonText'] : __('Ver perfil', 'wp_bunnymagazine');
$raw_url               = isset($attributes['url']) ? trim((string) $attributes['url']) : '';
$url                   = ($raw_url !== '' && $raw_url !== '#') ? esc_url($raw_url) : '';
$open_in_new_tab       = isset($attributes['openInNewTab']) ? (bool) $attributes['openInNewTab'] : true;
$placeholder_image     = isset($attributes['placeholderImage']) ? esc_url(trim((string) $attributes['placeholderImage'])) : '';

$grid_items_map = [
	'2x2' => 4,
	'2x3' => 6,
	'3x3' => 9,
	'3x4' => 12,
	'3x5' => 15,
];
$grid_count = $grid_items_map[$placeholder_grid_size] ?? 6;

$classes = [
	'wp-block',
	'wp-bunnymagazine-layout',
	'wp-bunnymagazine-social-profile',
	'wpbm-social-profile',
	'wpbm-social-profile--' . $profile_type,
	'wpbm-social-profile--style-' . $style_variant,
	'wpbm-social-profile--align-' . $alignment,
];
?>
<div class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-wpbm-layout="social-profile">
	<div class="wpbm-social-profile__inner">

		<?php /* Cabecera: Icono + Identidad */ ?>
		<div class="wpbm-social-profile__header">
			<div class="wpbm-social-profile__icon" aria-hidden="true">
				<?php echo \BunnyMagazine\Core\get_social_icon_svg($profile_type); ?>
			</div>

			<?php if (($show_name && $name !== '') || ($show_username && $username !== '')): ?>
				<div class="wpbm-social-profile__identity">
					<?php if ($show_name && $name !== ''): ?>
						<h3 class="wpbm-social-profile__name"><?php echo esc_html($name); ?></h3>
					<?php endif; ?>

					<?php if ($show_username && $username !== ''): ?>
						<span class="wpbm-social-profile__username"><?php echo esc_html($username); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php
		// Render de la descripción y del grid placeholder según la posición configurada
		$render_description = static function () use ($show_description, $description): void {
			if ($show_description && trim($description) !== ''):
				?>
				<div class="wpbm-social-profile__description">
					<?php echo wp_kses_post(nl2br(esc_html($description))); ?>
				</div>
				<?php
			endif;
		};

		$render_placeholder = static function () use ($placeholder_type, $placeholder_grid_size, $grid_count, $placeholder_image): void {
			if ($placeholder_type === 'grid'):
				?>
				<div class="wpbm-social-profile__grid wpbm-social-profile__grid--<?php echo esc_attr($placeholder_grid_size); ?>" aria-hidden="true">
					<?php for ($i = 0; $i < $grid_count; $i++): ?>
						<div class="wpbm-social-profile__grid-item"></div>
					<?php endfor; ?>
				</div>
				<?php
			elseif ($placeholder_type === 'image' && $placeholder_image !== ''):
				?>
				<div class="wpbm-social-profile__image" aria-hidden="true">
					<img
						src="<?php echo esc_url($placeholder_image); ?>"
						alt=""
						loading="lazy"
						decoding="async"
					/>
				</div>
				<?php
			endif;
		};

		if ($placeholder_position === 'before_description') {
			$render_placeholder();
			$render_description();
		} else {
			$render_description();
			$render_placeholder();
		}
		?>

		<?php /* Pie de la tarjeta: Botón alineado al fondo */ ?>
		<?php if ($show_button && $url !== '' && $button_text !== ''): ?>
			<div class="wpbm-social-profile__footer">
				<a
					class="wpbm-social-profile__button"
					href="<?php echo $url; ?>"
					<?php if ($open_in_new_tab): ?>
						target="_blank"
						rel="noopener noreferrer"
					<?php endif; ?>
				>
					<span><?php echo esc_html($button_text); ?></span>
				</a>
			</div>
		<?php endif; ?>

	</div>
</div>
