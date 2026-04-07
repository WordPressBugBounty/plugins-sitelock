<div class="<?php echo esc_attr($sitelock_token_last_key == $sitelock_i ? 'not-last-child:border-b' : 'border-b') ?> border-[#EEEEEE] px-3 sm:px-0">
    <div class="grid grid-cols-12 gap-2 md:gap-3 py-[10px]">
        <!-- Plus/minus toggle button -->
        <div class="c1 flex items-center col-span-5">
            <div>
                <button type="button" class="toggle mr-3" data-id="<?php echo esc_attr($sitelock_list) ?>">
                    <span><span></span> </span>
                </button>
            </div>
            <h3 href="#" class="heading block sm:flex items-center w-full gap-2">
                <?php
                $sitelock_unactivated_service_title = $sitelock_language_tokens['service_title_tokens'][$sitelock_list];
// Only show FREE for spam_scan and ssl_scan
$sitelock_show_free_badge = in_array($sitelock_list, ['spam_scan', 'ssl_scan']) && !$sitelock_connection_status;
?>
                <?php echo esc_html($sitelock_unactivated_service_title); ?>
                <?php if ($sitelock_show_free_badge): ?>
                    <span class="free-label"><?php echo esc_html($sitelock_language_tokens['var']['free']) ?></span>
                <?php endif; ?>
            </h3>
        </div>

        <!-- Heading -->
        <div class="c2 flex items-center col-span-4 ">
            <!-- Standard color circle -->
            <div class="pr-2">
                <span class="icon circle w-5 activation-required"></span>
            </div>
            <!-- Text -->
            <span class="capitalize analyzing status"><?php echo esc_html($sitelock_language_tokens['var']['requiresActivation']) ?></span>
        </div>

        <!-- Learn Moren / Close button -->
        <div class="flex items-center col-span-3">
                <button type="button" class="toggle-learn-more learn-more-box btn-secondary w-[112px] h-[32px] px-4" data-id="<?php echo esc_attr($sitelock_list); ?>">
                    <?php echo esc_html($sitelock_language_tokens['var']['learnMore']); ?>
                </button>
        </div>
    </div>

    <!-- Description -->

    <div id="<?php echo esc_attr($sitelock_list) ?>" class="collapsed">
        <div class="grid grid-cols-12 gap-4 mb-4">
            <?php if ($sitelock_list === 'scanning') : ?>
                 <div
                    class="col-span-12 md:col-span-6 ml-10 md:mx-7">
                    <?php include(plugin_dir_path(__FILE__) . 'sitelock-admin-security-scan/sitelock-security-scan-malware.php'); ?>
                </div>
               <div
                    class="col-span-12 md:col-span-6 ml-10">
                    <?php include(plugin_dir_path(__FILE__) . 'sitelock-admin-security-scan/sitelock-security-scan-file.php'); ?>
                </div>

                <div
                    class="col-span-12 md:col-span-6 ml-10 md:mx-7">
                    <?php include(plugin_dir_path(__FILE__) . 'sitelock-admin-security-scan/sitelock-security-scan-vulnerability.php'); ?>
                </div>
                <div
                    class="col-span-12 md:col-span-6 ml-10">
                    <?php include(plugin_dir_path(__FILE__) . 'sitelock-admin-security-scan/sitelock-security-scan-db.php'); ?>
                </div>

                <div class="col-span-12">
                    <div class="ml-10 flex justify-start items-center gap-5">
                        <a href="<?php echo esc_url('https://www.sitelock.com/pricing'); ?>" target="_blank"
                            class="btn-primary w-[112px] h-[32px]">
                            <?php echo esc_html($sitelock_language_tokens['var']['pickPlan']); ?> </a>

                        <p class="text-[14px]"><?php echo esc_html($sitelock_language_tokens['var']['requiredPlanForSecurityScan']); ?></p>

                    </div>
                </div>

                <!-- Add other scanning services similarly -->
            <?php else : ?>
                <div class="col-span-12">
                    <p class="mx-10 text-[14px] leading-[150%] mb-4 font-normal text-[#6A6A6A]">
                        <?php echo esc_html($sitelock_language_tokens['cloud_services_description'][$sitelock_list]); ?>
                    </p>

                    <div class="ml-10 flex justify-start items-center gap-5">
                        <a href="<?php echo esc_url('https://www.sitelock.com/pricing'); ?>" target="_blank"
                            class="btn-primary w-[112px] h-[32px]">
                            <?php echo esc_html($sitelock_language_tokens['var']['pickPlan']); ?> </a>

                        <p class="text-[14px]"><?php echo esc_html($sitelock_language_tokens['var']['requiredPlan']); ?></p>

                    </div>

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>