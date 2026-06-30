<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="bunny-wrap">
<?php \BunnyMagazine\Admin\Admin_Header::render($current_tab, $page_label); ?>
<div class="bunny-page-content">
	<?php if (isset($_GET['wpbm_notice']) && $_GET['wpbm_notice'] === 'rescanned'): ?>
		<div class="notice notice-success"><p><?php echo esc_html__('Layout cache refreshed.', 'wp_bunnymagazine'); ?></p></div>
	<?php endif; ?>
	<p>
		<a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wpbm_rescan_layouts'), 'wpbm_rescan_layouts')); ?>">
			<?php echo esc_html__('Rescan layouts', 'wp_bunnymagazine'); ?>
		</a>
	</p>
	<p>
		<strong><?php echo esc_html__('Last scan:', 'wp_bunnymagazine'); ?></strong>
		<?php echo $last_scanned_at ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_scanned_at)) : esc_html__('Never', 'wp_bunnymagazine'); ?>
	</p>
	<div class="wpbm-admin-grid">
		<?php foreach ($layouts as $layout): ?>
			<div class="bunny-card">
				<h2><?php echo esc_html($layout['title'] ?? $layout['slug']); ?></h2>
				<p><?php echo esc_html($layout['description'] ?? ''); ?></p>
				<p><strong><?php echo esc_html__('Slug:', 'wp_bunnymagazine'); ?></strong> <?php echo esc_html($layout['slug']); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</div>
</div>
