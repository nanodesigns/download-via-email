<?php
/**
 * Uninstallation hooks
 *
 * Page to delete data associated with the plugin on its uninstallation.
 *
 * @package  download-via-email
 */

// If uninstall not called from WordPress, exit
if( !defined( 'ABSPATH' ) && !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

$option_name = 'email_downloads_settings';

delete_option( $option_name );

// For site options in multisite
if ( is_multisite() && get_site_option( $option_name ) )
	delete_site_option( $option_name );


/**
 * Delete all the saved email addresses in our custom database.
 * @since  1.0.1
 * ------------------------------------------------------------------------------
 */
global $wpdb;
$table = $wpdb->prefix .'download_email';
$wpdb->query("DROP TABLE IF EXISTS $table");