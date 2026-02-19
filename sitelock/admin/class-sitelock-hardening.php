<?php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * WordPress Security Plugin - Hardening Features
 */
class Sitelock_Hardening
{
    private $settings;
    private $htaccess_path;
    private $uploads_htaccess_path;
    private $wp_filesystem;
    private $htaccess_backup_limit = 5; // Configurable backup limit
    private $sitelock_language_tokens;

    public function __construct()
    {
        $settings = get_option('sitelock_security_settings', []);
        $this->settings = is_array($settings) ? $settings : [];
        $this->htaccess_path         = ABSPATH . '.htaccess';
        $this->uploads_htaccess_path = ABSPATH . 'wp-content/uploads/.htaccess';

        if (function_exists('sitelock_get_language_tokens')) {
            $this->sitelock_language_tokens = sitelock_get_language_tokens();
        } else {
            $this->sitelock_language_tokens = [];
        }

        add_action('admin_init', [$this, 'register_security_settings']);
        add_action('add_option_sitelock_security_settings', [$this, 'update_security_rules'], 10, 2);
        add_action('update_option_sitelock_security_settings', [$this, 'update_security_rules'], 10, 2);
        add_action('add_option_sitelock_security_settings', [$this, 'update_security_rules'], 10, 2);

        add_action('admin_notices', [$this, 'display_permission_errors']);
    }

    /**
     * Initialize WP_Filesystem if not already available.
     * 
     * @return bool True if filesystem is available, false otherwise.
     */
    private function init_filesystem()
    {
        if ($this->wp_filesystem) {
            return true;
        }

        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if (WP_Filesystem()) {
            global $wp_filesystem;
            $this->wp_filesystem = $wp_filesystem;
            return true;
        }

        return false;
    }

    /**
     * Display permission error notices stored in transients.
     * This is necessary because errors occur during update_option hook,
     * which fires after admin_notices hook has already executed.
     */
    public function display_permission_errors()
    {
        $error = get_transient('sitelock_permission_error');
        if ($error) {
            echo '<div class="notice notice-error is-dismissible"><p>' . wp_kses_post($error) . '</p></div>';
            // Do not delete transient here so setting.php can also use it to suppress success message
        }
    }

    public function register_security_settings()
    {
        register_setting('sitelock_security_group', 'sitelock_security_settings', [$this, 'sanitize_settings']);
        register_setting('sitelock_security_group', 'sitelock_blocked_directories', [
            'type'              => 'array',
            'sanitize_callback' => function ($input) {
                return array_map('sanitize_text_field', (array) $input);
            },
            'default' => [],
        ]);
        add_settings_section('security_section', 'Security Settings', function () {
            echo '<p>Configure security settings to enhance your WordPress protection.</p>';
        }, 'security-plugin');

        $options = [
            // 'force_https'           => 'Force HTTPS',
            'disable_dir_listing' => 'Disable Directory Listing',
            'limit_php_execution' => 'Limit PHP Execution',
            'xss_sqli_protection' => 'Enable XSS & SQLi Protection',
        ];

        foreach ($options as $key => $label) {
            add_settings_field($key, esc_html($label), function () use ($key) {
                $value = isset($this->settings[$key]) ? esc_attr($this->settings[$key]) : '';
                echo "<input type='checkbox' name='" . esc_attr("sitelock_security_settings[$key]") . "' value='1' " . checked(1, $value, false) . '>';
            }, 'security-plugin', 'security_section');
        }
    }

    public function update_sitelock_blocked_directories()
    {
        if (isset($_POST['sitelock_security_settings']) && current_user_can('manage_options')) {
            $nonce = isset($_POST['sitelock_security_nonce']) ? sanitize_text_field(wp_unslash($_POST['sitelock_security_nonce'])) : '';
            if (!wp_verify_nonce($nonce, 'sitelock_security_form_action')) {
                wp_die(esc_html($this->sitelock_language_tokens['common_errors']['nonceVerificationFailed']));
            }
            $old_blocked_dirs = get_option('sitelock_blocked_directories', []);
            if (isset($_POST['sitelock_blocked_directories']) && is_array($_POST['sitelock_blocked_directories'])) {
                $blocked_dirs = isset($_POST['sitelock_blocked_directories']) && is_array($_POST['sitelock_blocked_directories'])
                    ? array_map('sanitize_text_field', wp_unslash($_POST['sitelock_blocked_directories']))
                    : [];
                $selected_dirs = array_map('sanitize_text_field', $blocked_dirs);
                update_option('sitelock_blocked_directories', $selected_dirs);
            } else {
                update_option('sitelock_blocked_directories', []);
            }
            $this->sitelock_apply_directory_htaccess_rules($old_blocked_dirs);
        }
    }

    public function sitelock_apply_directory_htaccess_rules($previous_dirs)
    {
        if (!$this->init_filesystem()) {
            return;
        }
        $current_dirs = get_option('sitelock_blocked_directories', []);

        if (!is_array($current_dirs)) {
            $current_dirs = [];
        }
        if (!is_array($previous_dirs)) {
            $previous_dirs = [];
        }

        // Define the rule to be added or removed
        $rules = [
            '<IfModule mod_php7.c>',
            '    php_flag engine off',
            '</IfModule>',
            '<IfModule mod_php5.c>',
            '    php_flag engine off',
            '</IfModule>',
            '<FilesMatch "\.(php|php5|php7|phtml|cgi)$">',
            '    Order Allow,Deny',
            '    Deny from all',
            '</FilesMatch>',
        ];

        // Directories to remove rules from
        $dirs_to_remove = array_diff($previous_dirs, $current_dirs);

        foreach ($dirs_to_remove as $dir) {
            $full_path     = ABSPATH . ltrim($dir, '/');
            $htaccess_file = $full_path . '/.htaccess';

            // Capture state before removal
            $before_state = $this->capture_htaccess_state($htaccess_file);

            if ($before_state['exists'] && $before_state['writable']) {
                $this->create_htaccess_backup($htaccess_file, $before_state['raw_content']);
                $this->rotate_htaccess_backups($htaccess_file);
            }

            if ($this->wp_filesystem->exists($htaccess_file) && $this->wp_filesystem->is_writable($htaccess_file)) {
                 // Migration: Remove old regex-based rules if they exist
                $content = $this->wp_filesystem->get_contents($htaccess_file);
                if (strpos($content, '# Sitelock Limit Access Security Rules Start') !== false) {
                    $content = preg_replace('/# Sitelock Limit Access Security Rules Start.*?# Sitelock Limit Access Security Rules End\s*/s', '', $content);
                    $this->wp_filesystem->put_contents($htaccess_file, $content);
                }
                
                // Use insert_with_markers to remove rules (pass empty array)
                insert_with_markers($htaccess_file, 'Sitelock Limit Access Security Rules', []);
                
                // Capture state after removal
                $after_state = $this->capture_htaccess_state($htaccess_file);
                
                // Log successful removal
                $this->log_htaccess_operation(
                    $htaccess_file,
                    'remove',
                    ['directory' => $dir],
                    $before_state,
                    $after_state,
                    [],
                    true
                );
            } else {
                // Log error if file not writable
                $this->log_htaccess_operation(
                    $htaccess_file,
                    'remove',
                    ['directory' => $dir],
                    $before_state,
                    [],
                    [],
                    false,
                    'File does not exist or is not writable'
                );
            }
        }

        // Directories to add rules to
        $dirs_to_add = array_diff($current_dirs, $previous_dirs);

        foreach ($dirs_to_add as $dir) {
            $full_path = ABSPATH . ltrim($dir, '/');
            
            // Normalize paths for comparison (remove trailing slashes)
            $normalized_path = untrailingslashit($full_path);
            $normalized_abspath = untrailingslashit(ABSPATH);
            
            // Critical Directory Blacklist
            $blacklisted_paths = [
                $normalized_abspath,                  // Site Root
                $normalized_abspath . '/wp-admin',    // Admin Panel
                $normalized_abspath . '/wp-includes', // Core System
                $normalized_abspath . '/wp-content',  // Content Root (Subdirs are okay, but root might be risky depending on config)
            ];

            if (in_array($normalized_path, $blacklisted_paths)) {
                sitelock_log(
                    'warning',
                    'Directory Hardening Skipped (Safety)',
                    "Attempt to block a critical system directory was prevented: {$dir}",
                    ['directory' => $dir, 'full_path' => $full_path],
                    __CLASS__
                );
                continue;
            }

            if (is_dir($full_path)) {
                $htaccess_file = $full_path . '/.htaccess';

                // Capture state before addition
                $before_state = $this->capture_htaccess_state($htaccess_file);

                if ($before_state['exists'] && $before_state['writable']) {
                    $this->create_htaccess_backup($htaccess_file, $before_state['raw_content']);
                    $this->rotate_htaccess_backups($htaccess_file);
                }

                // Migration: Remove old regex-based rules if they exist
                if ($this->wp_filesystem->exists($htaccess_file)) {
                    $content = $this->wp_filesystem->get_contents($htaccess_file);
                    if (strpos($content, '# Sitelock Limit Access Security Rules Start') !== false) {
                        $content = preg_replace('/# Sitelock Limit Access Security Rules Start.*?# Sitelock Limit Access Security Rules End\s*/s', '', $content);
                        $this->wp_filesystem->put_contents($htaccess_file, $content);
                    }
                }

                // Ensure rules are at the top: Prepend markers if they don't exist
                if ($this->wp_filesystem->exists($htaccess_file)) {
                    $content = $this->wp_filesystem->get_contents($htaccess_file);
                    if (strpos($content, '# BEGIN Sitelock Limit Access Security Rules') === false) {
                        $markers = "# BEGIN Sitelock Limit Access Security Rules\n# END Sitelock Limit Access Security Rules\n";
                        $this->wp_filesystem->put_contents($htaccess_file, $markers . $content);
                    }
                }

                // Append rules to the file
                insert_with_markers($htaccess_file, 'Sitelock Limit Access Security Rules', $rules);
                
                // Capture state after addition
                $after_state = $this->capture_htaccess_state($htaccess_file);
                
                // Log successful addition
                $this->log_htaccess_operation(
                    $htaccess_file,
                    'add',
                    ['directory' => $dir],
                    $before_state,
                    $after_state,
                    $rules,
                    true
                );
            } else {
                // Log error if directory doesn't exist
                sitelock_log(
                    'warning',
                    'Directory .htaccess Modification Skipped',
                    "Directory does not exist: {$full_path}",
                    ['directory' => $dir, 'full_path' => $full_path],
                    __CLASS__
                );
            }
        }
    }

    public function sanitize_settings($input)
    {
        return is_array($input) ? array_map('sanitize_text_field', $input) : [];
    }

    public function apply_security_rules()
    {
        $server = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : '';

        if (strpos($server, 'Apache') !== false) {
            $this->apply_apache_security();
        }
    }

    /**
     * Main orchestrator for applying Apache security rules.
     * Determines which .htaccess files need updating based on changed settings.
     *
     * @param array|null $changed_keys List of settings keys that changed. If null, assumes all.
     * @return bool True on success (or no update needed), false on failure
     */
    private function apply_apache_security($changed_keys = null)
    {
        // Define which settings affect which file
        $main_relevant_keys    = ['disable_dir_listing', 'xss_sqli_protection', 'limit_php_execution'];
        $uploads_relevant_keys = ['blocked_directories'];

        // Determine validity based on changed keys
        $should_update_main    = $changed_keys === null || !empty(array_intersect($changed_keys, $main_relevant_keys));
        $should_update_uploads = $changed_keys === null || !empty(array_intersect($changed_keys, $uploads_relevant_keys));

        $overall_success = true;

        // Start with main .htaccess
        if ($should_update_main) {
            // Check if main .htaccess settings have values
            $has_main_settings = !empty($this->settings['disable_dir_listing']) ||
                                 !empty($this->settings['xss_sqli_protection']) ||
                                 !empty($this->settings['limit_php_execution']);

            // Only execute main .htaccess logic if relevant settings exist or if cleaning up
            if ($has_main_settings || $this->has_main_htaccess_rules()) {
                if (!$this->apply_main_htaccess_rules()) {
                    $overall_success = false;
                }
            }
        }

        // Proceed to uploads .htaccess
        if ($should_update_uploads) {
            // Check if uploads .htaccess settings have values
            $has_uploads_settings = !empty($this->settings['blocked_directories']);

            // Only execute uploads .htaccess logic if relevant settings exist or if cleaning up
            if ($has_uploads_settings || $this->has_uploads_htaccess_rules()) {
                if (!$this->apply_uploads_htaccess_rules()) {
                    $overall_success = false;
                }
            }
        }

        return $overall_success;
    }

    /**
     * Check if main .htaccess file has existing Sitelock rules.
     */
    private function has_main_htaccess_rules()
    {
        if (!$this->init_filesystem()) {
            return false;
        }
        if (!$this->wp_filesystem->exists($this->htaccess_path)) {
            return false;
        }
        $content = $this->wp_filesystem->get_contents($this->htaccess_path);
        return strpos($content, '# BEGIN SitelockRules') !== false;
    }

    /**
     * Check if uploads .htaccess file has existing Sitelock rules.
     */
    private function has_uploads_htaccess_rules()
    {
        if (!$this->init_filesystem()) {
            return false;
        }
        if (!$this->wp_filesystem->exists($this->uploads_htaccess_path)) {
            return false;
        }
        $content = $this->wp_filesystem->get_contents($this->uploads_htaccess_path);
        return strpos($content, '# BEGIN SitelockRules') !== false;
    }

    /**
     * Apply security rules to main .htaccess file.
     * Handles: disable_dir_listing, xss_sqli_protection, limit_php_execution
     * 
     * @return bool True on success, false on failure
     */
    private function apply_main_htaccess_rules()
    {
        if (!$this->init_filesystem()) {
            return false;
        }
        // Capture state before modification
        $before_state = $this->capture_htaccess_state($this->htaccess_path);

        if ($before_state['exists'] && $before_state['writable']) {
            $this->create_htaccess_backup($this->htaccess_path, $before_state['raw_content']);
            $this->rotate_htaccess_backups($this->htaccess_path);
        }
        
        // Build rules array based on enabled settings
        $new_rules = [];

        if (!empty($this->settings['disable_dir_listing'])) {
            $new_rules[] = '#SitelockDirectoryListingRulesStart';
            $new_rules[] = 'Options -Indexes';
            $new_rules[] = '#SitelockDirectoryListingRulesEnd';
        }

        if (!empty($this->settings['xss_sqli_protection'])) {
            $new_rules[] = '#SitelockXssSqliRulesStart';
            $new_rules[] = '<IfModule mod_rewrite.c>';
            $new_rules[] = '    RewriteEngine On';
            $new_rules[] = '    RewriteCond %{QUERY_STRING} (union.*select.*|<script.*>) [NC]';
            $new_rules[] = '    RewriteRule .* - [F]';
            $new_rules[] = '</IfModule>';
            $new_rules[] = '#SitelockXssSqliRulesEnd';
        }

        if (!empty($this->settings['limit_php_execution'])) {
            $new_rules[] = '#SitelockPHPExecutionRulesStart';
            $new_rules[] = "<FilesMatch \"\\.(phtml|phar|cgi|pl|py|asp|aspx|jsp)$\">";
            $new_rules[] = '    <IfModule mod_authz_core.c>';
            $new_rules[] = '        Require all denied';
            $new_rules[] = '    </IfModule>';
            $new_rules[] = '    <IfModule !mod_authz_core.c>';
            $new_rules[] = '        Order allow,deny';
            $new_rules[] = '        Deny from all';
            $new_rules[] = '    </IfModule>';
            $new_rules[] = '</FilesMatch>';
            $new_rules[] = '#SitelockPHPExecutionRulesEnd';
        }


        // Only create .htaccess if we actually have rules to apply
        if (!empty($new_rules)) {
            // Ensure .htaccess file exists and is writable
            if (!$this->wp_filesystem->exists($this->htaccess_path)) {
                $default_rules = '# File created by Sitelock Security Plugin'. "\n";
                if ((!$this->wp_filesystem->is_writable(dirname($this->htaccess_path))) || !$this->wp_filesystem->put_contents($this->htaccess_path, $default_rules, 0644)) {
                    // Log error
                    $this->log_htaccess_operation(
                        $this->htaccess_path,
                        'create',
                        $this->settings,
                        $before_state,
                        [],
                        [],
                        false,
                        'Permission denied: Unable to create .htaccess file'
                    );
                    
                    set_transient('sitelock_permission_error', 'Permission denied for the plugin. Unable to create the <code>.htaccess</code> file. All hardening features has been disabled.', 45);
                    update_option('sitelock_security_settings', []);
                    return false;
                }
                clearstatcache(true, $this->htaccess_path);
            } elseif (!$this->wp_filesystem->is_writable($this->htaccess_path)) {
                // Log error
                $this->log_htaccess_operation(
                    $this->htaccess_path,
                    'update',
                    $this->settings,
                    $before_state,
                    [],
                    [],
                    false,
                    'Permission denied: .htaccess file is not writable'
                );
                
                set_transient('sitelock_permission_error', 'Permission denied for the plugin. Unable to update the <code>.htaccess</code> file. All hardening features has been disabled.', 45);
                update_option('sitelock_security_settings', []);
                return false;
            }
        } else {
            // No new rules. If file doesn't exist, we're done (don't create it).
            if (!$this->wp_filesystem->exists($this->htaccess_path)) {
                return true;
            }
        }

        // Migration: Remove old regex-based rules if they exist
        if ($this->wp_filesystem->exists($this->htaccess_path)) {
            $content = $this->wp_filesystem->get_contents($this->htaccess_path);
            if (strpos($content, '# SitelockRulesStart') !== false) {
                $content = preg_replace('/# SitelockRulesStart.*?# SitelockRulesEnd\s*/s', '', $content);
                $this->wp_filesystem->put_contents($this->htaccess_path, $content);
            }
        }

        // Apply or remove rules
        if (empty($new_rules)) {
            // Remove block if it exists
            if ($this->wp_filesystem->exists($this->htaccess_path) && $this->wp_filesystem->is_writable($this->htaccess_path)) {
                $content = $this->wp_filesystem->get_contents($this->htaccess_path);
                if (strpos($content, '# BEGIN SitelockRules') !== false) {
                    $content = preg_replace('/[\r\n]*# BEGIN SitelockRules.*?# END SitelockRules[\r\n]*/s', "\n", $content);
                    $content = trim($content);
                    $this->wp_filesystem->put_contents($this->htaccess_path, $content);
                }
            }
            
            // Capture state after removal
            $after_state = $this->capture_htaccess_state($this->htaccess_path);
            
            // Log successful removal
            $this->log_htaccess_operation(
                $this->htaccess_path,
                'remove',
                $this->settings,
                $before_state,
                $after_state,
                [],
                true
            );
            return true;
        } else {
            
            insert_with_markers($this->htaccess_path, 'SitelockRules', $new_rules);
            
            // Validate .htaccess changes (hybrid: Apache + HTTP)
            $validation = $this->validate_htaccess_changes(
                $this->htaccess_path,
                $before_state['raw_content']
            );
            
            // Capture state after modification (or after rollback if validation failed)
            $after_state = $this->capture_htaccess_state($this->htaccess_path);
            
            if (!$validation['overall_success']) {
                // Validation failed and rollback was performed
                $this->log_htaccess_operation(
                    $this->htaccess_path,
                    'update',
                    $this->settings,
                    $before_state,
                    $after_state,
                    $new_rules,
                    false,
                    $validation['failure_reason']
                );
                
                // Add validation details to log
                sitelock_log(
                    'error',
                    '.htaccess Validation Failed - Rollback Performed',
                    'Validation failed after .htaccess modification. File has been rolled back to previous state.',
                    [
                        'file_path' => $this->htaccess_path,
                        'validation' => $validation,
                        'before_md5' => $before_state['md5'],
                        'after_md5' => $after_state['md5'],
                    ],
                    __CLASS__
                );
                
                set_transient('sitelock_permission_error', 
                    'Security rules validation failed. Changes have been rolled back to prevent site errors. Details: ' . 
                    $validation['failure_reason'], 45);
                
                return false;
            }
            
            // Validation passed - log successful modification with validation details
            $context = [
                'operation' => 'update',
                'file_path' => $this->htaccess_path,
                'before' => $before_state,
                'settings' => $this->settings,
                'rules_generated' => $new_rules,
                'after' => $after_state,
                'success' => true,
                'validation' => $validation,
            ];
            
            sitelock_log('info', '.htaccess Modification - .htaccess', 
                'Successfully applied and validated security rules', $context, __CLASS__);
            return true;
        }
    }

    /**
     * Apply security rules to uploads .htaccess file.
     * Handles: blocked_directories
     * 
     * @return bool True on success, false on failure
     */
    private function apply_uploads_htaccess_rules()
    {
        if (!$this->init_filesystem()) {
            return false;
        }
        // Capture state before modification
        $before_state = $this->capture_htaccess_state($this->uploads_htaccess_path);

        if ($before_state['exists'] && $before_state['writable']) {
            $this->create_htaccess_backup($this->uploads_htaccess_path, $before_state['raw_content']);
            $this->rotate_htaccess_backups($this->uploads_htaccess_path);
        }
        
        // Build rules array based on enabled settings
        $uploads_htaccess_rules = [];

        if (!empty($this->settings['blocked_directories'])) {
            $uploads_htaccess_rules[] = '<IfModule mod_rewrite.c>';
            $uploads_htaccess_rules[] = '    RewriteEngine On';
            $uploads_htaccess_rules[] = '    RewriteCond %{REQUEST_FILENAME} -f';
            $uploads_htaccess_rules[] = "    RewriteCond %{REQUEST_FILENAME} \\.(php[0-9]?|phtml|phar|cgi|pl|py|asp|aspx|jsp)$ [NC]";
            $uploads_htaccess_rules[] = "    RewriteRule \\.php$ - [F,L]";
            $uploads_htaccess_rules[] = '</IfModule>';
            $uploads_htaccess_rules[] = '# Extra protection: Deny access via FilesMatch (works with mod_authz_core)';
            $uploads_htaccess_rules[] = "<FilesMatch \"\\.(php[0-9]?|phtml|phar|cgi|pl|py|asp|aspx|jsp)$\">";
            $uploads_htaccess_rules[] = '    Require all denied';
            $uploads_htaccess_rules[] = '</FilesMatch>';
        }

        // Only create uploads/.htaccess if we have rules
        if (!empty($uploads_htaccess_rules)) {
            // Ensure uploads .htaccess file exists and is writable
            if (!$this->wp_filesystem->exists($this->uploads_htaccess_path)) {
                $default_rules = '# File created by Sitelock Security Plugin'. "\n";
                if ((!$this->wp_filesystem->is_writable(dirname($this->uploads_htaccess_path))) || !$this->wp_filesystem->put_contents($this->uploads_htaccess_path, $default_rules, 0644)) {
                    // Log error
                    $this->log_htaccess_operation(
                        $this->uploads_htaccess_path,
                        'create',
                        $this->settings,
                        $before_state,
                        [],
                        [],
                        false,
                        'Permission denied: Unable to create uploads/.htaccess file'
                    );
                    
                    set_transient('sitelock_permission_error', 'Permission denied for the plugin. Unable to create the <code>uploads/.htaccess</code> file. The "Harden Writable Directories" feature has been disabled.', 45);
                    unset($this->settings['blocked_directories']);
                    update_option('sitelock_security_settings', $this->settings);
                    return false;
                }
            } elseif (!$this->wp_filesystem->is_writable($this->uploads_htaccess_path)) {
                // Log error
                $this->log_htaccess_operation(
                    $this->uploads_htaccess_path,
                    'update',
                    $this->settings,
                    $before_state,
                    [],
                    [],
                    false,
                    'Permission denied: uploads/.htaccess file is not writable'
                );
                
                set_transient('sitelock_permission_error', 'Permission denied for the plugin. Unable to update the <code>uploads/.htaccess</code> file. The "Harden Writable Directories" feature has been disabled.', 45);
                unset($this->settings['blocked_directories']);
                update_option('sitelock_security_settings', $this->settings);
                return false;
            }
        } else {
             // No new rules. If file doesn't exist, we're done (don't create it).
             if (!$this->wp_filesystem->exists($this->uploads_htaccess_path)) {
                return true;
            }
        }

        // Migration: Remove old regex-based rules if they exist
        if ($this->wp_filesystem->exists($this->uploads_htaccess_path)) {
            $content = $this->wp_filesystem->get_contents($this->uploads_htaccess_path);
            if (strpos($content, '#SitelockRulesStart') !== false) {
                $content = preg_replace('/#SitelockRulesStart.*?#SitelockRulesEnd\s*/s', '', $content);
                $this->wp_filesystem->put_contents($this->uploads_htaccess_path, $content);
            }
        }

        // Apply or remove rules
        if (empty($uploads_htaccess_rules)) {
            // Remove block if it exists (Strict cleanup)
            if ($this->wp_filesystem->exists($this->uploads_htaccess_path) && $this->wp_filesystem->is_writable($this->uploads_htaccess_path)) {
                $content = $this->wp_filesystem->get_contents($this->uploads_htaccess_path);
                if (strpos($content, '# BEGIN SitelockRules') !== false) {
                    $content = preg_replace('/[\r\n]*# BEGIN SitelockRules.*?# END SitelockRules[\r\n]*/s', "\n", $content);
                    $content = trim($content);
                    $this->wp_filesystem->put_contents($this->uploads_htaccess_path, $content);
                }
            }
            
            // Capture state after removal
            $after_state = $this->capture_htaccess_state($this->uploads_htaccess_path);
            
            // Log successful removal
            $this->log_htaccess_operation(
                $this->uploads_htaccess_path,
                'remove',
                $this->settings,
                $before_state,
                $after_state,
                [],
                true
            );
            return true;
        } else {
            
            insert_with_markers($this->uploads_htaccess_path, 'SitelockRules', $uploads_htaccess_rules);
            
            // Validate .htaccess changes (hybrid: Apache + HTTP)
            $validation = $this->validate_htaccess_changes(
                $this->uploads_htaccess_path,
                $before_state['raw_content']
            );
            
            // Capture state after modification (or after rollback if validation failed)
            $after_state = $this->capture_htaccess_state($this->uploads_htaccess_path);
            
            if (!$validation['overall_success']) {
                // Validation failed and rollback was performed
                $this->log_htaccess_operation(
                    $this->uploads_htaccess_path,
                    'update',
                    $this->settings,
                    $before_state,
                    $after_state,
                    $uploads_htaccess_rules,
                    false,
                    $validation['failure_reason']
                );
                
                // Add validation details to log
                sitelock_log(
                    'error',
                    '.htaccess Validation Failed - Rollback Performed',
                    'Validation failed after uploads/.htaccess modification. File has been rolled back to previous state.',
                    [
                        'file_path' => $this->uploads_htaccess_path,
                        'validation' => $validation,
                        'before_md5' => $before_state['md5'],
                        'after_md5' => $after_state['md5'],
                    ],
                    __CLASS__
                );
                
                set_transient('sitelock_permission_error', 
                    'Uploads security rules validation failed. Changes have been rolled back to prevent site errors. Details: ' . 
                    $validation['failure_reason'], 45);
                
                return false;
            }
            
            // Validation passed - log successful modification with validation details
            $context = [
                'operation' => 'update',
                'file_path' => $this->uploads_htaccess_path,
                'before' => $before_state,
                'settings' => $this->settings,
                'rules_generated' => $uploads_htaccess_rules,
                'after' => $after_state,
                'success' => true,
                'validation' => $validation,
            ];
            
            sitelock_log('info', '.htaccess Modification - .htaccess', 
                'Successfully applied and validated uploads security rules', $context, __CLASS__);
            return true;
        }
    }

    /**
     * Triggered when security settings are added or updated.
     * Handles both add_option and update_option hooks.
     * 
     * @param mixed $old_value Old value (update) or option name (add)
     * @param mixed $new_value New value
     */
    public function update_security_rules($old_value, $new_value)
    {
    
        if (!$old_value || !is_array($old_value)) {
            $old_value = [];
        }
        if (!$new_value || !is_array($new_value)) {
            $new_value = [];
        }

        // Explicitly fetch fresh settings from DB to ensure we have the latest committed value.
        // This avoids any potential confusion with hook argument order or stale data.
        $this->settings = get_option('sitelock_security_settings', []);
        


        $new_value = $this->settings;

        $disabled_features = array_diff_key($old_value, $new_value);

        if (!empty($disabled_features)) {
            $this->remove_apache_security_rules(array_keys($disabled_features));
        }

        // Identify which keys actually changed (values diff)
        // Note: array_diff_assoc doesn't work well if keys are missing from one.
        // We merged defaults before? No.
        // Let's just merge all keys and compare.
        $all_keys = array_unique(array_merge(array_keys($old_value), array_keys($new_value)));
        $changed_keys = [];

        foreach ($all_keys as $key) {
            $v_old = isset($old_value[$key]) ? $old_value[$key] : null;
            $v_new = isset($new_value[$key]) ? $new_value[$key] : null;

            if ($v_old !== $v_new) {
                $changed_keys[] = $key;
            }
        }

        if (!$this->apply_apache_security($changed_keys)) {
             // Revert settings to old value if application failed
             // Remove hook to prevent infinite loop
             remove_action('update_option_sitelock_security_settings', [$this, 'update_security_rules'], 10);
             remove_action('add_option_sitelock_security_settings', [$this, 'update_security_rules'], 10);
             update_option('sitelock_security_settings', $old_value);
             add_action('update_option_sitelock_security_settings', [$this, 'update_security_rules'], 10, 2);
             add_action('add_option_sitelock_security_settings', [$this, 'update_security_rules'], 10, 2);
             
             // Update internal state
             $this->settings = $old_value;
        }
    }

    private function remove_apache_security_rules($features)
    {
        // No-op: apply_apache_security uses insert_with_markers which handles removal of rules not present in the new set.
    }

    /**
     * Capture the current state of an .htaccess file.
     *
     * @param  string $file_path Path to .htaccess file
     * @return array File state information
     */
    private function capture_htaccess_state($file_path)
    {
        $state = [
            'exists' => file_exists($file_path),
            'writable' => false,
            'content' => '',
            'raw_content' => '', // Store untruncated content for rollback and backup
            'md5' => '',
            'size' => 0,
            'permissions' => '',
            'modified_time' => '',
        ];

        if ($state['exists']) {
            $state['writable'] = $this->wp_filesystem->is_writable($file_path);
            $content = $this->wp_filesystem->get_contents($file_path);
            if ($content !== false) {
                $state['raw_content'] = $content;
                // Use logger's safe_log_content to truncate if needed
                $logger = SiteLock_Logger::instance();
                $state['content'] = $logger->safe_log_content($content);
                $state['md5'] = md5($content);
                $state['size'] = strlen($content);
            }
            
            $perms = @fileperms($file_path);
            if ($perms !== false) {
                $state['permissions'] = substr(sprintf('%o', $perms), -4);
            }
            
            $mtime = @filemtime($file_path);
            if ($mtime !== false) {
                $state['modified_time'] = gmdate('Y-m-d\TH:i:s\Z', $mtime);
            }
        }

        return $state;
    }

    /**
     * Create a physical backup of an .htaccess file in the logs directory.
     *
     * @param  string $file_path Path to the original .htaccess file
     * @param  string $content   Content to backup
     * @return string|bool Path to the backup file on success, false on failure
     */
    private function create_htaccess_backup($file_path, $content)
    {
        $logger = SiteLock_Logger::instance();
        $log_dir = $logger->get_log_dir();
        
        if (!is_dir($log_dir)) {
            return false;
        }

        // Subfolder for clean isolation
        $backup_dir = $log_dir . '/config-backups';
        
        // Ensure directory exists
        if (!$this->wp_filesystem->is_dir($backup_dir)) {
            if (!$this->wp_filesystem->mkdir($backup_dir, FS_CHMOD_DIR)) {
                // Fallback to wp_mkdir_p if filesystem abstraction fails or method missing
                if (!wp_mkdir_p($backup_dir)) {
                     return false;
                }
            }
            
            // Protect this directory additionally (though it should inherit from parent)
            $htaccess_protect = $backup_dir . '/.htaccess';
            if (!$this->wp_filesystem->exists($htaccess_protect)) {
                 $this->wp_filesystem->put_contents($htaccess_protect, "Order deny,allow\nDeny from all");
            }
        }

        $type = (strpos($file_path, 'uploads') !== false) ? 'uploads' : 'main';
        $filename = sprintf('htaccess_%s_%s.bak', $type, gmdate('Ymd_His'));
        $backup_path = $backup_dir . '/' . $filename;

        if ($this->wp_filesystem->put_contents($backup_path, $content, defined('FS_CHMOD_FILE') ? FS_CHMOD_FILE : 0644)) {
            return $backup_path;
        }

        return false;
    }

    /**
     * Rotate old .htaccess backup files, keeping only the configured limit.
     *
     * @param string $file_path Path to the original .htaccess file to identify backup type
     */
    private function rotate_htaccess_backups($file_path)
    {
        $logger = SiteLock_Logger::instance();
        $log_dir = $logger->get_log_dir();
        $backup_dir = $log_dir . '/config-backups';
        
        if (!is_dir($backup_dir)) {
            return;
        }
        
        $limit = $this->htaccess_backup_limit;
        $type = (strpos($file_path, 'uploads') !== false) ? 'uploads' : 'main';
        
        $pattern = $backup_dir . '/htaccess_' . $type . '_*.bak';
        $backups = glob($pattern);
        
        if (empty($backups) || count($backups) <= $limit) {
            return;
        }
        
        // Sort by modified time (oldest first)
        usort($backups, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Delete oldest files
        $to_delete = array_slice($backups, 0, count($backups) - $limit);
        foreach ($to_delete as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Log an .htaccess operation with comprehensive context.
     *
     * @param  string $file_path Path to .htaccess file
     * @param  string $operation Operation type (add|remove|update)
     * @param  array  $settings Settings being applied
     * @param  array  $before_state State before modification
     * @param  array  $after_state State after modification
     * @param  array  $rules_generated Rules that were generated
     * @param  bool   $success Whether operation succeeded
     * @param  string|null $error Error message if failed
     */
    private function log_htaccess_operation(
        $file_path,
        $operation,
        $settings,
        $before_state,
        $after_state,
        $rules_generated,
        $success,
        $error = null
    ) {
        $file_name = basename($file_path);
        $level = $success ? 'info' : 'error';
        $title = $success 
            ? ".htaccess Modification - {$file_name}" 
            : ".htaccess Modification Failed - {$file_name}";
        
        $message = $success
            ? "Successfully applied security rules to {$file_path}"
            : "Failed to apply security rules to {$file_path}";

        $context = [
            'operation' => $operation,
            'file_path' => $file_path,
            'before' => $before_state,
            'settings' => $settings,
            'rules_generated' => $rules_generated,
            'after' => $after_state,
            'success' => $success,
        ];

        if ($error) {
            $context['error'] = $error;
        }

        sitelock_log($level, $title, $message, $context, __CLASS__);
    }

    /**
     * Validate Apache configuration using apachectl configtest.
     *
     * @return array Validation results with availability, validity, and message
     */
    private function validate_apache_config()
    {
        // Check if apachectl is available
        $apachectl = function_exists('exec') ? @exec('which apachectl 2>/dev/null') : '';
        
        if (empty($apachectl)) {
            return [
                'available' => false,
                'valid' => null,
                'message' => 'apachectl command not available on this system',
                'return_code' => null,
            ];
        }
        
        // Run Apache config test
        $output = [];
        $return_code = 0;
        @exec('apachectl configtest 2>&1', $output, $return_code);
        
        return [
            'available' => true,
            'valid' => ($return_code === 0),
            'message' => implode("\n", $output),
            'return_code' => $return_code,
        ];
    }

    /**
     * Validate site accessibility via HTTP request.
     *
     * @param string|null $url Optional URL to check. Defaults to home_url('/').
     * @return array Validation results with accessibility, status code, and message
     */
    private function validate_site_accessibility($url = null)
    {
        $target_url = $url ? $url : home_url('/');
        
        $response = wp_remote_get($target_url, [
            'timeout' => 10,
            'sslverify' => false,
            'headers' => [
                'User-Agent' => 'SiteLock-Validation/1.0',
            ],
        ]);
        
        if (is_wp_error($response)) {
            return [
                'accessible' => false,
                'status_code' => null,
                'message' => $response->get_error_message(),
                'url' => $target_url,
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        return [
            'accessible' => ($status_code >= 200 && $status_code < 400),
            'status_code' => $status_code,
            'message' => wp_remote_retrieve_response_message($response),
            'url' => $target_url,
        ];
    }

    /**
     * Validate .htaccess changes using hybrid approach (Apache test + HTTP test).
     * Automatically rolls back if validation fails.
     *
     * @param  string $file_path Path to .htaccess file
     * @param  string $before_content Content before modification (for rollback)
     * @return array Validation results including rollback status
     */
    private function validate_htaccess_changes($file_path, $before_content)
    {
        $validation_results = [
            'apache_test' => null,
            'http_test_home' => null,
            'http_test_login' => null,
            'overall_success' => false,
            'rollback_performed' => false,
            'failure_reason' => null,
        ];
        
        // Step 1: Try Apache config test first (fast, catches syntax errors)
        $apache_validation = $this->validate_apache_config();
        $validation_results['apache_test'] = $apache_validation;
        
        if ($apache_validation['available'] && !$apache_validation['valid']) {
            // Log warning but proceed to HTTP check (Apache check can be flaky in some envs)
            $validation_results['failure_reason'] = 'Apache syntax validation warning: ' . $apache_validation['message'];
        }
        
        // Step 2: HTTP accessibility test (catches runtime errors)
        // Wait a moment for Apache to reload configuration
        sleep(2);
        
        $max_retries = 1;
        $retry_delay = 2;
        $http_validation_home = [];
        $http_validation_login = [];

        for ($attempt = 0; $attempt <= $max_retries; $attempt++) {
            if ($attempt > 0) {
                sitelock_log('info', 'Validation Retry', "Attempt $attempt (Retrying after $retry_delay sec)", [], __CLASS__);
                sleep($retry_delay);
            }

            // Check Homepage
            $http_validation_home = $this->validate_site_accessibility(home_url('/'));
            $validation_results['http_test_home'] = $http_validation_home;

            // Check Login Page (Critical for Admin Lockout Prevention)
            $http_validation_login = $this->validate_site_accessibility(wp_login_url());
            $validation_results['http_test_login'] = $http_validation_login;

            if ($http_validation_home['accessible'] && $http_validation_login['accessible']) {
                break; // Success
            }
        }
        
        if (!$http_validation_home['accessible']) {
            // Homepage failed - rollback
            $status_info = $http_validation_home['status_code'] 
                ? "HTTP {$http_validation_home['status_code']}" 
                : 'Connection failed';
            $validation_results['failure_reason'] = "Homepage accessibility check failed ({$status_info}): {$http_validation_home['message']}";
            $this->rollback_htaccess($file_path, $before_content);
            $validation_results['rollback_performed'] = true;
            return $validation_results;
        }

        if (!$http_validation_login['accessible']) {
            // Login page failed - rollback
            $status_info = $http_validation_login['status_code'] 
                ? "HTTP {$http_validation_login['status_code']}" 
                : 'Connection failed';
            $validation_results['failure_reason'] = "Login page accessibility check failed ({$status_info}): {$http_validation_login['message']}";
            $this->rollback_htaccess($file_path, $before_content);
            $validation_results['rollback_performed'] = true;
            return $validation_results;
        }
        
        // All tests passed
        $validation_results['overall_success'] = true;
        return $validation_results;
    }

    /**
     * Rollback .htaccess file to previous content.
     *
     * @param  string $file_path Path to .htaccess file
     * @param  string $previous_content Previous content to restore
     * @return bool True if rollback successful, false otherwise
     */
    private function rollback_htaccess($file_path, $previous_content)
    {
        if (empty($previous_content)) {
            // If there was no previous content, delete the file
            if ($this->wp_filesystem->exists($file_path)) {
                return $this->wp_filesystem->delete($file_path);
            }
            return true;
        }
        
        // Restore previous content
        $result = $this->wp_filesystem->put_contents($file_path, $previous_content, defined('FS_CHMOD_FILE') ? FS_CHMOD_FILE : 0644);
        
        return $result !== false;
    }


}
