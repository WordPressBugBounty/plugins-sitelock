<?php defined( 'ABSPATH' ) || exit; ?>

<div class="flex flex-col justify-between h-full px-3">
    <div class="flex flex-col justify-start">
    <div>
        <h4 class="scan-result-title text-[#000000] mb-[6px]">
            <?php echo esc_html($sitelock_language_tokens['security_scan_list']['databaseScan']) ?>
        </h4>
        <p class="text-[14px] font-normal text-[#6A6A6A] security-scan-description"><?php echo esc_html($sitelock_language_tokens['security_scan_description']['databaseScan']) ?></p>
    </div>

    <?php if ($sitelock_connection_status && isset($sitelock_service)) {
        if (($sitelock_service['db_scan']['availability'] != 'upgradable' && !$sitelock_service['db_scan']['hideDetails']) && $sitelock_service['db_scan']['status'] != 'awaitingAllScan') { ?>
        <div>
            <div class="flex justify-between items-center border-b border-[#EEEEEE]">
            <h4 class="my-2 scan-result-title text-[#000000]"><?php echo esc_html($sitelock_language_tokens['var']['status']); ?>
            </h4>
            <p class="my-2 scan-result-data text-[#333333] flex items-center">
                <span
                class="icon circle w-5 mr-2 <?php echo esc_attr($sitelock_language_tokens['service_status_map'][$sitelock_service['db_scan']['status']]); ?>"></span>
                <?php echo esc_html($sitelock_language_tokens['service_status_tokens'][$sitelock_service['db_scan']['status']]); ?>
            </p>
            </div>
            <div class="flex justify-between items-center border-b border-[#EEEEEE]">
            <h4 class="my-2 scan-result-title text-[#000000]"><?php echo esc_html($sitelock_language_tokens['var']['lastScan']); ?>
            </h4>
            <p class="my-2 scan-result-data text-[#333333] last-scan">
                <?php echo $sitelock_service['db_scan']['lastScanDate'] ? esc_html($sitelock_service['db_scan']['lastScanDate']) : ''; ?>
            </p>
            </div>
            <div class="flex justify-between items-center border-b border-[#EEEEEE]">
            <h4
                class="my-2 scan-result-title <?php echo esc_attr(isset($sitelock_service['db_scan']['lastScan']['malware']) && isset($sitelock_service['db_scan']['lastScan']['malware']['found']) && $sitelock_service['db_scan']['lastScan']['malware']['found'] > 0 ? 'text-red' : 'text-[#000000]'); ?>">
                <?php echo esc_html($sitelock_language_tokens['var']['malware']); ?>
            </h4>
            <p
                class="my-2 scan-result-data <?php echo esc_attr(isset($sitelock_service['db_scan']['lastScan']['malware']) && isset($sitelock_service['db_scan']['lastScan']['malware']['found']) && $sitelock_service['db_scan']['lastScan']['malware']['found'] > 0 ? 'text-red' : 'text-[#333333]'); ?>">
                <?php echo isset($sitelock_service['db_scan']['lastScan']['malware'])                                       && isset($sitelock_service['db_scan']['lastScan']['malware']['found']) ? esc_html($sitelock_service['db_scan']['lastScan']['malware']['found']) : 0; ?>
            </p>
            </div>
            <div class="flex justify-between items-center border-b border-[#EEEEEE]">
            <h4
                class="my-2 scan-result-title <?php echo esc_attr(isset($sitelock_service['db_scan']['lastScan']['spam_code']) && isset($sitelock_service['db_scan']['lastScan']['spam_code']['found']) && $sitelock_service['db_scan']['lastScan']['spam_code']['found'] > 0 ? 'text-red' : 'text-[#000000]'); ?>">
                <?php echo esc_html($sitelock_language_tokens['var']['spamCode']); ?>
            </h4>
            <p
                class="my-2 scan-result-data <?php echo esc_attr(isset($sitelock_service['db_scan']['lastScan']['spam_code']) && isset($sitelock_service['db_scan']['lastScan']['spam_code']['found']) && $sitelock_service['db_scan']['lastScan']['spam_code']['found'] > 0 ? 'text-red' : 'text-[#333333]'); ?>">
                <?php echo isset($sitelock_service['db_scan']['lastScan']['spam_code'])                                       && isset($sitelock_service['db_scan']['lastScan']['spam_code']['found']) ? esc_html($sitelock_service['db_scan']['lastScan']['spam_code']['found']) : 0; ?>
            </p>
            </div>
            <div class="flex justify-between items-center not-last-child:border-b border-[#EEEEEE]">
            <h4
                class="my-2 scan-result-title <?php echo esc_attr(isset($sitelock_service['db_scan']['lastScan']['spam_link']) && isset($sitelock_service['db_scan']['lastScan']['spam_link']['found']) && $sitelock_service['db_scan']['lastScan']['spam_link']['found'] > 0 ? 'text-red' : 'text-[#000000]'); ?>">
                <?php echo esc_html($sitelock_language_tokens['var']['spamLinks']); ?>
            </h4>
            <p
                class="my-2 scan-result-data <?php echo esc_attr(isset($sitelock_service['db_scan']['lastScan']['spam_link']) && isset($sitelock_service['db_scan']['lastScan']['spam_link']['found']) && $sitelock_service['db_scan']['lastScan']['spam_link']['found'] > 0 ? 'text-red' : 'text-[#333333]'); ?>">
                <?php echo isset($sitelock_service['db_scan']['lastScan']['spam_link'])                                       && isset($sitelock_service['db_scan']['lastScan']['spam_link']['found']) ? esc_html($sitelock_service['db_scan']['lastScan']['spam_link']['found']) : 0; ?>
            </p>
            </div>
        </div>
    <?php } elseif ($sitelock_service['db_scan']['showConfigure']) {
        include plugin_dir_path(__FILE__) . 'sitelock-unconfigured-card.php';
    } elseif ($sitelock_service['db_scan']['status'] == 'awaitingAllScan') {
        include plugin_dir_path(__FILE__) . 'sitelock-no-data-card.php';
    } ?>
    <?php
    } ?>
    </div>

    <?php if ($sitelock_connection_status && isset($sitelock_service)) { ?>
    <div>

    <?php if (!$sitelock_service['db_scan']['showConfigure']): ?>
        <p
            class="<?php echo isset($sitelock_service['db_scan']['nextScheduledScan']['value']) && $sitelock_service['db_scan']['nextScheduledScan']['value'] ? 'text-center mt-2 mb-3' : 'hidden'; ?> text-[14px] text-pretty text-[#333333]">
            <?php echo esc_html($sitelock_language_tokens['var']['automaticSchedule']); ?> <span
                class="scan-result-title schedule-scan"><?php echo esc_html($sitelock_service['db_scan']['nextScheduledScan']['value']); ?></span>
        </p>
        <?php if (isset($sitelock_service['db_scan']['nextAvailableScan']['value']) && !empty($sitelock_service['db_scan']['nextAvailableScan']['value']) && $sitelock_service['db_scan']['nextAvailableScan']['value'] !== 'pending' && $sitelock_service['db_scan']['nextAvailableScan']['value'] !== 'NA' && empty($sitelock_service['db_scan']['nextScheduledScan']['value'])): ?>
            <p
                class="<?php echo isset($sitelock_service['db_scan']['nextAvailableScan']['value']) && $sitelock_service['db_scan']['nextAvailableScan']['value'] ? 'text-center mt-2 mb-3' : 'hidden'; ?> text-[14px] text-pretty text-[#333333]">
                <?php echo esc_html($sitelock_language_tokens['var']['nextAvailableScan']); ?> <span
                    class="scan-result-title schedule-scan"><?php echo esc_html($sitelock_service['db_scan']['nextAvailableScan']['value']); ?></span>
            </p>
        <?php endif; ?>
    <?php endif; ?>
        <div class="grid grid-cols-2 gap-5 mt-2 db-scan">

        <?php if ($sitelock_service['db_scan']['availability'] == 'upgradable') { ?>
            <?php if ($this->wpslp_partner_data['upgrade'] === '[default]'): ?>
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
                     <button class="upgradeOpenModalBtn w-[180px] text-[14px] py-[7px] text-center rounded bg-[#2D68C4] text-[#fff] hover:text-[#fff] focus:text-[#fff]">
                         <?php echo esc_html($sitelock_language_tokens['var']['upgrade']); ?>
                     </button>

            <?php endif; ?>
        <?php } else { ?>
            <?php if ($sitelock_service['db_scan']['showConfigure']) { ?>
                <a href="<?php echo esc_url(sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/' . esc_attr($sitelock_language_tokens['service_setup_url_tokens']['wizard_server'])) ?>"
                    class="w-full text-[14px] bg-[#F6F9FE] py-[5px] text-center text-[#083C8C] hover:text-[#083C8C] rounded border-blue">
                    <?php echo esc_html($sitelock_language_tokens['var']['setup']) ?>
                </a>
            <?php } else { 
                $sitelock_next_available_scan_value = false; 
                if($sitelock_service['db_scan']['nextAvailableScan']['value']) {
                    $sitelock_next_available_scan_value = true; 
                }
                ?>
                <button data-scan="db-scan" data-type="db_scan" <?php if ($sitelock_next_available_scan_value)
                                       echo esc_attr('disabled'); ?>
                        class="scan-now-button flex-1 text-[14px] bg-[#F6F9FE] py-[5px] text-center text-[#083C8C] hover:text-[#083C8C] rounded border-blue">
                        <?php echo $sitelock_next_available_scan_value
                            ? '<img src="' . esc_url(plugin_dir_url(__FILE__) . '../../../images/pending.svg') . '" class="mr-2" alt="Pending Icon" /> ' . esc_html($sitelock_language_tokens['var']['scanPending'])
                            : esc_html($sitelock_language_tokens['var']['scanNow']); ?>
                </button>
            <?php
            } ?>

            <?php if (isset($sitelock_service['db_scan']['lastScan']['scanned_at']) && $sitelock_service['db_scan']['lastScan']['scanned_at'] && !$sitelock_service['db_scan']['hideDetails']) { ?>
                <a target="_blank"
                    href="<?php echo esc_url($sitelock_service['db_scan']['status']                                                                                                                == 'unconfigured' ? sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/' . esc_attr($sitelock_language_tokens['service_url_tokens'][$sitelock_scan_result_url]) : sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/' . esc_attr($sitelock_language_tokens['service_url_tokens'][$sitelock_scan_result_url]) . '/' . esc_attr($sitelock_language_tokens['service_url_tokens']['smartdb'])) ?>"
                    class="w-full text-[14px] bg-[#2D68C4] py-[6px] text-center text-[#fff] hover:text-[#fff] focus:text-[#fff] rounded <?php echo esc_attr($sitelock_service['db_scan']['status'] == 'unconfigured' ? 'invisible' : 'visible') ?>">
                    <?php echo esc_html($sitelock_language_tokens['var']['viewDetails']) ?>
                </a>
            <?php } ?>

        <?php } ?>
        </div>   
    </div>
    <?php } ?>

</div>