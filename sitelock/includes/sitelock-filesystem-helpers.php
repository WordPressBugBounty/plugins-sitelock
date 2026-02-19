<?php

function sitelock_filesystem_is_writable($file)
{
    global $wp_filesystem;

    // Initialize the WP_Filesystem if not already initialized
    if (empty($wp_filesystem)) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    // Use WP_Filesystem's method to check if the file is writable
    return $wp_filesystem->is_writable($file);
}
