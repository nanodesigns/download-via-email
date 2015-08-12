<?php
/**
 * Uninstallation hooks
 *
 * Page to delete data associated with the plugin on its uninstallation.
 *
 * @package  email-downloads
 */

//if uninstall not called from WordPress exit
if( !defined( 'ABSPATH' ) && !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

$option_name = 'email_downloads_settings';

delete_option( $option_name );

// For site options in multisite
if ( is_multisite() && get_site_option( $option_name ) )
	delete_site_option( $option_name );