<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

delete_option('wpbm_settings');
delete_option('wpbm_cache_version');
delete_option('wpbm_layout_manifest');

