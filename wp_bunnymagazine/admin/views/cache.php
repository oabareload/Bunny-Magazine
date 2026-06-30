<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="bunny-wrap">
<?php \BunnyMagazine\Admin\Admin_Header::render($current_tab, $page_label); ?>
<div class="bunny-page-content">
	<?php if (isset($_GET['wpbm_notice']) && $_GET['wpbm_notice'] === 'cleared'): ?>
		<div class="notice notice-success"><p><?php echo esc_html__('Cache cleared.', 'wp_bunnymagazine'); ?></p></div>
	<?php endif; ?>
	<form method="post" action="options.php" class="bunny-card">
		<?php settings_fields('wpbm_settings_group'); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html__('Enable cache', 'wp_bunnymagazine'); ?></th>
				<td><label><input type="checkbox" name="wpbm_settings[cache_enabled]" value="1" <?php checked(!empty($settings['cache_enabled'])); ?>> <?php echo esc_html__('Store query results in transients', 'wp_bunnymagazine'); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__('TTL minutes', 'wp_bunnymagazine'); ?></th>
				<td><input type="number" min="5" step="5" name="wpbm_settings[cache_ttl]" value="<?php echo esc_attr((string) ($settings['cache_ttl'] ?? 360)); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__('Invalidate on publish', 'wp_bunnymagazine'); ?></th>
				<td><label><input type="checkbox" name="wpbm_settings[invalidate_on_publish]" value="1" <?php checked(!empty($settings['invalidate_on_publish'])); ?>> <?php echo esc_html__('Bump cache version when a post is published', 'wp_bunnymagazine'); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__('Invalidate on update', 'wp_bunnymagazine'); ?></th>
				<td><label><input type="checkbox" name="wpbm_settings[invalidate_on_update]" value="1" <?php checked(!empty($settings['invalidate_on_update'])); ?>> <?php echo esc_html__('Bump cache version when a post is updated', 'wp_bunnymagazine'); ?></label></td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
	<p class="description">
		<?php echo esc_html__('Looking for filter visibility settings (category, tag, author)?', 'wp_bunnymagazine'); ?>
		<a href="<?php echo esc_url(admin_url('admin.php?page=wpbm-query')); ?>"><?php echo esc_html__('Go to Query Settings.', 'wp_bunnymagazine'); ?></a>
	</p>
	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bunny-card">
		<input type="hidden" name="action" value="wpbm_clear_cache">
		<?php wp_nonce_field('wpbm_clear_cache'); ?>
		<?php submit_button(__('Clear cache now', 'wp_bunnymagazine'), 'secondary', 'submit', false); ?>
	</form>
</div>
</div>
