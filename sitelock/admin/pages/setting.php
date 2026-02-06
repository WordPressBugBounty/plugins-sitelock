<?php
$sitelock_security_enhancements_options = get_option('sitelock_security_settings');
$sitelock_connection_status             = $this->api->auth->get_auth_key();
$sitelock_login_lockout_enabled         = get_option('sitelock_login_lockout_enabled', '0');
$sitelock_login_lockout_max_attempts    = get_option('sitelock_login_lockout_max_attempts', 3);
$sitelock_login_lockout_duration        = get_option('sitelock_login_lockout_duration', 30);
$sitelock_login_lockout_reset_time      = get_option('sitelock_login_lockout_reset_time', 15);
$sitelock_password_strength_enabled     = get_option('sitelock_password_strength_enabled', '0');
$sitelock_password_strength_user_roles  = get_option('sitelock_password_strength_user_roles', []);
$sitelock_force_logout_enabled          = get_option('sitelock_force_logout_enabled', '0');
$sitelock_force_logout_duration         = get_option('sitelock_force_logout_duration', 12);
$sitelock_force_logout_excluded_roles   = get_option('sitelock_force_logout_excluded_roles', []);
$sitelock_language_tokens               = get_language_tokens();
$roles                                  = get_editable_roles();
$sitelock_enabled_roles                 = ($tmp                 = get_option('sitelock_login_logger_roles', [])) && is_array($tmp) ? $tmp : [];
$sitelock_retention_days                = get_option('sitelock_login_logger_retention', 30);
?>

<div class="sitelock-wrapper">
<div class="container my-3">
    <?php include plugin_dir_path(__FILE__) . '../partials/common/sitelock-admin-header.php'; ?>
    <?php include plugin_dir_path(__FILE__) . '../partials/common/sitelock-admin-tabs.php'; ?>
    <div id="sitelock-notification-section">
    <?php
    // Retrieve and display the error message
    $error_message = esc_html(get_transient('sitelock_auth_error'));
$success_message   = esc_html(get_transient('sitelock_auth_success'));
if ($error_message) {
    $message = $error_message;
    $status  = 'error';
    include plugin_dir_path(__FILE__) . '../partials/dashboard/sitelock-admin-notification-banner.php';
    $action = delete_transient('sitelock_auth_error');
}
if ($success_message) {
    $message = $success_message;
    $status  = 'success';
    include plugin_dir_path(__FILE__) . '../partials/dashboard/sitelock-admin-notification-banner.php';
    $action = delete_transient('sitelock_auth_success');
}
?>

    <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This request does not require nonce verification as it is handled securely.
    $settings_updated = isset($_GET['settings-updated']) ? sanitize_text_field(wp_unslash($_GET['settings-updated'])) : false;

if ($settings_updated): ?>
        <div class="banner flex items-center compact type-success mt-5">
            <div class="pr-4">
                <svg fill="none" viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"
                    class="w-6 h-6">
                    <path xmlns="http://www.w3.org/2000/svg"
                        d="M12 0C5.3828 0 0 5.3828 0 12C0 18.6172 5.3828 24 12 24C18.6172 24 24 18.6163 24 12C24 5.38373 18.6172 0 12 0ZM12 22.141C6.40898 22.141 1.85902 17.592 1.85902 12C1.85902 6.40805 6.40898 1.85902 12 1.85902C17.592 1.85902 22.141 6.40805 22.141 12C22.141 17.592 17.591 22.141 12 22.141Z"
                        fill="#00AA6B"></path>
                    <path xmlns="http://www.w3.org/2000/svg"
                        d="M17.5055 7.82644C17.1271 7.48251 16.5388 7.50947 16.193 7.88962L10.523 14.1332L7.78369 11.3484C7.42209 10.9822 6.83466 10.9766 6.46936 11.3372C6.10313 11.697 6.09755 12.2853 6.4582 12.6516L9.88716 16.1372C10.0628 16.3157 10.3008 16.4152 10.5499 16.4152C10.5555 16.4152 10.562 16.4152 10.5675 16.4161C10.8241 16.4105 11.0658 16.3008 11.2377 16.1112L17.5686 9.13987C17.9135 8.75873 17.8856 8.1713 17.5055 7.82644Z"
                        fill="#00AA6B"></path>
                </svg>
            </div>
            <div class="w-full">
                <div class="flex items-center">
                    <div class="w-full"><?php echo esc_html__('Settings Saved Successfully', 'sitelock-wordpress-plugin'); ?></div>
                </div>
            </div>
            <img src="<?php echo esc_url(plugin_dir_url(__DIR__) . 'images/x.svg'); ?>" alt="close"
                class="closebtn pl-2 cursor-pointer" />
        </div>
    <?php endif; ?>
    </div>

    <div class="setting">
        <div class="grid grid-cols-1 lg:grid-cols-12 mt-5">
            <div class="lg:col-span-3 mb-2">
                <ul class="tab-list">
                    <li class="px-4 mb-6 relative tab-setting-title" data-id="connection-to-sitelock"><span
                            class="inner flex items-center gap-2"><a
                                href="<?php echo esc_url(admin_url('admin.php?page=sitelock-settings&tab=connection-to-sitelock')); ?>"><?php echo esc_html__('SiteLock Plan & License', 'sitelock-wordpress-plugin'); ?></a>
                            <div> <img src="<?php echo esc_url(plugin_dir_url(__DIR__) . '/images/right-arrow.png'); ?>"
                                    alt="arrow" class="arrow-img" /> </div>
                        </span>
                        <p class="tab-setting-field-content mt-1">Manage your license key</p>
                    </li>
                    <li class="tab-setting-title px-4 mb-6 relative" data-id="login-security"><span
                            class="inner flex items-center gap-2"><a
                                href="<?php echo esc_url(admin_url('admin.php?page=sitelock-settings&tab=login-security')); ?>"><?php echo esc_html__('Login Security', 'sitelock-wordpress-plugin'); ?></a>
                            <div> <img src="<?php echo esc_url(plugin_dir_url(__DIR__) . '/images/right-arrow.png'); ?>"
                                    alt="arrow" class="arrow-img" /> </div>
                        </span>
                        <p class="tab-setting-field-content mt-1">Manage login and account security</p>
                    </li>
                    <li class="tab-setting-title px-4 mb-6 relative" data-id="security-enhancements"><span
                            class="inner flex items-center gap-2"><a
                                href="<?php echo esc_url(admin_url('admin.php?page=sitelock-settings&tab=security-enhancements')); ?>"><?php echo esc_html__('Website Security', 'sitelock-wordpress-plugin'); ?></a>
                            <div> <img src="<?php echo esc_url(plugin_dir_url(__DIR__) . '/images/right-arrow.png'); ?>"
                                    alt="arrow" class="arrow-img" /> </div>
                        </span>
                        <p class="tab-setting-field-content mt-1">Manage site protection settings</p>
                    </li>
                </ul>
            </div>
            <div class="lg:col-span-9 mb-2">
                <div class="tab-content ">
                    <!-- Connection to Sitelock -->
                    <?php include plugin_dir_path(__FILE__) . '../partials/settings/sitelock-admin-connection.php'; ?>

                    <!-- Security Enhancements -->
                    <?php include plugin_dir_path(__FILE__) . '../partials/settings/sitelock-admin-security-enhancement.php'; ?>

                    <!-- Login Security -->
                    <?php include plugin_dir_path(__FILE__) . '../partials/settings/sitelock-admin-login-security.php'; ?>

                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    jQuery(document).ready(function ($) {
        $(document).ready(function () {
            // Get the current URL
            const url = new URL(window.location.href);

            // Access the query parameters
            const params = new URLSearchParams(url.search);

            // Get individual parameters
            const tab = params.get('tab');

            // Find the tab with the matching 'data-id' attribute
            const $tabToClick = $(`.tab-setting-title[data-id="${tab}"]`);

            // If the element exists, simulate a click
            if ($tabToClick.length) {
                $(`.tab-setting-title[data-id="${tab}"] span`).addClass('active');
                const $element = $(`#${$tabToClick.data('id')}`);
                // Show the selected tab
                $element.removeClass('hidden').addClass('block');
            } else {
                $('.tab-setting-title[data-id="connection-to-sitelock"] span').addClass('active');
                const $defaultElement = $('#connection-to-sitelock');
                // Show the selected tab
                $defaultElement.removeClass('hidden').addClass('block');
            }
        });


        $('.lightswitch').click(function () {
            var input = $(this).find('input').first();
            input.prop('checked', !input.prop('checked'));
        });

        $('.option-info label').click(function () {
            var input = $(this).find('input').first();
            input.prop('checked', !input.prop('checked'));
        });
        
        $('.closebtn').click(function () {
            $('.banner').addClass('hidden');
        });
    });
</script>