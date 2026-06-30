<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="bunny-wrap">
<?php \BunnyMagazine\Admin\Admin_Header::render($current_tab, $page_label); ?>
<div class="bunny-page-content">

	<?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true'): ?>
		<div class="notice notice-success"><p>
			<?php echo esc_html__('Query settings saved. If filter visibility changed, the query cache was cleared automatically.', 'wp_bunnymagazine'); ?>
		</p></div>
	<?php endif; ?>

	<form method="post" action="options.php" class="bunny-card">
		<?php settings_fields('wpbm_settings_group'); ?>
		<!-- Identifies which field group this form owns, so sanitize_settings()
		     can tell "checkbox unchecked" (present marker, field absent) apart
		     from "field not part of this form" (marker absent entirely). -->
		<input type="hidden" name="wpbm_settings_form" value="query">

		<h2><?php echo esc_html__('Editor Filter Controls', 'wp_bunnymagazine'); ?></h2>
		<p class="description">
			<?php echo esc_html__('Choose which filter controls appear in the block editor\'s Filters panel. Disabling a filter here also prevents it from being applied to queries, even if a block was previously configured with that filter.', 'wp_bunnymagazine'); ?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html__('Category filters', 'wp_bunnymagazine'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpbm_settings[filter_category_enabled]" value="1" <?php checked(!empty($settings['filter_category_enabled'])); ?>>
						<?php echo esc_html__('Show and apply category include / exclude filters', 'wp_bunnymagazine'); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__('Tag filters', 'wp_bunnymagazine'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpbm_settings[filter_tag_enabled]" value="1" <?php checked(!empty($settings['filter_tag_enabled'])); ?>>
						<?php echo esc_html__('Show and apply tag include / exclude filters', 'wp_bunnymagazine'); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__('Author filters', 'wp_bunnymagazine'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpbm_settings[filter_author_enabled]" value="1" <?php checked(!empty($settings['filter_author_enabled'])); ?>>
						<?php echo esc_html__('Show and apply author include / exclude filters', 'wp_bunnymagazine'); ?>
					</label>
					<p class="description"><?php echo esc_html__('Disabled by default. Enable only if your editorial workflow requires per-author filtering.', 'wp_bunnymagazine'); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>

	<div class="bunny-card">
		<h2><?php echo esc_html__('Content Sources', 'wp_bunnymagazine'); ?></h2>
		<p class="description">
			<?php echo esc_html__('Built-in content sources available in every block\'s Content Source selector:', 'wp_bunnymagazine'); ?>
		</p>
		<ul>
			<li><strong><?php echo esc_html__('Latest Posts', 'wp_bunnymagazine'); ?></strong> — <?php echo esc_html__('most recently published posts.', 'wp_bunnymagazine'); ?></li>
			<li><strong><?php echo esc_html__('Latest Updated Posts', 'wp_bunnymagazine'); ?></strong> — <?php echo esc_html__('most recently modified posts.', 'wp_bunnymagazine'); ?></li>
			<li><strong><?php echo esc_html__('Random Posts', 'wp_bunnymagazine'); ?></strong> — <?php echo esc_html__('a fresh random selection on every page load (not cached).', 'wp_bunnymagazine'); ?></li>
		</ul>
		<p class="description">
			<?php echo esc_html__('Additional sources (WooCommerce Products, Custom Post Types, Affiliate Products, etc.) can be added by other plugins or custom code via the wpbm_content_sources filter, without modifying BunnyMagazine core files.', 'wp_bunnymagazine'); ?>
		</p>
	</div>

	<div class="bunny-card">
		<h2><?php echo esc_html__('Invalid or Legacy Content Source Values', 'wp_bunnymagazine'); ?></h2>
		<p class="description">
			<?php echo esc_html__('Blocks saved before this system existed, or blocks with a content source that no longer matches a registered provider, automatically fall back to Latest Posts. No manual migration is required.', 'wp_bunnymagazine'); ?>
		</p>
	</div>

</div>
</div>
