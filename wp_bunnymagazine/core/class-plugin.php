<?php

declare(strict_types=1);

namespace BunnyMagazine\Core;

use BunnyMagazine\Admin\Admin;
use BunnyMagazine\Blocks\Block_Registry;

if (!defined('ABSPATH')) {
	exit;
}

final class Plugin
{
	private static ?self $instance = null;

	private Query_Manager $query_manager;
	private Layout_Engine $layout_engine;

	public static function instance(): self
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct()
	{
		$this->query_manager = new Query_Manager();
		$this->layout_engine = new Layout_Engine();
	}

	public function register(): void
	{
		load_plugin_textdomain('wp_bunnymagazine', false, dirname(WPMB_BASENAME) . '/languages');

		add_action('init', [$this, 'register_blocks']);
		add_action('init', [$this, 'register_post_events']);
		add_action('admin_menu', [Admin::class, 'menu']);
		add_action('admin_init', [Admin::class, 'register_settings']);
		add_action('admin_post_wpbm_rescan_layouts', [Admin::class, 'handle_rescan']);
		add_action('admin_post_wpbm_clear_cache', [Admin::class, 'handle_clear_cache']);

		Admin::boot();
		Block_Registry::boot($this->query_manager, $this->layout_engine);
	}

	public function register_blocks(): void
	{
		Block_Registry::register_all();
	}

	public function register_post_events(): void
	{
		add_action('transition_post_status', [$this, 'maybe_invalidate_on_transition'], 10, 3);
		add_action('post_updated', [$this, 'maybe_invalidate_on_update'], 10, 3);
	}

	public function maybe_invalidate_on_transition(string $new_status, string $old_status, \WP_Post $post): void
	{
		if ($post->post_type !== 'post') {
			return;
		}

		if ($new_status === 'publish' && Cache_Manager::invalidate_on_publish()) {
			Cache_Manager::clear();
		}
	}

	public function maybe_invalidate_on_update(int $post_id, \WP_Post $post_after, \WP_Post $post_before): void
	{
		if ($post_after->post_type !== 'post' || wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
			return;
		}

		if ($post_before->post_status === 'publish' && $post_after->post_status === 'publish' && Cache_Manager::invalidate_on_update()) {
			Cache_Manager::clear();
		}
	}

	public function query_manager(): Query_Manager
	{
		return $this->query_manager;
	}

	public function layout_engine(): Layout_Engine
	{
		return $this->layout_engine;
	}
}
