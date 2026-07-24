<?php
/**
 * Plugin Name:       Bunny Magazine
 * Plugin URI:        https://bunnychase.net/bunny-magazine/
 * Description:       Editorial Gutenberg layouts for magazine-style WordPress pages.
 * Version:           1.4.1
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            BunnyChase
 * Author URI:        https://bunnychase.net/
 * License:           GPL-2.0-or-later
 * Text Domain:       wp_bunnymagazine
 * Domain Path:       /languages
 *
 * @package BunnyMagazine
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

define('WPMB_VERSION', '1.4.1');
define('WPMB_FILE', __FILE__);
define('WPMB_PATH', plugin_dir_path(__FILE__));
define('WPMB_URL', plugin_dir_url(__FILE__));
define('WPMB_BASENAME', plugin_basename(__FILE__));

spl_autoload_register(
	static function (string $class_name): void {
		$prefix = 'BunnyMagazine\\';

		if (strpos($class_name, $prefix) !== 0) {
			return;
		}

		$relative = substr($class_name, strlen($prefix));
		$parts = explode('\\', $relative);
		$namespace = array_shift($parts);
		$class_base = strtolower(str_replace('_', '-', (string) array_pop($parts)));
		$class_file = 'class-' . $class_base . '.php';
		$file_path = WPMB_PATH . strtolower($namespace) . DIRECTORY_SEPARATOR . $class_file;

		if (is_readable($file_path)) {
			require_once $file_path;
		}
	}
);

require_once WPMB_PATH . 'core/helpers.php';

register_activation_hook(WPMB_FILE, static function (): void {
	BunnyMagazine\Core\Cache_Manager::bootstrap_defaults();
	BunnyMagazine\Core\Layout_Registry::refresh_manifest();
});

add_action(
	'plugins_loaded',
	static function (): void {
		BunnyMagazine\Core\Plugin::instance()->register();
	}
);
