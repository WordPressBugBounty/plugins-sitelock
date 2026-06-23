<?php defined( 'ABSPATH' ) || exit; ?>
<!-- Tab Section -->
<div class="flex justify-between mb-8 page-tabs-sec">
    <nav id="page-tabs-wrapper" class="flex items-center justify-between">
        <!-- Page tabs (desktop) -->
        <ul id="page-tabs">
            <?php
            if (current_user_can('manage_options')) {
                ?>
            <li id="dashboard">
                <span class="inline-flex items-center">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sitelock')); ?>">
                        <span class="inline-flex items-center">
                            <span>
                                <?php echo esc_html($sitelock_language_tokens['var']['securityReport']); ?>
                            </span>
                        </span>
                    </a>
                </span>
            </li>

            <li id="sitelock-settings">
                <span class="inline-flex items-center">
                    <!-- Link -->
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sitelock-settings&tab=connection-to-sitelock')); ?>">
                        <span class="inline-flex items-center">
                            <span>
                                <?php echo esc_html($sitelock_language_tokens['var']['settings']); ?>
                            </span>
                        </span>
                    </a>
                </span>
            </li>

            <li id="sitelock-activity-logs">
                <span class="inline-flex items-center">
                    <!-- Link -->
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sitelock-activity-logs')); ?>">
                        <span class="inline-flex items-center">
                            <span>
                                <?php echo esc_html($sitelock_language_tokens['var']['activityLogs']); ?>
                            </span>
                        </span>
                    </a>
                </span>
            </li>
            <?php
            }
            if (current_user_can('edit_posts')) {
                $sitelock_two_fa_settings = get_option('sitelock_2fa_settings');
                if (!is_array($sitelock_two_fa_settings)) {
                    $sitelock_two_fa_settings = [];
                }
                $sitelock_two_fa_settings['enable_2fa'] = isset($sitelock_two_fa_settings['enable_2fa']) ? $sitelock_two_fa_settings['enable_2fa'] : false;
                if ($sitelock_two_fa_settings['enable_2fa']) {
                    ?>
            <li id="sitelock-your-2fa">
                <span class="inline-flex items-center">
                    <!-- Link -->
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sitelock-your-2fa')); ?>">
                        <span class="inline-flex items-center">
                            <span>
                                <?php echo esc_html($sitelock_language_tokens['var']['your2fa']); ?>
                            </span>
                        </span>
                    </a>
                </span>
            </li>
            <?php
                }
            }
            ?>
        </ul>
    </nav>
    <div class="flex items-center pb-[0.75rem] text-[14px]">
        <p class="mr-2 hidden lg:block text-[14px]">
           <?php echo esc_html($sitelock_language_tokens['give_feedback']['note']); ?>
        </p>
        <a href="<?php echo esc_url('https://wordpress.org/support/plugin/sitelock/reviews/#new-post'); ?>" target="_blank" rel="noopener noreferrer" class="text-[#2161CC] underline" aria-label="<?php echo esc_attr($sitelock_language_tokens['give_feedback']['leaveReview']); ?>">
            <span class="hidden lg:block -ml-1"><?php echo esc_html($sitelock_language_tokens['give_feedback']['leaveReview']); ?></span>
        </a>
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
            let page = 'dashboard'; // Default to 'dashboard'
            switch (params.get('page')) {
                case 'sitelock-settings':
                    page = 'sitelock-settings';
                    break;
                case 'sitelock-activity-logs':
                    page = 'sitelock-activity-logs';
                    break;
                case 'sitelock-your-2fa':
                    page = 'sitelock-your-2fa';
                    break;
                case 'sitelock':
                    page = 'dashboard';
                    break;
                default:
                    page = '';
                    break;
            }

            // Remove 'active' class from all list items
            $('#page-tabs li').removeClass('active');

            // Add 'active' class to the selected page tab
            $('#' + page).addClass('active');
        });

    });
</script>