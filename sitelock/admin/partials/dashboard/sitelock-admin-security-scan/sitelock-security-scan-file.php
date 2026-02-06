<div class="flex flex-col justify-between h-full px-3">
    <div class="flex flex-col justify-start">
    <div>
        <h4 class="scan-result-title text-[#000000] mb-[6px]">
            <?php echo esc_html($sitelock_language_tokens['security_scan_list']['fileScan']) ?>
        </h4>
        <p class="text-[14px] font-normal text-[#6A6A6A] security-scan-description"><?php echo esc_html($sitelock_language_tokens['security_scan_description']['fileScan']) ?></p>
    </div>

    <?php if ($sitelock_connection_status && isset($service)) {
        if ($service['smart_scan']['availability'] != 'upgradable' && !$service['smart_scan']['hideDetails'] && $service['smart_scan']['status'] != 'awaitingAllScan') { ?>
        <div>
            <div class="flex justify-between items-center border-b border-[#EEEEEE]">
                <h4 class="my-2 scan-result-title text-[#000000]"><?php echo esc_html($sitelock_language_tokens['var']['status']) ?>
                </h4>
                <p class="my-2 scan-result-data text-[#333333] flex items-center">
                    <span
                        class="icon circle w-5 mr-2 <?php echo esc_html($sitelock_language_tokens['service_status_map'][$service['smart_scan']['status']]) ?>"></span>
                    <?php echo esc_html($sitelock_language_tokens['service_status_tokens'][$service['smart_scan']['status']]) ?>
                </p>
            </div>
            <div class="flex justify-between items-center border-b border-[#EEEEEE]">
                <h4 class="my-2 scan-result-title text-[#000000]"><?php echo esc_html($sitelock_language_tokens['var']['lastScan']) ?>
                </h4>
                <p class="my-2 scan-result-data text-[#333333] last-scan">
                    <?php echo $service['smart_scan']['lastScanDate'] ? esc_html($service['smart_scan']['lastScanDate']) : '' ?>
                </p>
            </div>
            <?php if (!empty($service['smart_scan']['lastGoodScanDate'])): ?>
                <div class="flex justify-between items-center border-b border-[#EEEEEE]">
                    <h4 class="my-2 scan-result-title text-[#000000]">
                        <?php echo esc_html($sitelock_language_tokens['var']['lastGoodScan']) ?>
                    </h4>
                    <p class="my-2 scan-result-data text-[#333333] last-scan">
                        <?php echo esc_html($service['smart_scan']['lastGoodScanDate']) ?>
                    </p>
                </div>
            <?php endif; ?>
            <div class="flex justify-between items-center border-b border-[#EEEEEE]">
                <h4
                    class="my-2 scan-result-title <?php echo isset($service['smart_scan']['lastScan']['num_malicious']) && $service['smart_scan']['lastScan']['num_malicious'] > 0 ? 'text-red' : 'text-[#000000]' ?>">
                    <?php echo esc_html($sitelock_language_tokens['var']['maliciousFilesFound']) ?>
                </h4>
                <p
                    class="my-2 scan-result-data <?php echo isset($service['smart_scan']['lastScan']['num_malicious']) && $service['smart_scan']['lastScan']['num_malicious'] > 0 ? 'text-red' : 'text-[#333333]' ?>">
                    <?php echo isset($service['smart_scan']['lastScan']['num_malicious'])                              && $service['smart_scan']['lastScan']['num_malicious'] ? esc_html($service['smart_scan']['lastScan']['num_malicious']) : 0 ?>
                </p>
            </div>
            <div class="flex justify-between items-center border-b border-[#EEEEEE]">
                <h4
                    class="my-2 scan-result-title text-[#000000]">
                    <?php echo esc_html($sitelock_language_tokens['var']['maliciousFilesCleaned']) ?>
                </h4>
                <p
                    class="my-2 scan-result-data text-[#333333]">
                    <?php echo isset($service['smart_scan']['lastScan']['num_cleaned']) && $service['smart_scan']['lastScan']['num_cleaned'] ? esc_html($service['smart_scan']['lastScan']['num_cleaned']) : 0 ?>
                </p>
            </div>
            <div class="flex justify-between items-center border-b border-[#EEEEEE]">
                <h4
                    class="my-2 scan-result-title text-[#000000] ?>">
                    <?php echo esc_html($sitelock_language_tokens['var']['suspiciousFilesFound']) ?>
                </h4>
                <p
                    class="my-2 scan-result-data text-[#333333] ?>">
                    <?php echo isset($service['smart_scan']['lastScan']['num_suspicious']) && $service['smart_scan']['lastScan']['num_suspicious'] ? esc_html($service['smart_scan']['lastScan']['num_suspicious']) : 0 ?>
                </p>
            </div>
            <div class="flex justify-between items-center not-last-child:border-b border-[#EEEEEE]">
                <h4
                    class="my-2 scan-result-title <?php echo isset($service['smart_scan']['lastScan']['num_review']) && $service['smart_scan']['lastScan']['num_review'] > 0 ? 'text-red' : 'text-[#000000]' ?>">
                    <?php echo esc_html($sitelock_language_tokens['var']['filesUnderReview']) ?>
                </h4>
                <p
                    class="my-2 scan-result-data <?php echo isset($service['smart_scan']['lastScan']['num_review']) && $service['smart_scan']['lastScan']['num_review'] > 0 ? 'text-red' : 'text-[#333333]' ?>">
                    <?php echo isset($service['smart_scan']['lastScan']['num_review'])                              && $service['smart_scan']['lastScan']['num_review'] ? esc_html($service['smart_scan']['lastScan']['num_review']) : 0 ?>
                </p>
            </div>
        </div>
    <?php } elseif ($service['smart_scan']['showConfigure']) {
        include plugin_dir_path(__FILE__) . 'sitelock-unconfigured-card.php';
    } elseif ($service['smart_scan']['status'] == 'awaitingAllScan') {
        include plugin_dir_path(__FILE__) . 'sitelock-no-data-card.php';
    } ?>
    <?php } ?>
    </div>

    <?php if ($sitelock_connection_status && isset($service)) { ?>
    <div>

    <?php if (!$service['smart_scan']['showConfigure']): ?>
        <p
            class="<?php echo isset($service['smart_scan']['nextScheduledScan']['value']) && $service['smart_scan']['nextScheduledScan']['value'] ? 'text-center mt-2 mb-3' : 'hidden'; ?> text-[14px] text-pretty text-[#333333]">
            <?php echo esc_html($sitelock_language_tokens['var']['automaticSchedule']); ?> <span
                class="scan-result-title schedule-scan"><?php echo esc_html($service['smart_scan']['nextScheduledScan']['value']); ?></span>
        </p>
        <?php if (isset($service['smart_scan']['nextAvailableScan']['value']) && !empty($service['smart_scan']['nextAvailableScan']['value']) && ($service['smart_scan']['nextAvailableScan']['value'] !== 'pending' && $service['smart_scan']['nextAvailableScan']['value'] !== 'NA') && empty($service['smart_scan']['nextScheduledScan']['value'])): ?>
            <p
                class="<?php echo isset($service['smart_scan']['nextAvailableScan']['value']) && $service['smart_scan']['nextAvailableScan']['value'] ? 'text-center mt-2 mb-3' : 'hidden'; ?> text-[14px] text-pretty text-[#333333]">
                <?php echo esc_html($sitelock_language_tokens['var']['nextAvailableScan']); ?> <span
                    class="scan-result-title schedule-scan"><?php echo esc_html($service['smart_scan']['nextAvailableScan']['value']); ?></span>
            </p>
        <?php endif; ?>
    <?php endif; ?>
    
        <div class="grid grid-cols-2 gap-5 mt-2 smart-scan">
            <?php if ($service['smart_scan']['availability'] == "upgradable") { ?>
                <?php if ($this->wpslp_partner_data['upgrade'] === "[default]"): ?>
                    <a href="<?php echo esc_url(admin_url() . 'admin.php?page=sitelock-upgrade'); ?>"
                        class="w-[180px] text-[14px] py-[7px] text-center rounded bg-[#2D68C4] text-[#fff] hover:text-[#fff] focus:text-[#fff]">
                        <?php echo esc_html($sitelock_language_tokens['var']['upgrade']); ?>
                    </a>
                <?php elseif ($this->wpslp_partner_data['upgrade']['action'] === 'redirect' && $this->wpslp_partner_data['upgrade']['value']['popup_option'] === 'no_popup'): ?>
                    <a href="<?php echo esc_url($this->wpslp_partner_data['upgrade']['value']['url']); ?>" target="_blank"
                        class="w-[180px] text-[14px] py-[7px] text-center rounded bg-[#2D68C4] text-[#fff] hover:text-[#fff] focus:text-[#fff]">
                        <?php echo esc_html($sitelock_language_tokens['var']['upgrade']); ?>
                    </a>
    
                <?php elseif ($this->wpslp_partner_data['upgrade']['action'] == 'prompt' || $this->wpslp_partner_data['upgrade']['action'] == 'redirect'): ?>
    
                    <!-- upgrade modal popup initialization -->
                    <?php
                    include(plugin_dir_path(__FILE__) . '../../common/sitelock-modal.php'); ?>
                    <button
                        class="upgradeOpenModalBtn w-[180px] text-[14px] py-[7px] text-center rounded bg-[#2D68C4] text-[#fff] hover:text-[#fff] focus:text-[#fff]">
                        <?php echo esc_html($sitelock_language_tokens['var']['upgrade']); ?>
                    </button>
    
                <?php endif; ?>
            <?php } else { ?>
                <?php if ($service['smart_scan']['showConfigure']) { ?>
                    <a href="<?php echo esc_url(sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/' . esc_attr($sitelock_language_tokens['service_setup_url_tokens']['wizard_server'])) ?>"
                        class="w-full text-[14px] bg-[#F6F9FE] py-[5px] text-center text-[#083C8C] hover:text-[#083C8C] rounded border-blue">
                        <?php echo esc_html($sitelock_language_tokens['var']['setup']) ?>
                    </a>
                <?php } else {
                    $nextAvailableScanValue = false;
                    if ($service['smart_scan']['nextAvailableScan']['value']) {
                        $nextAvailableScanValue = true;
                    }
                    ?>
                    <button data-scan="smart-scan" data-type="smart_scan" <?php if ($nextAvailableScanValue)
                        echo esc_attr('disabled'); ?>
                        class="security-scan-now-button w-full text-[14px] bg-[#F6F9FE] py-[5px] text-center text-[#083C8C] hover:text-[#083C8C] rounded border-blue">
                        <?php echo $nextAvailableScanValue
                            ? '<img src="' . esc_url(plugin_dir_url(__FILE__) . '../../../images/pending.svg') . '" class="mr-2" alt="Pending Icon" /> ' . esc_html($sitelock_language_tokens['var']['scanPending'])
                            : esc_html($sitelock_language_tokens['var']['scanNow']); ?>
                    </button>
                <?php } ?>
    
                <?php if (isset($service['smart_scan']['lastScan']['scanned_at']) && $service['smart_scan']['lastScan']['scanned_at'] && !$service['smart_scan']['hideDetails']) { ?>
                    <a target="_blank"
                        href="<?php echo esc_url($service['smart_scan']['status'] == 'unconfigured' ? sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/' . esc_attr($sitelock_language_tokens['service_url_tokens'][$url]) : sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/' . esc_attr($sitelock_language_tokens['service_url_tokens'][$url]) . '/' . esc_attr($sitelock_language_tokens['service_url_tokens']['smart'])) ?>"
                        class="w-full text-[14px] bg-[#2D68C4] py-[6px] text-center text-[#fff] hover:text-[#fff] focus:text-[#fff] rounded <?php echo esc_attr($service['smart_scan']['status'] == 'unconfigured' ? 'invisible' : 'visible') ?>">
                        <?php echo esc_html($sitelock_language_tokens['var']['viewDetails']) ?>
                    </a>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
  

</div>