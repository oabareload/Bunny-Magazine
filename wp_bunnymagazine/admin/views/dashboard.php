<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="bunny-wrap">
<?php \BunnyMagazine\Admin\Admin_Header::render($current_tab, $page_label); ?>
<div class="bunny-page-content">
	<div class="wpbm-admin-grid">
		<div class="bunny-card">
			<h2><?php echo esc_html__('Status', 'wp_bunnymagazine'); ?></h2>
			<p><strong><?php echo esc_html__('Version:', 'wp_bunnymagazine'); ?></strong> <?php echo esc_html(WPMB_VERSION); ?></p>
			<p><strong><?php echo esc_html__('Layouts detected:', 'wp_bunnymagazine'); ?></strong> <?php echo esc_html((string) $layout_count); ?></p>
			<p><strong><?php echo esc_html__('Cache version:', 'wp_bunnymagazine'); ?></strong> <?php echo esc_html((string) $cache_version); ?></p>
		</div>
		<div class="bunny-card">
			<h2><?php echo esc_html__('General state', 'wp_bunnymagazine'); ?></h2>
			<p><?php echo \BunnyMagazine\Core\Cache_Manager::enabled() ? esc_html__('Cache enabled', 'wp_bunnymagazine') : esc_html__('Cache disabled', 'wp_bunnymagazine'); ?></p>
			<p><?php echo esc_html__('Query reuse is handled per request and via transients.', 'wp_bunnymagazine'); ?></p>
		</div>
	</div>
</div>
</div>
