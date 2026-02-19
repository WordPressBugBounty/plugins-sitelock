<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       http://www.sitelock.com
 * @since      1.9.0
 *
 * @package    Sitelock
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete plugin options and user meta
 */
global $wpdb;

// Delete all options starting with 'sitelock_'
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$sitelock_options = $wpdb->get_results( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", 'sitelock_%' ) );
foreach ( $sitelock_options as $sitelock_option ) {
	delete_option( $sitelock_option->option_name );
}

// Clear options from cache
wp_cache_delete( 'wpslp_options', 'options' );

// Delete all user meta keys starting with 'sitelock_'
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s", 'sitelock_%' ) );

/**
 * Remove .htaccess rules
 */
if (!function_exists('insert_with_markers')) {
    require_once ABSPATH . 'wp-admin/includes/misc.php';
}

// Initialize WP_Filesystem
if ( ! function_exists( 'WP_Filesystem' ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
}
WP_Filesystem();
global $wp_filesystem;

$sitelock_files_to_clean = [
    ABSPATH . '.htaccess',
    ABSPATH . 'wp-content/uploads/.htaccess'
];

foreach ($sitelock_files_to_clean as $sitelock_file) {
    if ( $wp_filesystem->exists( $sitelock_file ) && $wp_filesystem->is_writable( $sitelock_file ) ) {
        // 1. Remove markers
        insert_with_markers( $sitelock_file, 'SitelockRules', [] );

        // 1.5. Force remove standard markers if insert_with_markers left them
        $sitelock_content = $wp_filesystem->get_contents( $sitelock_file );
        if ( false !== strpos( $sitelock_content, '# BEGIN SitelockRules' ) ) {
            $sitelock_content = preg_replace( '/[\r\n]*# BEGIN SitelockRules.*?# END SitelockRules[\r\n]*/s', "\n", $sitelock_content );
            $wp_filesystem->put_contents( $sitelock_file, $sitelock_content, 0644 );
        }

        // 2. Remove legacy regex (if any)
        $sitelock_content = $wp_filesystem->get_contents( $sitelock_file );
        if ( false !== strpos( $sitelock_content, '# SitelockRulesStart' ) || false !== strpos( $sitelock_content, '#SitelockRulesStart' ) ) {
            $sitelock_content = preg_replace( '/#\s?SitelockRulesStart.*?#\s?SitelockRulesEnd\s*/s', '', $sitelock_content );
            $wp_filesystem->put_contents( $sitelock_file, $sitelock_content, 0644 );
        }

        // 3. Remove "File created by" comment and cleanup
        $sitelock_content = $wp_filesystem->get_contents( $sitelock_file );
        $sitelock_content = str_replace( "# File created by Sitelock Security Plugin\n", "", $sitelock_content );
        $sitelock_content = str_replace( "# File created by Sitelock Security Plugin", "", $sitelock_content );
        $sitelock_content = trim( $sitelock_content );

        if ( empty( $sitelock_content ) ) {
            // If empty, delete the file
            $wp_filesystem->delete( $sitelock_file );
        } else {
            // Otherwise save trimmed content
            $wp_filesystem->put_contents( $sitelock_file, $sitelock_content, 0644 );
        }
    }
}
