<?php
/**
 * Uninstall script for LSAH Admin Help Search
 *
 * Runs when the plugin is deleted from the WordPress admin.
 * Cleans up the database table and options.
 *
 * This file is automatically called by WordPress when the plugin is deleted.
 *
 * @package LSAH_Admin_Help_Search
 */

// Exit if uninstall is not called properly (direct access protection)
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Drop the network-wide searches table (safe for both single-site and multisite)
$table_name = $wpdb->base_prefix . 'lsah_admin_searches';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Delete the stored site options (works correctly in both single-site and multisite)
delete_site_option('lsah_help_search_action_url');
delete_site_option('lsah_notice_set_help_url');