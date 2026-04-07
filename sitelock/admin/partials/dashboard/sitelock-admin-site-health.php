<?php defined( 'ABSPATH' ) || exit; ?>

<div id="site-health" class="simple-box border-[#DDDDDD] bg-[#fff] mb-6">

    <!-- Standard header -->
    <div class="header flex items-center justify-between px-3 sm:px-5 py-3">

    <div class="flex items-center gap-3">
    <!-- Title + Free Badge grouped -->
        <h4 class="title">
        <?php echo esc_html($sitelock_language_tokens['var']['siteHealth']); ?>
            </h4>
            <?php if (!$sitelock_connection_status): ?>
                <span class="free-label"><?php echo esc_html($sitelock_language_tokens['var']['free']) ?></span>
            <?php endif ?>
        </div>
    
        <div class="flex items-center gap-6">
            <a href="<?php echo esc_url(sitelock_get_redirect_url('whatIsThis')); ?>" target="_blank" rel="noopener noreferrer"
                class="link underline"><?php echo esc_html($sitelock_language_tokens['var']['whatIsThis']); ?></a>
            <img src="<?php echo esc_url(plugin_dir_url(__DIR__) . '../images/down-arrow.svg'); ?>"
                alt="<?php echo esc_attr($sitelock_language_tokens['var']['downArrow']); ?>"
                class="header-toggle cursor-pointer img-arrow arrow-rotate"
                data-id="<?php echo esc_attr('site-health-card'); ?>" />
        </div>
    </div>

    <div id="site-health-card" class="collapsed expanded">
        <!-- Report Body Section -->
        <div class="pb-6 <?php echo esc_attr($sitelock_connection_status ? 'px-5' : 'sm:px-16 text-center'); ?> ">
            <!-- Dial indicator -->
            <div class="dial mt-3 mb-6">
            <svg width="269" height="139" viewBox="0 0 269 139" fill="#6a6a6a" xmlns="http://www.w3.org/2000/svg">
                <path
                d="M264.733 139C262.346 139 260.224 136.879 260.224 134.492C260.224 105.323 249.879 76.6837 231.045 54.4089C229.453 52.5527 229.719 49.6358 231.576 48.0447C233.432 46.4537 236.35 46.7188 237.942 48.5751C258.102 72.7061 268.978 103.201 268.978 134.492C269.243 137.144 267.121 139 264.733 139Z"
                fill="<?php echo esc_attr($sitelock_connection_status ? '#DB1010' : '#DDDDDD'); ?>" />
                <path
                d="M222.374 44C221.309 44 220.244 43.7285 219.18 42.9141C198.153 23.0974 170.738 11.153 142.259 9.25275C139.863 8.98128 138 6.80958 138 4.36642C138.266 1.92325 140.395 -0.248454 143.057 0.0230088C173.666 1.92325 202.944 14.9535 225.567 36.1276C227.431 37.7564 227.431 40.7424 225.834 42.6427C224.769 43.4571 223.438 44 222.374 44Z"
                fill="<?php echo esc_attr($sitelock_connection_status ? '#FD8731' : '#DDDDDD'); ?>" />
                <path
                d="M46.6391 44C45.3046 44 44.2371 43.4604 43.1695 42.651C41.5682 40.7624 41.5682 37.7946 43.4364 36.1758C43.9702 35.6363 44.504 35.0967 45.0377 34.5571C67.4561 14.3222 96.2796 1.91146 126.171 0.0228677C128.573 -0.246931 130.708 1.91146 130.975 4.33964C131.242 6.76783 129.373 8.92621 126.704 9.19601C98.9485 11.0846 71.9931 22.4161 51.1761 41.302C50.6423 41.8416 50.1086 42.1114 49.5748 42.651C48.7741 43.4604 47.7066 44 46.6391 44Z"
                fill="<?php echo esc_attr($sitelock_connection_status ? '#FFD601' : '#DDDDDD'); ?>" />
                <path
                d="M4.51205 139C2.12332 139 0 136.879 0 134.492C0 103.201 11.1474 72.4409 31.0536 48.5751C32.646 46.7188 35.5656 46.4537 37.4235 48.0447C39.2814 49.6358 39.5468 52.5527 37.9543 54.4089C19.1099 76.9489 8.75869 105.323 8.75869 134.492C9.02411 137.144 6.90079 139 4.51205 139Z"
                fill="<?php echo esc_attr($sitelock_connection_status ? '#00AA6B' : '#DDDDDD'); ?>" />
                <path
                d="M245.685 138C244.926 138 244.42 137.494 244.42 136.736C244.42 76.0709 195.078 26.7805 134.349 26.7805C73.6204 26.7805 24.5313 76.0709 24.5313 136.736C24.5313 137.494 24.0252 138 23.2661 138C22.507 138 22.0009 137.494 22.0009 136.736C21.7479 74.5543 72.3552 24 134.349 24C196.596 24 246.95 74.5543 246.95 136.483C247.203 137.494 246.444 138 245.685 138Z"
                fill="#EEEEEE" />
                <!-- This is the path that the circle will follow. It is an invisible line in the SVG -->
                <path id="pathElement" ref="pathElement"
                d="M4.1,135.8c0-72,58-131.3,130-131.3s130.7,59.3,130.7,131.3" class="opacity-0" />
            </svg>

            <?php
                if (!$sitelock_connection_status) {
                    $sitelock_health_stage = 'requiresActivation';
                } elseif (!isset($sitelock_site_info['healthStage']) || !isset($sitelock_language_tokens['health_map_descriptions'][$sitelock_site_info['healthStage']])) {
                    $sitelock_health_stage = 'unavailable';
                } else {
                    $sitelock_health_stage = $sitelock_site_info['healthStage'];
                }
        ?>

            <!-- Circle element -->
            <?php if ($sitelock_connection_status) {
                echo '<span class="circle" id="circleElement"></span>';
            } ?>

            <!-- Icon and text inside indicator dial -->
            <div class="z-20 absolute left-0 bottom-0 top-12 text-center w-full leading-none">
                <div class="mt-3">
                <div class="icon">
                    <!-- Exclamation / triangle icon  -->
                    <div>
                    <?php if ($sitelock_connection_status) { ?>

                    <?php if ($sitelock_site_info && $sitelock_site_info['healthStage'] == 'healthy') { ?>
                        <img src="<?php echo esc_url(plugin_dir_url(__DIR__) . '../images/health.svg'); ?>" width="24" height="34" alt="" class="mx-auto" />
                    <?php } elseif ($sitelock_site_info && $sitelock_site_info['healthStage'] == 'analyzing') { ?>
                        <img src="<?php echo esc_url(plugin_dir_url(__DIR__) . '../images/awaiting.svg'); ?>" width="24" height="34" alt="" class="mx-auto" />
                    <?php } elseif ($sitelock_site_info && ($sitelock_site_info['healthStage'] == 'compromised' || $sitelock_site_info['healthStage'] == 'atRisk' || $sitelock_site_info['healthStage'] == 'impaired')) { ?>
                        <?php
                            if ($sitelock_site_info['healthStage'] == 'compromised') {
                                $sitelock_site_health_color = '#DB1010'; // color for compromised
                            } elseif ($sitelock_site_info['healthStage'] == 'impaired') {
                                $sitelock_site_health_color = '#fd8731'; // color for impaired
                            } else {
                                $sitelock_site_health_color = '#ffd601'; // color for other stages
                            }
                        ?>
                        <svg width="34" height="24" viewBox="0 0 24 21" fill="none"
                        xmlns="http://www.w3.org/2000/svg" class="mx-auto text-yellow">
                        <path
                            d="M23.6908 17.3689L13.8464 1.04273C13.4585 0.399563 12.7511 0 12 0C11.2489 0 10.5414 0.399563 10.1535 1.04278L0.309161 17.3689C-0.0916669 18.0337 -0.103526 18.8666 0.278177 19.5425C0.659974 20.2185 1.37932 20.6384 2.15557 20.6384H21.8444C22.6206 20.6384 23.34 20.2185 23.7218 19.5425C24.1035 18.8665 24.0917 18.0336 23.6908 17.3689ZM22.3612 18.7741C22.2561 18.9602 22.0581 19.0758 21.8444 19.0758H2.15557C1.94187 19.0758 1.74382 18.9602 1.63877 18.7742C1.53368 18.5881 1.53696 18.3588 1.64726 18.1758L11.4917 1.84964C11.5985 1.67259 11.7933 1.56258 12 1.56258C12.2067 1.56258 12.4015 1.67259 12.5083 1.84964L22.3527 18.1758C22.463 18.3588 22.4663 18.5881 22.3612 18.7741Z"
                            fill="<?php echo esc_attr($sitelock_site_health_color); ?>" />
                        <path
                            d="M12.006 6.42871C11.4116 6.42871 10.9478 6.74765 10.9478 7.31305C10.9478 9.03815 11.1507 11.5171 11.1507 13.2422C11.1507 13.6916 11.5422 13.8801 12.0061 13.8801C12.354 13.8801 12.8468 13.6916 12.8468 13.2422C12.8468 11.5171 13.0498 9.0382 13.0498 7.31305C13.0498 6.7477 12.5714 6.42871 12.006 6.42871Z"
                            fill="#000000" />
                        <path
                            d="M12.0216 14.8809C11.3837 14.8809 10.9053 15.3882 10.9053 15.9971C10.9053 16.5915 11.3837 17.1134 12.0216 17.1134C12.6159 17.1134 13.1234 16.5915 13.1234 15.9971C13.1234 15.3882 12.6159 14.8809 12.0216 14.8809Z"
                            fill="#000000" />
                        </svg>
                    <?php } else { ?>
                        <svg width="17" height="27" viewBox="0 0 17 27" fill="none"
                        xmlns="http://www.w3.org/2000/svg" class="mx-auto text-yellow">
                        <path
                            d="M8.50233 22.7644C7.25008 22.7644 6.23504 23.7125 6.23504 24.8822C6.23504 26.0518 7.25008 27 8.50233 27C9.75458 27 10.7696 26.0518 10.7696 24.8822C10.7696 23.7125 9.75458 22.7644 8.50233 22.7644ZM16.8696 6.51274C16.2696 3.31329 13.4554 0.685694 10.0301 0.123142C7.51703 -0.288432 4.96633 0.342376 3.03781 1.85523C1.10707 3.37016 0 5.58724 0 7.93978C0 8.81668 0.761675 9.52813 1.70047 9.52813C2.63927 9.52813 3.40094 8.81668 3.40094 7.93978C3.40094 6.52826 4.06519 5.19739 5.22541 4.28843C6.39892 3.3681 7.88903 2.99789 9.44117 3.25228C11.4848 3.58732 13.1632 5.15396 13.5219 7.06185C13.9093 9.13104 12.9041 11.1154 10.9601 12.1164C8.35623 13.4576 6.80191 15.7854 6.80191 18.3447V18.5288C6.80191 19.4057 7.56358 20.0251 8.50238 20.0251C9.44117 20.0251 10.2028 19.2216 10.2028 18.3447C10.2028 16.9601 11.0996 15.6716 12.603 14.8981C15.7891 13.257 17.5029 9.88799 16.8696 6.51274Z"
                            fill="black" />
                        </svg>
                    <?php } ?>
                    <?php } else { ?>
                        <svg width="17" height="27" viewBox="0 0 17 27" fill="none"
                        xmlns="http://www.w3.org/2000/svg" class="mx-auto text-yellow">
                        <path
                            d="M8.50233 22.7644C7.25008 22.7644 6.23504 23.7125 6.23504 24.8822C6.23504 26.0518 7.25008 27 8.50233 27C9.75458 27 10.7696 26.0518 10.7696 24.8822C10.7696 23.7125 9.75458 22.7644 8.50233 22.7644ZM16.8696 6.51274C16.2696 3.31329 13.4554 0.685694 10.0301 0.123142C7.51703 -0.288432 4.96633 0.342376 3.03781 1.85523C1.10707 3.37016 0 5.58724 0 7.93978C0 8.81668 0.761675 9.52813 1.70047 9.52813C2.63927 9.52813 3.40094 8.81668 3.40094 7.93978C3.40094 6.52826 4.06519 5.19739 5.22541 4.28843C6.39892 3.3681 7.88903 2.99789 9.44117 3.25228C11.4848 3.58732 13.1632 5.15396 13.5219 7.06185C13.9093 9.13104 12.9041 11.1154 10.9601 12.1164C8.35623 13.4576 6.80191 15.7854 6.80191 18.3447V18.5288C6.80191 19.4057 7.56358 20.0251 8.50238 20.0251C9.44117 20.0251 10.2028 19.2216 10.2028 18.3447C10.2028 16.9601 11.0996 15.6716 12.603 14.8981C15.7891 13.257 17.5029 9.88799 16.8696 6.51274Z"
                            fill="black" />
                        </svg>
                    <?php } ?>

                    </div>
                </div>

                <!-- Site Health heading -->
                <div class="mt-3 text-[18px] leading-[14px] text-[#828282]">
                    <?php echo esc_html($sitelock_language_tokens['var']['siteHealth']); ?>
                </div>

                <!-- Status -->
                <div class="text-[24px] text-dark mt-1">
                    <?php echo esc_html($sitelock_connection_status && $sitelock_site_info ? ($sitelock_language_tokens['service_status_tokens'][$sitelock_site_info['healthStage']] ?? 'N/A') : $sitelock_language_tokens['var']['requiresActivation']); ?>
                </div>
                </div>
            </div>
            </div>

            <div class="rich-text text-[#333333]">
            <?php echo esc_html($sitelock_language_tokens['health_map_descriptions'][$sitelock_health_stage]); ?>

            </div>

        </div>

        <?php if (!$sitelock_connection_status) { ?>
            <div class="bg-gradient-to-b from-[#F7F7F7] to-[#FFFFFF] py-4 px-10">
            <div class="flex items-center justify-center gap-5">
                <a href="<?php echo esc_url(sitelock_get_redirect_url('signup') . admin_url()); ?>" target="_blank" rel="noopener noreferrer"
                    class="w-[163px] btn-primary">
                    <?php echo esc_html($sitelock_language_tokens['var']['activateFreeAccount']); ?>
                </a>
                <a href="<?php echo esc_url(sitelock_get_redirect_url('comparePlan')); ?>" target="_blank" rel="noopener noreferrer"
                    class="w-[163px] btn-secondary">
                    <?php echo esc_html($sitelock_language_tokens['var']['comparePlans']); ?>
                </a>
            </div>
        </div>

        <?php } ?>

    </div>

</div>

<script>
jQuery(document).ready(function($) {
    $('.header-toggle').click(function() {
        const content = $(this).data('id');
        const $element = $('#' + content);
        $element.toggleClass('expanded');
        $(this).toggleClass('arrow-rotate');
    });
});
</script>