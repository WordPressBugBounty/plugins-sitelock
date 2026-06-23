<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.9.0
 * @package    Sitelock
 * @subpackage Sitelock/includes
 * @author     Todd Low <tlow@sitelock.com>
 */
class Sitelock_Activator
{
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.9.0
     */
    public static function activate()
    {
        self::clean_legacy_htaccess_rules();
    }

    /**
     * Clean up legacy .htaccess rules that used regex markers.
     * This ensures we don't have duplicate or conflicting rules when switching to insert_with_markers.
     */
    private static function clean_legacy_htaccess_rules()
    {
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
            // Probe the transport: WP_Filesystem() itself does not throw — the TypeError
            // only fires when a method is called on an FTP transport with no valid connection.
            // Catch it here and reinitialize with 'direct'.
            try {
                if (!empty($wp_filesystem)) {
                    $wp_filesystem->is_dir(ABSPATH);
                }
            } catch (TypeError $e) {
                $force_direct = function () { return 'direct'; };
                add_filter('filesystem_method', $force_direct);
                WP_Filesystem();
                remove_filter('filesystem_method', $force_direct);
            }
        }

        // 1. Clean up main .htaccess
        $htaccess_file = ABSPATH . '.htaccess';
        if ($wp_filesystem->exists($htaccess_file) && $wp_filesystem->is_writable($htaccess_file)) {
            $content = $wp_filesystem->get_contents($htaccess_file);
            if (strpos($content, '# SitelockRulesStart') !== false) {
                $content = preg_replace('/# SitelockRulesStart.*?# SitelockRulesEnd\s*/s', '', $content);
                $wp_filesystem->put_contents($htaccess_file, $content);
            }
        }

        // 2. Clean up uploads .htaccess
        $uploads_htaccess_file = ABSPATH . 'wp-content/uploads/.htaccess';
        if ($wp_filesystem->exists($uploads_htaccess_file) && $wp_filesystem->is_writable($uploads_htaccess_file)) {
            $content = $wp_filesystem->get_contents($uploads_htaccess_file);
            // Note: Uploads .htaccess used #SitelockRulesStart (no space) in some versions
            if (strpos($content, '#SitelockRulesStart') !== false) {
                $content = preg_replace('/#SitelockRulesStart.*?#SitelockRulesEnd\s*/s', '', $content);
                $wp_filesystem->put_contents($uploads_htaccess_file, $content);
            }
        }

        // 3. Clean up blocked directories rules
        $blocked_dirs = get_option('sitelock_blocked_directories', []);
        if (is_array($blocked_dirs)) {
            foreach ($blocked_dirs as $dir) {
                $full_path = ABSPATH . ltrim($dir, '/'); // Ensure path is relative to ABSPATH
                $htaccess_file = $full_path . '/.htaccess';
                
                if ($wp_filesystem->exists($htaccess_file) && $wp_filesystem->is_writable($htaccess_file)) {
                    $content = $wp_filesystem->get_contents($htaccess_file);
                    if (strpos($content, '# Sitelock Limit Access Security Rules Start') !== false) {
                        $content = preg_replace('/# Sitelock Limit Access Security Rules Start.*?# Sitelock Limit Access Security Rules End\s*/s', '', $content);
                        $wp_filesystem->put_contents($htaccess_file, $content);
                    }
                }
            }
        }
    }
}
