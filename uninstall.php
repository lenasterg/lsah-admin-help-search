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
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop the network-wide searches table (safe for both single-site and multisite)
$lsah_table_name = $wpdb->base_prefix . 'lsah_admin_searches';
$wpdb->query("DROP TABLE IF EXISTS $lsah_table_name");

// Delete the stored site options (works correctly in both single-site and multisite)
delete_site_option('lsah_help_search_action_url');
delete_site_option('lsah_notice_set_help_url');

/**
 * 3. Delete all user metadata related to search visibility
 * We use a wildcard query to catch all 'lsah_hide_search_site_{ID}' entries
 * @since 1.2.0
 */
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
        'lsah_hide_search_site_%'
    )
);
