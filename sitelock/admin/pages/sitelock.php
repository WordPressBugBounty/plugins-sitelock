<?php
$sitelock_security_enhancements_options = get_option('sitelock_security_settings', []);
$sitelock_security_2fa_options          = get_option('sitelock_2fa_settings', []);
$sitelock_login_lockout_enabled         = get_option('sitelock_login_lockout_enabled');
$sitelock_password_strength_enabled     = get_option('sitelock_password_strength_enabled', '0');
$sitelock_force_logout_enabled          = get_option('sitelock_force_logout_enabled');
$sitelock_connection_status             = $this->api->auth->get_auth_key();
$sitelock_site_info                     = $this->wpslp_data['site']                 ?? null;
$sitelock_services                      = $this->wpslp_data['services']             ?? [];
$featureStatusSortMap                   = $this->wpslp_data['featureStatusSortMap'] ?? [];
$sitelock_language_tokens               = get_language_tokens();
uasort($sitelock_services, function ($a, $b) use ($featureStatusSortMap) {
    return (isset($a['overallStatus']) ? $featureStatusSortMap[$a['overallStatus']] : $featureStatusSortMap[$a['status']]) <=> (isset($b['overallStatus']) ? $featureStatusSortMap[$b['overallStatus']] : $featureStatusSortMap[$b['status']]);
});
?>
<div class="sitelock-wrapper ">
<div class="container my-3">
    <?php include plugin_dir_path(__FILE__) . '../partials/common/sitelock-admin-header.php'; ?>
    <?php include plugin_dir_path(__FILE__) . '../partials/common/sitelock-admin-tabs.php'; ?>
    <div id="sitelock-notification-section">
    <?php
    // Retrieve and display the error message
    $error_message = get_transient('sitelock_auth_error');
    if ($error_message) {
        $message = esc_html($error_message); // Escaping the error message
        $status = 'error';
        include plugin_dir_path(__FILE__) . '../partials/dashboard/sitelock-admin-notification-banner.php';
        $action = delete_transient('sitelock_auth_error');
    }
    ?>
        </div>
    <!-- Report Section -->

    <div id="report-section" class="flex flex-col xl:flex-row mt-5 xl:gap-6">

        <!-- Left Side Section -->

        <div class="col-1 order-2 xl:order-1">

            <?php include plugin_dir_path(__FILE__) . '../partials/dashboard/sitelock-admin-site-health.php'; ?>

            <?php include plugin_dir_path(__FILE__) . '../partials/dashboard/sitelock-admin-login-security.php'; ?>

            <?php include plugin_dir_path(__FILE__) . '../partials/dashboard/sitelock-admin-need-help.php'; ?>

        </div>

        <!-- Right Side Section -->
        <div class="col-2 order-1 xl:order-2">

            <?php include plugin_dir_path(__FILE__) . '../partials/dashboard/sitelock-admin-scan-result.php'; ?>

            <?php include plugin_dir_path(__FILE__) . '../partials/dashboard/sitelock-admin-security-enhancement.php'; ?>

        </div>

    </div>

</div>
</div>

<script>
    jQuery(document).ready(function($) {
        $(document).ready(function() {
            const $pathElement = $('#pathElement');
            const $circleElement = $('#circleElement');
            const circleStyles = {};

            function setCircleStyles(percentage) {
                if (!$pathElement.length || !$circleElement.length) {
                    return;
                }

                // Reset previous border classes
                $circleElement.removeClass('border-red border-orange border-yellow border-green');

                if (percentage > 75) {
                    $circleElement.addClass('border-red');
                } else if (percentage > 50) {
                    $circleElement.addClass('border-orange');
                } else if (percentage > 25) {
                    $circleElement.addClass('border-yellow');
                } else {
                    $circleElement.addClass('border-green');
                }

                const pathLength = $pathElement.get(0).getTotalLength();

                // Calculate the length along the path based on the percentage
                const lengthAtPercentage = (percentage / 100) * pathLength;

                // Get the point at the calculated length
                const point = $pathElement.get(0).getPointAtLength(lengthAtPercentage);

                circleStyles.position = 'absolute';
                circleStyles.left = point.x - 14 + 'px';
                circleStyles.top = point.y - 14 + 'px';

                // Apply the styles to the circle element
                $circleElement.css(circleStyles);
            }

            const healthScore = <?php echo json_encode(isset($sitelock_site_info['healthStage']) && $sitelock_site_info['healthStage'] !== 'analyzing' ? ($sitelock_site_info['healthScore'] ?? 0) : null); ?>;
            if (healthScore === null) {
                $circleElement.css({'display': 'none'});
            } else {
                setCircleStyles(healthScore);
            }
        });

        $('.toggle, .heading, .toggle-learn-more').on('click', function(e) {
            e.preventDefault();
            const contentId = $(this).data('id');
            const $content = $('#' + contentId);
            const $button = $('.toggle[data-id="' + contentId + '"]');
            const $span = $button.find('span span');

            $button.toggleClass('active');
            $content.toggleClass('expanded');

            const buttons = document.querySelectorAll('.toggle-learn-more');
            buttons.forEach(button => {
                

                // Set initial text content based on the current state
                const contentId = button.getAttribute('data-id');
                const $content = $('#' + contentId);
                const $button = $('.toggle[data-id="' + contentId + '"]');
                if ($content.hasClass('expanded') || $button.hasClass('active')) {
                    button.textContent = '<?php echo esc_js($sitelock_language_tokens['var']['close']); ?>';
                } else {
                    button.textContent = '<?php echo esc_js($sitelock_language_tokens['var']['learnMore']); ?>';
                }
            });

            // Adjust span height based on active state
            const isActive = $button.hasClass('active');
            $span.css('height', isActive ? '0px' : '14px');
        });

        $('.closebtn').click(function() {
            $('.banner').addClass('hidden');
        });
    });
</script>