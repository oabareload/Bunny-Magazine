<?php

declare(strict_types=1);

namespace BunnyMagazine\Core;

if (!defined('ABSPATH')) {
	exit;
}

final class Layout_Engine
{
	public function render(string $layout_slug, array $posts, array $attributes = []): string
	{
		$layout = Layout_Registry::get($layout_slug);
		if (!$layout) {
			return '';
		}

		$render_file = trailingslashit((string) $layout['path']) . 'render.php';
		if (!is_readable($render_file)) {
			return '';
		}

		$attributes = wp_parse_args(
			$attributes,
			[
				'layout' => $layout_slug,
				'ctaLabel' => __('Read more', 'wp_bunnymagazine'),
			]
		);

		if (defined('REST_REQUEST') && REST_REQUEST) {
			@ini_set('display_errors', '0');
			@ini_set('display_startup_errors', '0');
		}

		ob_start();

		set_error_handler(
			static function (int $severity, string $message, string $file = '', int $line = 0): bool {
				if (!(error_reporting() & $severity)) {
					return false;
				}

				throw new \ErrorException($message, 0, $severity, $file, $line);
			}
		);

		try {
			include $render_file;
			$output = (string) ob_get_clean();
			restore_error_handler();

			return $output;
		} catch (\Throwable $e) {
			if (ob_get_level() > 0) {
				ob_end_clean();
			}

			restore_error_handler();
			error_log($e->getMessage());

			return '<div class="bunny-magazine-error">Render error</div>';
		}
	}
}
