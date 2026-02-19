<?php
$sitelock_security_enhancements_options = get_option('sitelock_security_settings', []);
$sitelock_security_2fa_options          = get_option('sitelock_2fa_settings', []);
$sitelock_login_lockout_enabled         = get_option('sitelock_login_lockout_enabled');
$sitelock_password_strength_enabled     = get_option('sitelock_password_strength_enabled', '0');
$sitelock_force_logout_enabled          = get_option('sitelock_force_logout_enabled');
$sitelock_connection_status             = $this->api->auth->get_auth_key();
$sitelock_site_info                     = $this->wpslp_data['site']                 ?? null;
$sitelock_services                      = $this->wpslp_data['services']             ?? [];
$sitelock_feature_status_sort_map_global                   = $this->wpslp_data['featureStatusSortMap'] ?? [];
$sitelock_language_tokens               = sitelock_get_language_tokens();
uasort($sitelock_services, function ($a, $b) use ($sitelock_feature_status_sort_map_global) {
    return (isset($a['overallStatus']) ? $sitelock_feature_status_sort_map_global[$a['overallStatus']] : $sitelock_feature_status_sort_map_global[$a['status']]) <=> (isset($b['overallStatus']) ? $sitelock_feature_status_sort_map_global[$b['overallStatus']] : $sitelock_feature_status_sort_map_global[$b['status']]);
});
?>
<div class="sitelock-wrapper ">
<div class="container my-3">
    <?php include plugin_dir_path(__FILE__) . '../partials/common/sitelock-admin-header.php'; ?>
    <?php include plugin_dir_path(__FILE__) . '../partials/common/sitelock-admin-tabs.php'; ?>
    <div id="sitelock-notification-section">
    <?php
    // Retrieve and display the error message
    $sitelock_error_message = get_transient('sitelock_auth_error');
    if ($sitelock_error_message) {
        $sitelock_message = esc_html($sitelock_error_message); // Escaping the error message
        $sitelock_status = 'error';
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

            <?php 
            if (
                empty($this->wpslp_partner_data['customer_support_options']['value']['support_contact_info']['action']) || isset($this->wpslp_partner_data['customer_support_options']['value']['support_contact_info']['action']) &&
                $this->wpslp_partner_data['customer_support_options']['value']['support_contact_info']['action'] !== "hide"
            ) :
                include plugin_dir_path(__FILE__) . '../partials/dashboard/sitelock-admin-need-help.php'; 
            endif;
            ?>

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

            // Calculate percentage for circle based on site health score
            function calculatePercentage(siteInfo) {
                let p = null;

                if (siteInfo?.['healthStage'] === 'analyzing' || typeof siteInfo?.['healthStage'] === 'undefined' || siteInfo?.['healthStage'] === null) {
                    return null;
                }

                // Base percentages for each category
                // This calculates the percentage on the curve to put the circle
                // 0% is on the bottom-left of the dial, 50% is in the middle and 100% is in the bottom-right
                // The percentages are given with some margins so that they are never exactly at the start or end of a colored section
                const basePercentages = {
                    healthy: 11.5, // Healthy will show right in the middle of the green section
                    atRisk: [31, 43], // At Risk will show on a sliding scale in the yellow section
                    impaired: [56, 70], // Impaired will show on a sliding scale in the orange section
                    compromised: [82, 95], // Compromised will show on a sliding scale in the red section
                };

                // Calculate the percentage based on the category
                if (siteInfo?.['healthStage'] && siteInfo?.['healthStage'] !== 'unavailable') {
                    const stageKey = siteInfo?.['healthStage'] === null ? '' : siteInfo?.['healthStage'];

                    const healthScoreMap = {
                        // https://confluence.comodoca.net/display/SLDEV/Site+Health+Metric
                        // Updated based on [SE-2242]
                        'analyzing': { 'min': -1, 'max': -1 },
                        'healthy': { 'min': 0.0, 'max': 0.0 },
                        'atRisk': { 'min': 0.01, 'max': 33.32 },
                        'impaired': { 'min': 33.33, 'max': 66.65 },
                        'compromised': { 'min': 66.66, 'max': 100.0 }
                    };

                    if (siteInfo?.['healthStage'] === 'healthy') {
                        p = basePercentages['healthy'];
                    } else if (!healthScoreMap || !healthScoreMap[stageKey] || !basePercentages[stageKey]) {
                        return null;
                    } else {
                        const range = healthScoreMap[stageKey];
                        const basePercentage = basePercentages[stageKey];
                        const categoryRange = range.max - range.min;
                        const scorePositionInRange = (siteInfo?.['healthScore'] - range.min) / categoryRange;
                        p = basePercentage[0] + (basePercentage[1] - basePercentage[0]) * scorePositionInRange;
                    }
                }
                return p;
            }

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

            const siteInfo = <?php echo json_encode($sitelock_site_info); ?>;
            const healthScore = calculatePercentage(siteInfo);
            
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