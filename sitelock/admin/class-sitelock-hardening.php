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

    public function __construct()
    {
        $this->settings              = get_option('sitelock_security_settings', []);
        $this->htaccess_path         = ABSPATH . '.htaccess';
        $this->uploads_htaccess_path = ABSPATH . 'wp-content/uploads/.htaccess';

        add_action('admin_init', [$this, 'register_security_settings']);
        add_action('update_option_sitelock_security_settings', [$this, 'update_security_rules'], 10, 2);
        add_action('init', [$this, 'apply_security_rules']);
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
                wp_die(esc_html__('Nonce verification failed. Please try again.', 'sitelock-wordpress-plugin'));
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
        $current_dirs = get_option('sitelock_blocked_directories', []);

        if (!is_array($current_dirs)) {
            $current_dirs = [];
        }
        if (!is_array($previous_dirs)) {
            $previous_dirs = [];
        }

        // Define the rule to be added or removed
        $rule_start = '# Sitelock Limit Access Security Rules Start';
        $rule_end   = '# Sitelock Limit Access Security Rules End';
        $rule       = $rule_start . "\n" .
            "<IfModule mod_php7.c>\n" .
            "    php_flag engine off\n" .
            "</IfModule>\n" .
            "<IfModule mod_php5.c>\n" .
            "    php_flag engine off\n" .
            "</IfModule>\n" .
            "<FilesMatch \"\\.(php|php5|php7|phtml|cgi)$\">\n" .
            "    Order Allow,Deny\n" .
            "    Deny from all\n" .
            "</FilesMatch>\n" .
            $rule_end;

        // Directories to remove rules from
        $dirs_to_remove = array_diff($previous_dirs, $current_dirs);

        foreach ($dirs_to_remove as $dir) {
            $full_path     = ABSPATH . ltrim($dir, '/');
            $htaccess_file = $full_path . '/.htaccess';

            if (file_exists($htaccess_file) && filesystem_is_writable($htaccess_file)) {
                $existing_content = file_get_contents($htaccess_file);

                // Remove the rules if they exist
                if (strpos($existing_content, $rule_start) !== false) {
                    $updated_content = preg_replace(
                        "/$rule_start.*?$rule_end/s",
                        '',
                        $existing_content
                    );
                    file_put_contents($htaccess_file, $updated_content);
                }
            }
        }

        // Directories to add rules to
        $dirs_to_add = array_diff($current_dirs, $previous_dirs);

        foreach ($dirs_to_add as $dir) {
            $full_path = ABSPATH . ltrim($dir, '/');
            if (is_dir($full_path)) {
                $htaccess_file = $full_path . '/.htaccess';

                // Check if the rules already exist in the file
                if (file_exists($htaccess_file)) {
                    $existing_content = file_get_contents($htaccess_file);
                    if (strpos($existing_content, $rule_start) !== false) {
                        continue; // Skip if rules already exist
                    }
                }

                // Append rules to the file
                file_put_contents($htaccess_file, $rule . PHP_EOL, FILE_APPEND);
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

    private function apply_apache_security()
    {
        if (!file_exists($this->htaccess_path)) {
            // Default WordPress .htaccess rules
            $default_rules = '# File created by Sitelock Security Plugin'. "\n";
            if (!file_put_contents($this->htaccess_path, $default_rules) !== false) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible"><p>Permission denied for the plugin. Unable to create the <code>.htaccess</code> file. All hardening features has been disabled.</p></div>';
                });
                update_option('sitelock_security_settings', []);

                return;
            }
        } elseif (!filesystem_is_writable($this->htaccess_path)) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>Permission denied for the plugin. Unable to update the <code>.htaccess</code> file. All hardening features has been disabled.</p></div>';
            });
            update_option('sitelock_security_settings', []);

            return;
        }

        $htaccess_content = file_get_contents($this->htaccess_path);
        $new_rules        = [];

        // if (!empty($this->settings['force_https'])) {
        //     $new_rules[] = "#SitelockForceHTTPSRulesStart";
        //     $new_rules[] = "<IfModule mod_rewrite.c>";
        //     $new_rules[] = "    RewriteEngine On";
        //     $new_rules[] = "    RewriteCond %{ENV:HTTPS} !=on";
        //     $new_rules[] = "    RewriteCond %{HTTP:X-Forwarded-Proto} !https";
        //     $new_rules[] = "    RewriteCond %{HTTPS} !=on [OR]";
        //     $new_rules[] = "    RewriteCond %{SERVER_PORT} !^443$";
        //     $new_rules[] = "    RewriteCond %{REQUEST_URI} !^/wp-login.php [NC]";
        //     $new_rules[] = "    RewriteCond %{REQUEST_URI} !^/wp-admin [NC]";
        //     $new_rules[] = "    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301,NE]";
        //     $new_rules[] = "</IfModule>";
        //     $new_rules[] = "#SitelockForceHTTPSRulesEnd";
        // }

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
            $new_rules[] = "<FilesMatch \"\.(phtml|phar|cgi|pl|py|asp|aspx|jsp)$\">";
            $new_rules[] = '    <IfModule mod_authz_core.c>';
            $new_rules[] = '        Require all denied';
            $new_rules[] = '    </IfModule>';
            $new_rules[] = '    <IfModule !mod_authz_core.c>';
            $new_rules[] = '        Order allow,deny';
            $new_rules[] = '        Deny from all';
            $new_rules[] = '    </IfModule>';
            $new_rules[] = '</FilesMatch>';
            $new_rules[] = '#SitelockPHPExecutionRulesStart';
        }

        $htaccess_content = preg_replace('/# SitelockRulesStart.*?# SitelockRulesEnd/s', '', $htaccess_content);
        $htaccess_content .= "# SitelockRulesStart\n" . implode("\n", $new_rules) . "\n# SitelockRulesEnd";

        file_put_contents($this->htaccess_path, $htaccess_content);
        if (!file_exists($this->uploads_htaccess_path)) {
            $default_rules = '# File created by Sitelock Security Plugin'. "\n";
            if ((!filesystem_is_writable(dirname($this->uploads_htaccess_path))) || !file_put_contents($this->uploads_htaccess_path, $default_rules) !== false) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible"><p>Permission denied for the plugin. Unable to create the <code>uploads/.htaccess</code> file. The "Limit PHP Execution" feature has been disabled.</p></div>';
                });
                unset($this->settings['blocked_directories']);
                update_option('sitelock_security_settings', $this->settings);

                return;
            }
        } elseif (!filesystem_is_writable($this->uploads_htaccess_path)) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>Permission denied for the plugin. Unable to update the <code>uploads/.htaccess</code> file. The "Limit PHP Execution" feature has been disabled.</p></div>';
            });
            unset($this->settings['blocked_directories']);
            update_option('sitelock_security_settings', $this->settings);

            return;
        }

        $uploads_htaccess_content = file_get_contents($this->uploads_htaccess_path);
        $uploads_htaccess_rules   = [];

        if (!empty($this->settings['blocked_directories'])) {
            $uploads_htaccess_rules[] = '#SitelockBlockedDirectoriesRulesStart';
            $uploads_htaccess_rules[] = '<IfModule mod_rewrite.c>';
            $uploads_htaccess_rules[] = '    RewriteEngine On';
            $uploads_htaccess_rules[] = '    RewriteCond %{REQUEST_FILENAME} -f';
            $uploads_htaccess_rules[] = "    RewriteCond %{REQUEST_FILENAME} \.(php[0-9]?|phtml|phar|cgi|pl|py|asp|aspx|jsp)$ [NC]";
            $uploads_htaccess_rules[] = "    RewriteRule \.php$ - [F,L]";
            $uploads_htaccess_rules[] = '</IfModule>';
            $uploads_htaccess_rules[] = '# Extra protection: Deny access via FilesMatch (works with mod_authz_core)';
            $uploads_htaccess_rules[] = "<FilesMatch \"\.(php[0-9]?|phtml|phar|cgi|pl|py|asp|aspx|jsp)$\">";
            $uploads_htaccess_rules[] = '    Require all denied';
            $uploads_htaccess_rules[] = '</FilesMatch>';
            $uploads_htaccess_rules[] = '#SitelockBlockedDirectoriesRulesEnd';
        }

        $uploads_htaccess_content = preg_replace('/#SitelockRulesStart.*?#SitelockRulesEnd/s', '', $uploads_htaccess_content);
        $uploads_htaccess_content .= "#SitelockRulesStart\n" . implode("\n", $uploads_htaccess_rules) . "\n#SitelockRulesEnd";
        if (file_exists($this->uploads_htaccess_path) && filesystem_is_writable($this->uploads_htaccess_path)) {
            file_put_contents($this->uploads_htaccess_path, $uploads_htaccess_content);
        }
    }

    public function update_security_rules($old_value, $new_value)
    {
        if (!$old_value) {
            $old_value = [];
        }
        if (!$new_value) {
            $new_value = [];
        }

        $disabled_features = array_diff_key($old_value, $new_value);

        if (!empty($disabled_features)) {
            $this->remove_apache_security_rules(array_keys($disabled_features));
        }

        $this->apply_apache_security();
    }

    private function remove_apache_security_rules($features)
    {
        if (!file_exists($this->htaccess_path) || !filesystem_is_writable($this->htaccess_path)) {
            return;
        }

        $htaccess_content = file_get_contents($this->htaccess_path);

        // if (in_array('force_https', $features)) {
        //     $htaccess_content = preg_replace('/#SitelockForceHTTPSRulesStart.*?#SitelockForceHTTPSRulesEnd/s', '', $htaccess_content);
        // }

        if (in_array('disable_dir_listing', $features)) {
            $htaccess_content = preg_replace('/#SitelockDirectoryListingRulesStart.*?#SitelockDirectoryListingRulesEnd/s', '', $htaccess_content);
        }

        if (in_array('limit_php_execution', $features)) {
            $htaccess_content = preg_replace('/#SitelockPHPExecutionRulesStart.*?#SitelockPHPExecutionRulesEnd/s', '', $htaccess_content);
        }

        if (in_array('xss_sqli_protection', $features)) {
            $htaccess_content = preg_replace('/#SitelockXssSqliRulesStart.*?#SitelockXssSqliRulesEnd/s', '', $htaccess_content);
        }

        file_put_contents($this->htaccess_path, $htaccess_content);
    }
}
