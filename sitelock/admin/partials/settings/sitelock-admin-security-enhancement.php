<div id="security-enhancements" class="hidden">

    <div class="simple-box border-[#DDDDDD] bg-[#fff] mb-5 p-8 min-h-full xl:min-h-[500px]">
        <?php
    // Use get_home_path() if available ensuring we check the same location where the file is created.
    // Fallback to ABSPATH.
    $sitelock_home_path = function_exists('get_home_path') ? get_home_path() : ABSPATH;
    $sitelock_htaccess_path = $sitelock_home_path . '.htaccess';
        $sitelock_server_software = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : '';
        $sitelock_show_hardening_form       = true;
        if (stripos($sitelock_server_software, 'apache') !== false) {
            // Check if .htaccess exists
            if (file_exists($sitelock_htaccess_path)) {
                // Check if .htaccess is not writable
                if (!sitelock_filesystem_is_writable($sitelock_htaccess_path)) {
                    $sitelock_show_hardening_form                = false;
                    $sitelock_warning_message = '<strong>Warning:</strong> The <code>.htaccess</code> file is not writable. Please set its permissions to 644 (or 666 if you want the plugin to edit it) and ensure your web server has write access.';
                    include plugin_dir_path(__FILE__) . '../common/sitelock-notice-warning-template.php';
                }
            }
            // If .htaccess does not exist, check if we can create it (check dir permissions)
            else {
                if (!sitelock_filesystem_is_writable(dirname($sitelock_htaccess_path))) {
                    $sitelock_show_hardening_form = false;
                    $sitelock_warning_message = '<strong>Warning:</strong> The <code>.htaccess</code> file does not exist, and the directory is not writable. Please verify permissions for the root directory.';
                    include plugin_dir_path(__FILE__) . '../common/sitelock-notice-warning-template.php'; 
                }
            }
        }
        // Check if Nginx is being used
        elseif (stripos($sitelock_server_software, 'nginx') !== false) {
            $sitelock_show_hardening_form                = false;
            $sitelock_warning_message = '<strong>WP Hardening Notice:</strong> Your server is running Nginx, which does not use a .htaccess file. Security settings related to .htaccess will not work. If you want hardening features, please add the following rules manually in your Nginx configuration:';
            $sitelock_is_nginx        = true;
            include plugin_dir_path(__FILE__) . '../common/sitelock-notice-warning-template.php';
        } else {
            $sitelock_show_hardening_form                = false;
            $sitelock_warning_message = "<strong>Note:</strong> Your web server (<code>{$sitelock_server_software}</code>) is not recognized as Apache. The .htaccess file may not be supported, depending on your server configuration.";
            include plugin_dir_path(__FILE__) . '../common/sitelock-notice-warning-template.php';
        }
        if ($sitelock_show_hardening_form) {
            ?>
        <div class="w-full xl:w-[70%]">
        <h3 class="box-title mb-8">Website Security</h3>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="sitelock_security_form_data">
        <input type="hidden" name="tab" value="sitelock_website_security">
            <?php
                wp_nonce_field('sitelock_website_security_action', 'sitelock_website_security_nonce'); ?>
            <div class="security-options">

                <!-- <div class="flex mb-6">
                    <div class="lightswitch">
                        <input type="checkbox" id="force_https" name="sitelock_security_settings[force_https]" value="1"
                            <?php //echo isset($sitelock_security_enhancements_options['force_https']) && $sitelock_security_enhancements_options['force_https'] == 1 ? 'checked' : ""?> />
                        <span class="switch"><span></span></span>
                    </div>
                    <div class="option-info ml-2">
                        <label for="force_https" class="cursor-pointer">
                            <h2 class="tab-title mb-1">Force HTTPS</h2>
                            <p class="tab-content-field-content">Ensure all traffic is forced to use HTTPS for secure communication</p>
                        </label>
                    </div>
                </div> -->

                <div class="flex mb-6">
                    <div class="lightswitch">
                        <input type="checkbox" id="disable-directory-listing" name="sitelock_security_settings[disable_dir_listing]" value="1"
                            <?php echo isset($sitelock_security_enhancements_options['disable_dir_listing']) && $sitelock_security_enhancements_options['disable_dir_listing'] == 1 ? 'checked' : '' ?> />
                        <span class="switch"><span></span></span>
                    </div>
                    <div class="option-info ml-2">
                        <label for="disable-directory-listing" class="cursor-pointer">
                            <h2 class="tab-title mb-1"><?php echo esc_html($sitelock_language_tokens['website_security_enhancements_list']['disableDirectoryListing']) ?></h2>
                            <p class="tab-content-field-content"><?php echo esc_html($sitelock_language_tokens['website_security_enhancements_description']['disableDirectoryListing']) ?></p>
                        </label>
                    </div>
                </div>

                <div class="flex mb-6">
                    <div class="lightswitch">
                        <input type="checkbox" id="deny-access-to-unsafe-script-extensions" name="sitelock_security_settings[limit_php_execution]" value="1"
                            <?php echo isset($sitelock_security_enhancements_options['limit_php_execution']) && $sitelock_security_enhancements_options['limit_php_execution'] == 1 ? 'checked' : '' ?> />
                        <span class="switch"><span></span></span>
                    </div>
                    <div class="option-info ml-2">
                        <label for="deny-access-to-unsafe-script-extensions" class="cursor-pointer">
                            <h2 class="tab-title mb-1"><?php echo esc_html($sitelock_language_tokens['website_security_enhancements_list']['limitPhpExecution']) ?></h2>
                            <p class="tab-content-field-content"><?php echo esc_html($sitelock_language_tokens['website_security_enhancements_description']['limitPhpExecution']) ?></p>
                        </label>
                    </div>
                </div>

                <div class="flex mb-6">
                    <div class="lightswitch">
                        <input type="checkbox" id="xss-sqli-protection" name="sitelock_security_settings[xss_sqli_protection]" value="1"
                            <?php echo isset($sitelock_security_enhancements_options['xss_sqli_protection']) && $sitelock_security_enhancements_options['xss_sqli_protection'] == 1 ? 'checked' : '' ?> />
                        <span class="switch"><span></span></span>
                    </div>
                    <div class="option-info ml-2">
                        <label for="xss-sqli-protection" class="cursor-pointer">
                            <h2 class="tab-title mb-1"><?php echo esc_html($sitelock_language_tokens['website_security_enhancements_list']['xssSqliProtection']) ?></h2>
                            <p class="tab-content-field-content"><?php echo esc_html($sitelock_language_tokens['website_security_enhancements_description']['xssSqliProtection']) ?></p>
                        </label>
                    </div>
                </div>

                <div class="flex mb-6">
                    <div class="lightswitch">
                        <input type="checkbox" name="sitelock_security_settings[blocked_directories]" id="blocked-directories-toggle" value="1"
                            <?php echo isset($sitelock_security_enhancements_options['blocked_directories']) && $sitelock_security_enhancements_options['blocked_directories'] == 1 ? 'checked' : '' ?> />
                        <span class="switch"><span></span></span>
                    </div>
                    <div class="option-info ml-2">
                        <label for="blocked-directories-toggle" class="cursor-pointer">
                            <h2 class="tab-title mb-1"><?php echo esc_html($sitelock_language_tokens['website_security_enhancements_list']['hardenWritableDirs']) ?></h2>
                            <p class="tab-content-field-content"><?php echo wp_kses_post($sitelock_language_tokens['website_security_enhancements_description']['hardenWritableDirs']) ?></p>
                        </label>
                    </div>
                </div>
                <input type="hidden" id="sitelock-selected-dirs" name="sitelock_blocked_directories_json" />
            </div>

            <div class="mb-7">
                <button type="submit" class="w-[131px] h-[32px] btn-primary">Save
                    Changes</button>
            </div>

        </form>
        </div>
        <?php
        }
        ?>

    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $('.lightswitch input').change(function () {
            $(this).prop('checked', !$(this).prop('checked'));
        });
    });
</script>