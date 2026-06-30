<?php
/**
 * Bunny Magazine - Reusable admin header.
 *
 * Renders the shared admin chrome using the bunny-* UI system.
 * Each plugin keeps its own copy to stay visually identical across the Bunny ecosystem.
 *
 * @package BunnyMagazine
 */

declare(strict_types=1);

namespace BunnyMagazine\Admin;

if (!defined('ABSPATH')) {
	exit;
}

final class Admin_Header
{
	private static array $tabs = [
		'wpbm-dashboard' => 'Dashboard',
		'wpbm-layouts' => 'Layouts',
		'wpbm-query' => 'Query Settings',
		'wpbm-cache' => 'Cache',
	];

	public static function render(string $current_tab, string $page_label = ''): void
	{
		$version = defined('WPMB_VERSION') ? WPMB_VERSION : '';
		$label = $page_label ?: (self::$tabs[$current_tab] ?? '');
		?>
		<div class="bunny-header">
			<div class="bunny-header-inner">
				<span class="bunny-logo">🐰</span>
				<div class="bunny-title-stack">
					<h1 class="bunny-plugin-name">Bunny Magazine</h1>
					<?php if ($label !== '') : ?>
						<span class="bunny-page-subtitle"><?php echo esc_html($label); ?></span>
					<?php endif; ?>
				</div>
				<?php if ($version !== '') : ?>
					<span class="bunny-version-badge">v<?php echo esc_html($version); ?></span>
				<?php endif; ?>
			</div>
			<nav class="bunny-nav" aria-label="<?php echo esc_attr__('Plugin navigation', 'wp_bunnymagazine'); ?>">
				<?php foreach (self::$tabs as $slug => $tab_label) : ?>
					<a href="<?php echo esc_url(admin_url('admin.php?page=' . $slug)); ?>" class="bunny-nav-item<?php echo $slug === $current_tab ? ' bunny-nav-active' : ''; ?>">
						<?php echo esc_html($tab_label); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>
		<?php
	}
}
