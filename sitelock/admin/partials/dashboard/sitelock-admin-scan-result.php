<?php
defined( 'ABSPATH' ) || exit;
$sitelock_site_id     = get_option('sitelock_site_id', '');
$sitelock_upgrade_prompt       = isset($this->wpslp_partner_data['upgrade']['value']['prompt_text']) ? $this->wpslp_partner_data['upgrade']['value']['prompt_text'] : '';
$sitelock_upgrade_redirect_url = isset($this->wpslp_partner_data['upgrade']['value']['url']) ? $this->wpslp_partner_data['upgrade']['value']['url'] : '';
$sitelock_accepted_feature_list = ['scanning', 'waf', 'backup', 'patchman', 'ssl_scan', 'spam_scan', 'risk_score'];
?>

<div id="scan-result" class="simple-box border-grey-light bg-[#fff] mb-6">
    <!-- Standard header -->

    <?php  if ($sitelock_connection_status) { ?>
    <div class="header p-0 sm:px-5 py-3">
        <div class="grid grid-cols-12 sm:grid-cols-12 gap-4">
            <!-- Plus/minus toggle button -->
            <div class="col-span-12 sm:col-span-4 c1 flex items-center justify-start pl-2">
                <h4 class="title">
                    <?php echo esc_html($sitelock_language_tokens['var']['cloudServices']) . ' '; ?>
                </h4>
            </div>
            <div class="col-span-6 sm:col-span-3 c2 hidden sm:flex items-center justify-start align-middle  text-[#828282]"><?php echo esc_html($sitelock_language_tokens['var']['dashboardStatus']); ?></div>
            <div class="col-span-6 sm:col-span-3 c3 hidden sm:flex items-center text-left align-middle  text-[#828282]"><?php echo esc_html($sitelock_language_tokens['var']['lastScanAndBackup']); ?></div>
       
        </div>
        </div>
        <?php } else { ?>
        <div class="header px-3 sm:px-5 py-3">
        <h4 class="title">
            <?php echo esc_html($sitelock_language_tokens['var']['cloudServices']) . ' ';
            if (!$sitelock_connection_status) {
                echo '(' . esc_html($sitelock_language_tokens['var']['accountRequired']) . ')';
            } ?>
        </h4>
    </div>
    <?php } ?>
    <!-- Report Body Section -->
    <?php if (!$sitelock_connection_status || count($sitelock_services) == 0) { ?>

        <div class="p-0 sm:px-5 min-h-[250px] ">

            <?php
            $sitelock_token_last_key = array_key_last($sitelock_language_tokens['cloud_services_list']);
        foreach ($sitelock_language_tokens['cloud_services_list'] as $sitelock_i => $sitelock_list) {
            include plugin_dir_path(__FILE__) . 'sitelock-scan-result-without-activation.php';
        }
        ?>
        </div>
        <?php if (!$sitelock_connection_status) { ?>
            <div class="flex justify-between bg-gradient-to-b from-[#F7F7F7] to-[#FFFFFF] py-2 px-2 sm:px-5">
                <div class="flex items-center justify-start gap-4">
                    <a href="<?php echo esc_url(sitelock_get_redirect_url('signup') . admin_url()); ?>" target="_blank" rel="noopener noreferrer"
                        class="px-6 text-center py-[6px] bg-[#F6F9FE] text-[#083C8C] border-blue rounded my-3">
                        <?php echo esc_html($sitelock_language_tokens['var']['activateFreeAccount']); ?>
                    </a>
                </div>
                <div class="block sm:flex text-[14px] font-normal leading-[150%] py-4">
                    Already have an account?
                    <a target="_blank" rel="noopener noreferrer" class="text-[#2D68C4] underline cursor-pointer ml-1"
                        href="<?php echo esc_url(sitelock_api_url() . '/login?redirect_to=site.settings.wordpress&siteId=' . sitelock_get_site_identifier()); ?>">
                        Get your license key now
                    </a>
                </div>
            </div>
        <?php } ?>

    <?php } else { ?>
        <div class="p-0 sm:px-5">
            <?php
        if (isset($sitelock_services['risk_score']) && $sitelock_services['risk_score']['availability'] == 'upgradable') {
            unset($sitelock_services['risk_score']);
        }

        $sitelock_scan_result_last_key = array_key_last($sitelock_services);
        foreach ($sitelock_services as $sitelock_index => $sitelock_service): 
            if (in_array($sitelock_index, $sitelock_accepted_feature_list)): ?>
                <?php
            if ($sitelock_index == 'scanning') {
                $sitelock_scan_result_code          = $sitelock_language_tokens['service_title_tokens'][$sitelock_index];
                $sitelock_last_scan_date  = $sitelock_service['lastScanDate'] ?? null;
                $sitelock_scan_result_status        = $sitelock_service['overallStatus'];
                $sitelock_scanning_array = [];
                if (isset($sitelock_service['smart_scan'])) {
                    $sitelock_scanning_array[] = $sitelock_service['smart_scan'];
                }
                if (isset($sitelock_service['db_scan'])) {
                    $sitelock_scanning_array[] = $sitelock_service['db_scan'];
                }
                if (isset($sitelock_service['malware_scan'])) {
                    $sitelock_scanning_array[] = $sitelock_service['malware_scan'];
                }
                if (isset($sitelock_service['vulnerability_scan'])) {
                    $sitelock_scanning_array[] = $sitelock_service['vulnerability_scan'];
                }

                $sitelock_feature_status_sort_map_global;
                
                uasort($sitelock_scanning_array, function ($a, $b) use ($sitelock_feature_status_sort_map_global) {
                    return ($sitelock_feature_status_sort_map_global[$a['status']]) <=> $sitelock_feature_status_sort_map_global[$b['status']];
                });
            } elseif ($sitelock_index == 'backup') {
                if (!isset($sitelock_service['code'])) {
                    continue;
                }
                $sitelock_scan_result_code         = $sitelock_language_tokens['service_title_tokens'][$sitelock_service['code']];
                $sitelock_last_scan_date = $sitelock_service['lastBackupDate'] ?? null;
                $sitelock_scan_result_status       = $sitelock_service['status'];

                if ($sitelock_service['availability'] != 'upgradable' && $sitelock_service['status'] != 'unconfigured' && $sitelock_service['status'] != 'partiallyConfigured' && $sitelock_service['status'] != 'awaitingFirstBackup') {
                    $sitelock_file_backup_configured = false;
                    $sitelock_db_backup_configured   = false;
                    $sitelock_files_status          = $sitelock_service['lastBackup']['files']['status'] ?? null;
                    $sitelock_db_status             = $sitelock_service['lastBackup']['db']['status']    ?? null;
                    foreach ($sitelock_service['subFeatures'] as $sitelock_feature) {
                        if ($sitelock_feature['shortName'] === 'Backup Database' && $sitelock_feature['isConfigured'] === 1) {
                            $sitelock_db_backup_configured = $sitelock_feature['isConfigured'];
                        } elseif ($sitelock_feature['shortName'] === 'Backup Files' && $sitelock_feature['isConfigured'] === 1) {
                            $sitelock_file_backup_configured = $sitelock_feature['isConfigured'];
                        }
                    }
                    $sitelock_show_files_status = $sitelock_file_backup_configured ? 'active' : 'inactive';
                    if ($sitelock_files_status === 'error') {
                        $sitelock_show_files_status = 'issuesFound';
                    }
                    if (!$sitelock_file_backup_configured) {
                        $sitelock_show_files_status = 'unconfigured';
                    }

                    $sitelock_show_db_status = $sitelock_db_backup_configured ? 'active' : 'inactive';
                    if ($sitelock_db_status === 'error') {
                        $sitelock_show_db_status = 'issuesFound';
                    }
                    if (!$sitelock_db_backup_configured) {
                        $sitelock_show_db_status = 'unconfigured';
                    }
                    // Get quota in MB, fallback to 0
                    $sitelock_quota_value = isset($sitelock_service['quota']) ? $sitelock_service['quota'] : 0;
                    $sitelock_quota      = is_numeric($sitelock_quota_value) ? (int) $sitelock_quota_value : intval((string) $sitelock_quota_value);

                    // Get DB usage in KB, fallback to 0
                    $sitelock_db_usage_kb_value = isset($sitelock_service['utilization']['db']['usage_kb']) ? $sitelock_service['utilization']['db']['usage_kb'] : 0;
                    $sitelock_db_usage_kb      = is_numeric($sitelock_db_usage_kb_value) ? (int) $sitelock_db_usage_kb_value : intval((string) $sitelock_db_usage_kb_value);

                    // Get file usage in KB, fallback to 0
                    $sitelock_files_usage_kb_value = isset($sitelock_service['utilization']['files']['usage_kb']) ? $sitelock_service['utilization']['files']['usage_kb'] : 0;
                    $sitelock_files_usage_kb      = is_numeric($sitelock_files_usage_kb_value) ? (int) $sitelock_files_usage_kb_value : intval((string) $sitelock_files_usage_kb_value);

                    // Total usage in KB and MB
                    $sitelock_usage_kb = $sitelock_db_usage_kb + $sitelock_files_usage_kb;
                    $sitelock_usage_mb = $sitelock_usage_kb / 1000;

                    // Calculate percentage used
                    $sitelock_percentage_storage_used = ($sitelock_quota > 0) ? ($sitelock_usage_mb / $sitelock_quota) * 100 : 0;

                    // Format percentage
                    $sitelock_formatted_percentage_storage_used = ($sitelock_percentage_storage_used >= 1)
                        ? number_format(floor($sitelock_percentage_storage_used), 0)
                        : number_format($sitelock_percentage_storage_used, 2);

                    // Convert total storage MB to GB if available
                    $sitelock_total_storage_in_mb = $sitelock_quota; // or another value if defined separately
                    $sitelock_total_storage_in_gb = ($sitelock_total_storage_in_mb) ? ($sitelock_total_storage_in_mb / 1000) : 0;
                }
            } else {
                if (!isset($sitelock_service['code'])) {
                    continue;
                }
                $sitelock_scan_result_code         = $sitelock_language_tokens['service_title_tokens'][$sitelock_service['code']];
                $sitelock_last_scan_date = $sitelock_service['lastScanDate'] ?? null;
                $sitelock_scan_result_status       = $sitelock_service['status'];
                if ($sitelock_index == 'patchman') {
                    $sitelock_last_good_scan_date = $sitelock_service['lastGoodScanDate'] ?? null;
                }
            }
            $sitelock_scan_result_url = $sitelock_index == 'scanning' ? $sitelock_index : $sitelock_service['code'];

            ?>
                <div class="<?php echo esc_attr($sitelock_scan_result_last_key == $sitelock_index ?? 'not-last-child:') ?>border-b border-[#EEEEEE] px-3 sm:px-0">
                    <div class="grid grid-cols-12 sm:grid-cols-12 gap-4 py-[10px]">
                        <!-- Plus/minus toggle button -->
                        <div class="col-span-12 sm:col-span-4 c1 flex items-center justify-start ">
                            <div>
                                <button type="button" class="toggle mr-3" data-id="<?php echo esc_attr($sitelock_scan_result_url); ?>">
                                    <span><span></span> </span>
                                </button>
                            </div>
                            <?php if ($sitelock_service['upgradable']) { ?>
                                <?php if ($this->wpslp_partner_data['upgrade'] === '[default]'): ?>
                                    <a href="<?php echo esc_url(admin_url() . 'admin.php?page=sitelock-upgrade'); ?>" class="title">
                                        <?php echo esc_html($sitelock_scan_result_code); ?>
                                    </a>
                                <?php elseif ($this->wpslp_partner_data['upgrade']['action'] === 'redirect' && $this->wpslp_partner_data['upgrade']['value']['popup_option'] === 'no_popup'): ?>
                                    <a href="<?php echo esc_url($this->wpslp_partner_data['upgrade']['value']['url']); ?>" class="title" target="_blank">
                                        <?php echo esc_html($sitelock_scan_result_code); ?>
                                    </a>
                                <?php elseif ($this->wpslp_partner_data['upgrade']['action'] == 'prompt' || $this->wpslp_partner_data['upgrade']['action'] == 'redirect'): ?>
                                
                                    <!-- upgrade modal popup initialization -->
                                
                                    <?php
                                include(plugin_dir_path(__FILE__) . '../common/sitelock-modal.php'); ?>
                                
                                    <h2 class="upgradeOpenModalBtn title cursor-pointer">
                                        <?php echo esc_html($sitelock_scan_result_code); ?>
                                    </h2>
                                
                                <?php endif; ?>
                            <?php } elseif (($sitelock_scan_result_url == 'patchman' || $sitelock_scan_result_url == 'waf' || $sitelock_scan_result_url == 'backup') && isset($sitelock_service['showConfigure']) && $sitelock_service['showConfigure'] && isset($sitelock_service['hideDetails']) && !$sitelock_service['hideDetails']) { ?>
                                <a class="title" target="_blank"
                                    href="<?php echo esc_url(sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/wizard/' . esc_attr($sitelock_language_tokens['service_setup_url_tokens'][$sitelock_scan_result_url])); ?>">
                                    <?php echo esc_html($sitelock_scan_result_code); ?>
                                </a>
                            <?php } elseif (($sitelock_scan_result_url == 'patchman' || $sitelock_scan_result_url == 'waf' || $sitelock_scan_result_url == 'backup') && $sitelock_service['status'] == 'unconfigured' || (isset($sitelock_service['showConfigure']) && $sitelock_service['showConfigure'])) { ?>
                                <a class="title" target="_blank"
                                    href="<?php echo esc_url(sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/wizard/' . esc_attr($sitelock_language_tokens['service_setup_url_tokens'][$sitelock_scan_result_url])); ?>">
                                    <?php echo esc_html($sitelock_scan_result_code); ?>
                                </a>
                            <?php } elseif (($sitelock_scan_result_url == 'patchman' || $sitelock_scan_result_url == 'waf' || $sitelock_scan_result_url == 'backup') && $sitelock_service['status'] == 'unconfigured' || (isset($sitelock_service['showConfigure']) && $sitelock_service['showConfigure'])) { ?>
                                <a class="title" target="_blank"
                                    href="<?php echo esc_url(sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/wizard/' . esc_attr($sitelock_language_tokens['service_setup_url_tokens'][$sitelock_scan_result_url])); ?>">
                                    <?php echo esc_html($sitelock_scan_result_code); ?>
                                </a>
                            <?php } else { ?>
                                <a class="title" target="_blank"
                                    href="<?php echo esc_url(sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/' . esc_attr($sitelock_language_tokens['service_url_tokens'][$sitelock_scan_result_url])); ?>">
                                    <?php echo esc_html($sitelock_scan_result_code); ?>
                                </a>
                            <?php }
                            ?>
                        </div>

                        <!-- Heading -->
                        <div class="col-span-6 sm:col-span-3 c2 flex items-center justify-start">
                            <div>
                                <p class="block sm:hidden mb-1 uppercase text-[#828282]"><?php echo esc_html($sitelock_language_tokens['var']['status']); ?></p>
                                <div class="flex items-center justify-start">
                                    <?php if (isset($sitelock_service['code']) && $sitelock_service['code'] == 'advisories') { ?>
                                        <div class="pr-2">
                                            <span class="icon circle w-5 <?php echo esc_attr($sitelock_language_tokens['service_status_map'][$sitelock_scan_result_status]); ?>"> </span>
                                        </div>
                                        <!-- Text -->
                                        <span class="status"><?php echo isset($sitelock_service['score']) && isset($sitelock_language_tokens['service_risk_score_token'][$sitelock_service['score']]) ? esc_html($sitelock_language_tokens['service_risk_score_token'][$sitelock_service['score']]) : esc_html(isset($sitelock_language_tokens['service_status_tokens']['notEnrolled']) ? $sitelock_language_tokens['service_status_tokens']['notEnrolled'] : ''); ?> </span>
                                    <?php } elseif (isset($sitelock_service['code']) && $sitelock_service['code'] == 'backup' && ($sitelock_scan_result_status == 'nearQuota' || $sitelock_scan_result_status == 'overQuota')) { ?>
                                        <span class="icon circle w-5 mr-2 <?php echo esc_attr($sitelock_formatted_percentage_storage_used < 80 ? esc_attr($sitelock_language_tokens['service_status_map']['active']) : ($sitelock_formatted_percentage_storage_used > 80 ? esc_attr($sitelock_language_tokens['service_status_map']['nearQuota']) : esc_attr($sitelock_language_tokens['service_status_map']['unconfigured']))); ?>"></span>
                                        <span class="status"><?php echo esc_html(number_format($sitelock_formatted_percentage_storage_used, 0)) . '% Full'; ?></span>
                                    <?php } else { ?>
                                        <!-- Standard color circle -->
                                        <div class="pr-2">
                                            <span class="icon circle w-5 <?php echo esc_attr($sitelock_language_tokens['service_status_map'][$sitelock_scan_result_status]); ?>"> </span>
                                        </div>
                                        <!-- Text -->
                                        <span class="status"><?php echo esc_html($sitelock_language_tokens['service_status_tokens'][$sitelock_scan_result_status]); ?> </span>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-span-6 sm:col-span-3 c3 flex items-center text-left">
                            <?php if ($sitelock_last_scan_date) { ?>
                                <div>
                                    <p class="block sm:hidden mb-1 uppercase text-[#828282]"><?php echo esc_html($sitelock_language_tokens['var']['lastScan']); ?></p>
                                    <div class="flex-1 <?php echo esc_attr($sitelock_scan_result_url == 'spam_scan' ? 'email-last-scan' : 'last-scan') ?>">
                                        <?php echo esc_html($sitelock_last_scan_date) ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="col-span-4 sm:col-span-2 c4 flex items-center text-left">
                            <?php if ($sitelock_service['upgradable']) { ?>
                                <?php if ($this->wpslp_partner_data['upgrade'] === '[default]'): ?>
                                    <a href="<?php echo esc_url(admin_url() . 'admin.php?page=sitelock-upgrade'); ?>"
                                        class="w-full text-[14px] py-[7px] text-center rounded bg-[#2D68C4] text-[#fff] hover:text-[#fff] focus:text-[#fff]">
                                        <?php echo esc_html($sitelock_language_tokens['var']['upgrade']); ?>
                                    </a>
                                <?php elseif ($this->wpslp_partner_data['upgrade']['action'] === 'redirect' && $this->wpslp_partner_data['upgrade']['value']['popup_option'] === 'no_popup'): ?>
                                    <a href="<?php echo esc_url($this->wpslp_partner_data['upgrade']['value']['url']); ?>" target="_blank"
                                        class="w-full text-[14px] py-[7px] text-center rounded bg-[#2D68C4] text-[#fff] hover:text-[#fff] focus:text-[#fff]">
                                        <?php echo esc_html($sitelock_language_tokens['var']['upgrade']); ?>
                                    </a>
                                <?php elseif ($this->wpslp_partner_data['upgrade']['action'] == 'prompt' || $this->wpslp_partner_data['upgrade']['action'] == 'redirect'): ?>

                                    <!-- upgrade modal popup initialization -->

                                    <?php
                                    include(plugin_dir_path(__FILE__) . '../common/sitelock-modal.php'); ?>

                                    <button class="upgradeOpenModalBtn w-full text-[14px] py-[7px] text-center rounded bg-[#2D68C4] text-[#fff] hover:text-[#fff] focus:text-[#fff]">
                                        <?php echo esc_html($sitelock_language_tokens['var']['upgrade']); ?>
                                    </button>

                                <?php endif; ?>
                            <?php } elseif (($sitelock_scan_result_url == 'patchman' || $sitelock_scan_result_url == 'waf' || $sitelock_scan_result_url == 'backup') && (isset($sitelock_service['showConfigure']) && $sitelock_service['showConfigure'] && isset($sitelock_service['hideDetails']) && !$sitelock_service['hideDetails'])) { ?>
                                <div class="relative w-full relative-wrapper">
                                    <a href="<?php echo esc_url_raw(sitelock_api_url()) . '/sites/' . esc_attr($sitelock_site_info['id']) . '/wizard/' . esc_attr($sitelock_language_tokens['service_setup_url_tokens'][$sitelock_scan_result_url]); ?>"
                                        class="w-full text-[14px] py-[6px] text-center rounded border-blue bg-[#F6F9FE] text-[#083C8C] relative inline-block" target="_blank" rel="noopener noreferrer">
                                        <span class="mr-6"><?php echo esc_html($sitelock_language_tokens['var']['setup']); ?></span>
                                        <span class="dropdown-arrow border border-l-[#A2BEEB]">
                                            <img src="<?php echo esc_url(plugin_dir_url(__DIR__) . '../images/down-arrow-blue.svg'); ?>"
                                                alt="<?php echo esc_attr($sitelock_language_tokens['var']['downArrow']); ?>" class="w-3 arrow-toggle" />
                                        </span>
                                    </a>
                                    <div class="dropdown base-dropdown" style="display:none;">
                                        <span><a class="link" target="_blank" rel="noopener noreferrer"
                                                href="<?php echo esc_url(sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/wizard/' . esc_attr($sitelock_language_tokens['service_setup_url_tokens'][$sitelock_scan_result_url])); ?>">Set
                                                Up</a></span>
                                        <span><a class="link" target="_blank" rel="noopener noreferrer"
                                                href="<?php echo esc_url(sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/' . esc_attr($sitelock_language_tokens['service_url_tokens'][$sitelock_scan_result_url])); ?>">Details</a></span>
                                    </div>
                                </div>
                            <?php } elseif (($sitelock_scan_result_url == 'patchman' || $sitelock_scan_result_url == 'waf' || $sitelock_scan_result_url == 'backup') && $sitelock_service['status'] == 'unconfigured' || (isset($sitelock_service['showConfigure']) && $sitelock_service['showConfigure'])) { ?>
                                <a target="_blank"
                                    href="<?php echo esc_url(sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/wizard/' . esc_attr($sitelock_language_tokens['service_setup_url_tokens'][$sitelock_scan_result_url])); ?>"
                                    class="w-full text-[14px] py-[6px] text-center rounded border-blue bg-[#F6F9FE] text-[#083C8C]">
                                    <?php echo esc_html($sitelock_language_tokens['var']['setup']); ?>
                                </a>
                            <?php } else { ?>
                                <a target="_blank"
                                    href="<?php echo esc_url(sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/' . esc_attr($sitelock_language_tokens['service_url_tokens'][$sitelock_scan_result_url])); ?>"
                                    class="w-full text-[14px] py-[6px] text-center rounded border-blue bg-[#F6F9FE] text-[#083C8C]">
                                    <?php echo esc_html($sitelock_language_tokens['var']['details']); ?>
                                </a>
                            <?php } ?>
                        </div>
                    </div>

                    <div id="<?php echo esc_attr($sitelock_scan_result_url); ?>" class="collapsed">
                        <div
                            class="<?php echo esc_attr('grid grid-cols-1 md:grid-cols-12 gap-1 sm:gap-4 mb-3 ' . ($sitelock_scan_result_url == 'scanning' ? 'pr-2 sm:pr-0 pl-[28px]' : '')); ?>">
                            <?php if ($sitelock_scan_result_url == 'scanning') { ?>

                                <?php if (isset($sitelock_service['db_scan'])) { ?>
                                    <div
                                        class="md:col-span-6 order-<?php echo esc_attr(array_search('db_scan', array_column($sitelock_scanning_array, 'code')) + 1); ?>">
                                        <?php include plugin_dir_path(__FILE__) . 'sitelock-admin-security-scan/sitelock-security-scan-db.php'; ?>
                                    </div>
                                <?php } ?>

                                <?php if (isset($sitelock_service['smart_scan'])) { ?>
                                    <div
                                        class="md:col-span-6 order-<?php echo esc_attr(array_search('smart_scan', array_column($sitelock_scanning_array, 'code')) + 1); ?>">
                                        <?php include plugin_dir_path(__FILE__) . 'sitelock-admin-security-scan/sitelock-security-scan-file.php'; ?>
                                    </div>
                                <?php } ?>

                                <?php if (isset($sitelock_service['vulnerability_scan'])) { ?>
                                    <div
                                        class="md:col-span-6 order-<?php echo esc_attr(array_search('vulnerability_scan', array_column($sitelock_scanning_array, 'code')) + 1); ?>">
                                        <?php include plugin_dir_path(__FILE__) . 'sitelock-admin-security-scan/sitelock-security-scan-vulnerability.php'; ?>
                                    </div>
                                <?php } ?>

                                <?php if (isset($sitelock_service['malware_scan'])) { ?>
                                    <div
                                        class="md:col-span-6 order-<?php echo esc_attr(array_search('malware_scan', array_column($sitelock_scanning_array, 'code')) + 1); ?>">
                                        <?php include plugin_dir_path(__FILE__) . 'sitelock-admin-security-scan/sitelock-security-scan-malware.php'; ?>
                                    </div>
                                <?php } ?>

                            <?php } elseif ($sitelock_scan_result_url == 'ssl_scan' || $sitelock_scan_result_url == 'advisories') { ?>
                                <div class="md:col-span-12">
                                    <p class="mx-5 sm:mx-10 text-[14px] leading-[150%] mb-4 font-normal text-[#6A6A6A]">
                                        <?php echo esc_html($sitelock_language_tokens['cloud_services_description'][$sitelock_scan_result_url]); ?>
                                    </p>
                                </div>
                            <?php } elseif ($sitelock_scan_result_url == 'backup') { ?>
                                <?php if ($sitelock_service['availability'] == 'upgradable' || $sitelock_service['status'] == 'unconfigured' || $sitelock_service['status'] == 'partiallyConfigured' || $sitelock_service['status'] == 'awaitingFirstBackup') { ?>
                                    <div class="md:col-span-12">
                                        <p class="mx-5 sm:mx-10 text-[14px] leading-[150%] mb-4 font-normal text-[#6A6A6A]">
                                            <?php echo esc_html($sitelock_language_tokens['cloud_services_description']['backup']); ?>
                                        </p>
                                    </div>
                                <?php } else { ?>
                                    <div class="md:col-span-6">
                                        <p class="mx-5 sm:mx-10 mb-4 mr-5 text-[14px] leading-[150%] font-normal text-[#6A6A6A]">
                                            <?php echo esc_html($sitelock_language_tokens['cloud_services_description']['backup']); ?>
                                        </p>
                                    </div>

                                    <div class="md:col-span-6">
                                        <div class="px-5 md:px-0">
                                            <div class="flex justify-between items-center border-b border-[#EEEEEE]">
                                                <h4 class="my-2 scan-result-title text-[#000000]">
                                                    <?php echo esc_html($sitelock_total_storage_in_gb) . ' ' . esc_html($sitelock_language_tokens['var']['storage']); ?>
                                                </h4>
                                                <div>
                                                    <p class="mt-2 mb-1 scan-result-data text-[#333333] flex items-center">
                                                        <span
                                                            class="icon circle w-5 mr-2 <?php echo esc_attr($sitelock_formatted_percentage_storage_used < 80 ? $sitelock_language_tokens['service_status_map']['active'] : ($sitelock_formatted_percentage_storage_used > 80 ? $sitelock_language_tokens['service_status_map']['nearQuota'] : $sitelock_language_tokens['service_status_map']['unconfigured'])) ?>"></span>
                                                        <?php echo esc_html(number_format($sitelock_formatted_percentage_storage_used, 2)) . '% full' ?>
                                                    </p>
                                                    <?php if ($sitelock_percentage_storage_used > 80) { ?>
                                                    <?php if ($this->wpslp_partner_data['upgrade'] === '[default]'): ?>
                                                            <a href="<?php echo esc_url(admin_url() . 'admin.php?page=sitelock-upgrade'); ?>"
                                                                class="underline hover:!underline text-blue text-end mb-1">
                                                        <?php echo esc_html($sitelock_language_tokens['var']['upgradeStorage']); ?>
                                                            </a>
                                                            <?php elseif ($this->wpslp_partner_data['upgrade']['action'] === 'redirect' && $this->wpslp_partner_data['upgrade']['value']['popup_option'] === 'no_popup'): ?>
                                                                <a href="<?php echo esc_url($this->wpslp_partner_data['upgrade']['value']['url']); ?>" target="_blank"
                                                                class="underline hover:!underline text-blue text-end mb-1">
                                                        <?php echo esc_html($sitelock_language_tokens['var']['upgradeStorage']); ?>
                                                            </a>
                                                    <?php elseif ($this->wpslp_partner_data['upgrade']['action'] == 'prompt' || $this->wpslp_partner_data['upgrade']['action'] == 'redirect'): ?>
                                                    
                                                            <!-- upgrade modal popup initialization -->
                                                        <?php
                                                        include(plugin_dir_path(__FILE__) . '../common/sitelock-modal.php'); ?>
                                                            <button class="upgradeOpenModalBtn w-full text-end underline hover:!underline text-blue mb-1">
                                                        <?php echo esc_html($sitelock_language_tokens['var']['upgradeStorage']); ?>
                                                            </button>
                                                    
                                                    <?php endif; ?>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <div class="flex justify-between items-center border-b border-[#EEEEEE]">
                                                <h4 class="my-2 scan-result-title text-[#000000]"><?php echo esc_html($sitelock_language_tokens['var']['fileBackup']); ?>
                                                </h4>
                                                <p class="my-2 scan-result-data text-[#333333] flex items-center">
                                                    <span
                                                        class="icon circle w-5 mr-2 <?php echo esc_attr($sitelock_language_tokens['service_status_map'][$sitelock_show_files_status]); ?> "></span><?php echo esc_html($sitelock_language_tokens['service_status_tokens'][$sitelock_show_files_status]); ?>
                                                </p>
                                            </div>
                                            <?php if (isset($sitelock_service['lastBackup']['files']) && $sitelock_service['lastBackup']['files']['created_at']) { ?>
                                                <div class="flex justify-between items-center border-b border-[#EEEEEE]">
                                                    <h4 class="my-2 scan-result-title text-[#000000]">
                                                        <?php echo esc_html($sitelock_language_tokens['var']['lastFileBackup']); ?>
                                                    </h4>
                                                    <p class="my-2 scan-result-data text-[#333333] flex items-center last-scan">
                                                        <?php echo esc_html(isset($sitelock_service['lastBackup']['files']) && $sitelock_service['lastBackup']['files']['created_at'] ? $sitelock_service['lastBackup']['files']['created_at'] : ''); ?>
                                                    </p>
                                                </div>
                                            <?php } ?>
                                            <div
                                                class="flex justify-between items-center <?php echo esc_attr(!isset($sitelock_service['lastBackup']['db']) || !$sitelock_service['lastBackup']['db']['created_at'] ? 'not-last-child:' : '') ?>border-b border-[#EEEEEE]">
                                                <h4 class="my-2 scan-result-title text-[#000000]">
                                                    <?php echo esc_html($sitelock_language_tokens['var']['databaseBackup']); ?>
                                                </h4>
                                                <p class="my-2 scan-result-data text-[#333333] flex items-center">
                                                    <span
                                                        class="icon circle w-5 mr-2 <?php echo esc_attr($sitelock_language_tokens['service_status_map'][$sitelock_show_db_status]); ?>"></span><?php echo esc_html($sitelock_language_tokens['service_status_tokens'][$sitelock_show_db_status]); ?>
                                                </p>
                                            </div>

                                            <?php if (isset($sitelock_service['lastBackup']['db']) && $sitelock_service['lastBackup']['db']['created_at']) { ?>
                                                <div class="flex justify-between items-center not-last-child:border-b border-[#EEEEEE]">
                                                    <h4 class="my-2 scan-result-title text-[#000000]">
                                                        <?php echo esc_html($sitelock_language_tokens['var']['lastDatabaseBackup']); ?>
                                                    </h4>
                                                    <p class="my-2 scan-result-data text-[#333333] flex items-center last-scan">
                                                        <?php echo esc_html(isset($sitelock_service['lastBackup']['db']) && $sitelock_service['lastBackup']['db']['created_at'] ? $sitelock_service['lastBackup']['db']['created_at'] : ''); ?>
                                                    </p>
                                                </div>
                                            <?php } ?>

                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } elseif ($sitelock_scan_result_url == 'spam_scan') { ?>
                                <div class="<?php echo ($sitelock_service['availability'] !== 'upgradable') ? 'md:col-span-6' : 'md:col-span-12'; ?>">
                                    <p class="mx-5 sm:mx-10 mr-5 mb-4 text-[14px] leading-[150%] font-normal text-[#6A6A6A]">
                                        <?php echo esc_html($sitelock_language_tokens['cloud_services_description']['spam_scan']); ?>
                                    </p>
                                </div>

                                <?php if ($sitelock_service['availability'] !== 'upgradable'): ?>
                                <div class="md:col-span-6">
                                    <div class="px-5 md:px-0">
                                        <?php if ($sitelock_last_scan_date): ?>
                                            <div class="flex justify-between items-center border-b border-[#EEEEEE]">
                                                <h4 class="my-2 scan-result-title text-[#000000]"><?php echo esc_html($sitelock_language_tokens['var']['lastScan']); ?></h4>
                                                <p class="my-2 scan-result-data text-[#333333] email-last-scan">
                                                    <?php echo esc_html($sitelock_last_scan_date); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex justify-between items-center not-last-child:border-b border-[#EEEEEE]">
                                            <h4 class="my-2 scan-result-title text-[#000000]"><?php echo esc_html($sitelock_language_tokens['var']['frequency']); ?>
                                            </h4>
                                            <p class="my-2 scan-result-data text-[#333333]"><?php echo esc_html($sitelock_service['frequency']); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif ?>
                            <?php } elseif ($sitelock_scan_result_url == 'patchman') { ?>
                                <div class="<?php echo ($sitelock_service['availability'] !== 'upgradable') ? 'md:col-span-6' : 'md:col-span-12'; ?> mx-5 sm:mx-10 ">
                                    <p class="text-[14px] mb-4 leading-[150%] font-normal text-[#6A6A6A]"><?php echo esc_html($sitelock_language_tokens['cloud_services_description']['patchman']); ?>
                                    </p>
                                    <?php 
                                    $sitelock_upgradable = isset($sitelock_service['upgradable']) ? $sitelock_service['upgradable'] : false;
                                    $sitelock_scan_result_status = isset($sitelock_service['status']) ? $sitelock_service['status'] : null;
                                    $sitelock_show_configure = isset($sitelock_service['showConfigure']) ? $sitelock_service['showConfigure'] : false;
                                    $sitelock_show_scan_now_button = $sitelock_scan_result_status !== 'failedBilling' && !$sitelock_upgradable && !$sitelock_show_configure;
                                    ?>
                                    <?php if ($sitelock_show_scan_now_button) { 
                                        $sitelock_next_available_scan_value = false; 
                                        if(isset($sitelock_service['nextAvailableScan']['value']) && !empty($sitelock_service['nextAvailableScan']['value']) && ($sitelock_service['nextAvailableScan']['value'] !== 'pending' || $sitelock_service['nextAvailableScan']['value'] !== 'NA')) {
                                            $sitelock_next_available_scan_value = true; 
                                        } ?>
                                        <div>
                                            <button data-type="<?php echo esc_attr($sitelock_scan_result_url); ?>" <?php if ($sitelock_next_available_scan_value)
                                                   echo esc_attr('disabled'); ?>
                                                class="scan-now-button w-[150px] text-[14px] bg-[#F6F9FE] py-[5px] text-center text-[#083C8C] hover:text-[#083C8C] rounded border-blue mb-3">
                                                <?php echo $sitelock_next_available_scan_value
                                                    ? '<img src="' . esc_url(plugin_dir_url(__DIR__) . '../images/pending.svg') . '" class="mr-2" alt="Pending Icon" /> ' . esc_html($sitelock_language_tokens['var']['scanPending'])
                                                    : esc_html($sitelock_language_tokens['var']['scanNow']); ?>
                                            </button>

                                            <?php if (!empty($sitelock_service['nextScheduledScan']['value'])): ?>
                                                <p
                                                    class="<?php echo !empty($sitelock_service['nextScheduledScan']['value']) ? 'my-2' : ''; ?> text-[14px] text-pretty">
                                                    <?php echo esc_html($sitelock_language_tokens['var']['automaticSchedule']); ?> <span
                                                        class="scan-result-title text-[#000000] schedule-scan"><?php echo esc_html($sitelock_service['nextScheduledScan']['value']); ?></span>
                                                </p>
                                            <?php endif; ?>
                                            <?php if (isset($sitelock_service['nextAvailableScan']['value']) && !empty($sitelock_service['nextAvailableScan']['value']) && $sitelock_service['nextAvailableScan']['value'] !== 'pending' && $sitelock_service['nextAvailableScan']['value'] !== 'NA' && empty($sitelock_service['nextScheduledScan']['value'])): ?>
                                                <p
                                                    class="<?php echo !empty($sitelock_service['nextAvailableScan']['value']) ? 'my-2' : ''; ?> text-[14px] text-pretty">
                                                    <?php echo esc_html($sitelock_language_tokens['var']['nextAvailableScan']); ?> <span
                                                        class="scan-result-title text-[#000000] schedule-scan"><?php echo esc_html($sitelock_service['nextAvailableScan']['value']); ?></span>
                                                </p>
                                            
                                            <?php endif; ?>
                                        </div>
                                    <?php
                                    } ?>
                                </div>
                                <?php if ($sitelock_service['availability'] !== 'upgradable'): ?>
                                <div class="md:col-span-6">
                                    <div class="px-5 md:px-0">
                                        <?php if ($sitelock_last_good_scan_date) { ?>
                                            <div class="flex justify-between items-center border-b border-[#EEEEEE]">
                                                <h4 class="my-2 scan-result-title text-[#000000]"><?php echo esc_html($sitelock_language_tokens['var']['lastGoodScan']); ?>
                                                </h4>
                                                <p class="my-2 scan-result-data text-[#333333] last-scan">
                                                    <?php echo esc_html($sitelock_last_good_scan_date); ?>
                                                </p>
                                            </div>
                                        <?php } ?>
                                        <div class="flex justify-between items-center border-b border-[#EEEEEE]">
                                            <h4
                                                class="my-2 scan-result-title <?php echo esc_attr(isset($sitelock_service['lastScan']['num_vulnerable']) && $sitelock_service['lastScan']['num_vulnerable'] > 0 ? 'text-red' : 'text-[#000000]'); ?> ">
                                                <?php echo esc_html($sitelock_language_tokens['var']['vulnerabilitiesFound']); ?>
                                            </h4>
                                            <p
                                                class="my-2 scan-result-data <?php echo esc_attr(isset($sitelock_service['lastScan']['num_vulnerable']) && $sitelock_service['lastScan']['num_vulnerable'] > 0 ? 'text-red' : 'text-[#333333]'); ?>">
                                                <?php echo esc_html(isset($sitelock_service['lastScan']['num_vulnerable'])                              && $sitelock_service['lastScan']['num_vulnerable'] ? $sitelock_service['lastScan']['num_vulnerable'] : 0); ?>
                                            </p>
                                        </div>
                                        <div class="flex justify-between items-center border-b border-[#EEEEEE]">
                                            <h4 class="my-2 scan-result-title text-[#000000]"><?php echo esc_html($sitelock_language_tokens['var']['reverted']); ?></h4>
                                            <p class="my-2 scan-result-data text-[#333333]">
                                                <?php echo esc_html($sitelock_service['lastScan']['num_reverted'] ?? 0); ?>
                                            </p>
                                        </div>
                                        <div class="flex justify-between items-center not-last-child:border-b border-[#EEEEEE]">
                                            <h4 class="my-2 scan-result-title text-[#000000]"><?php echo esc_html($sitelock_language_tokens['var']['patched']); ?></h4>
                                            <p class="my-2 scan-result-data text-[#333333]">
                                                <?php echo esc_html($sitelock_service['lastScan']['num_patched'] ?? 0); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif ?>
                            <?php } elseif ($sitelock_scan_result_url == 'waf') { ?>
                                <?php if ($sitelock_service['availability'] == 'upgradable') { ?>
                                    <div class="md:col-span-12">
                                        <p class="mx-5 sm:mx-10 text-[14px] leading-[150%] mb-4 font-normal text-[#6A6A6A]">
                                            <?php echo esc_html($sitelock_language_tokens['cloud_services_description']['waf']); ?>
                                        </p>
                                    </div>
                                <?php } else { ?>
                                    <div class="md:col-span-6">
                                        <p class="mx-5 sm:mx-10 mr-5 mb-4 text-[14px] leading-[150%] font-normal text-[#6A6A6A]">
                                            <?php echo esc_html($sitelock_language_tokens['cloud_services_description']['waf']); ?>
                                        </p>
                                    </div>
                                    <div class="md:col-span-6">
                                        <div class="px-5 md:px-0">
                                            <div class="w-full <?php echo esc_attr($sitelock_service['status'] === 'unconfigured' ? 'not-last-child:border-b' : 'border-b'); ?> border-[#EEEEEE] text-[0px]">
                                                <h4 class="w-1/2 inline-block align-top my-2 scan-result-title text-[#000000]">
                                                    <?php echo esc_html($sitelock_language_tokens['var']['status']); ?>
                                                </h4>
                                                <p class="w-1/2 inline-flex align-top my-2 scan-result-data text-[#333333]">
                                                    <span class="icon circle w-5 mr-2 align-middle <?php echo esc_attr($sitelock_language_tokens['service_status_map'][$sitelock_service['status']]); ?>"></span>
                                                    <?php echo esc_html($sitelock_language_tokens['service_status_tokens'][$sitelock_service['status'] === 'ok' ? 'active' : $sitelock_service['status']] ?? ''); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <!-- Traffic Routing status -->
                                        <?php if ($sitelock_service['status'] === 'ok') { ?>
                                            <div class="px-5 sm:px-10 md:px-0">
                                                <div class="w-full <?php echo esc_attr($sitelock_service['statuses']['routingSL'] ? 'border-b' : 'not-last-child:border-b') ?> border-[#EEEEEE] text-[0px]">
                                                    <h4 class="w-1/2 inline-block align-top my-2 scan-result-title text-[#000000]"><?php echo esc_html($sitelock_language_tokens['var']['trafficRouting']); ?></h4>
                                                    <p class="w-1/2 inline-flex align-top my-2 scan-result-data text-[#333333]">
                                                        <?php if ($sitelock_service['statuses']['routingSL']) { ?>
                                                            <span class="relative before:content-[''] before:inline-block before:w-[0.75rem] before:h-[0.75rem] before:rounded-full before:mr-2 before:bg-[#00AA6B]"></span>
                                                            <?php echo esc_html($sitelock_language_tokens['service_status_tokens']['sitelockNetwork']); ?>
                                                        <?php } else { ?>
                                                            <span class="relative before:content-[''] before:inline-block before:w-[0.75rem] before:h-[0.75rem] before:rounded-full before:mr-2 before:bg-[#FFD601]"></span>
                                                            <?php echo esc_html($sitelock_language_tokens['service_status_tokens']['bypassNetwork']); ?>
                                                        <?php } ?>
                                                    </p>
                                                </div>
                                            </div>

                                            <?php if ($sitelock_service['statuses']['routingSL']) { ?>
                                                <!-- CDN status -->
                                                <div class="px-5 sm:px-10 md:px-0">
                                                    <div class="w-full border-b border-[#EEEEEE] text-[0px]">
                                                        <h4 class="w-1/2 inline-block align-top my-2 scan-result-title text-[#000000]"><?php echo esc_html($sitelock_language_tokens['var']['cdn']); ?></h4>
                                                        <p class="w-1/2 inline-flex align-top my-2 scan-result-data text-[#333333] items-center">
                                                            <?php if (!empty($sitelock_service['statuses']['trueSpeed'])) { ?>
                                                                <span class="icon circle w-5 mr-2 <?php echo esc_attr($sitelock_language_tokens['service_status_map']['enabled']); ?>"></span>
                                                                <?php echo esc_html($sitelock_language_tokens['var']['enabled']); ?>
                                                            <?php } else { ?>
                                                                <span class="icon circle w-5 mr-2 <?php echo esc_attr($sitelock_language_tokens['service_status_map']['disabled']); ?>"></span>
                                                                <?php echo esc_html($sitelock_language_tokens['var']['disabled']); ?>
                                                            <?php } ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <!-- Firewall status -->
                                                <div class="px-5 sm:px-10 md:px-0">
                                                    <div class="w-full not-last-child:border-b border-[#EEEEEE] text-[0px]">
                                                        <h4 class="w-1/2 inline-block align-top my-2 scan-result-title text-[#000000]"><?php echo esc_html($sitelock_language_tokens['var']['firewall']); ?></h4>
                                                        <p class="w-1/2 inline-flex align-top my-2 scan-result-data text-[#333333] items-center">
                                                            <?php if (!empty($sitelock_service['statuses']['trueShield'])) { ?>
                                                                <span class="icon circle w-5 mr-2 <?php echo esc_attr($sitelock_language_tokens['service_status_map']['enabled']); ?>"></span>
                                                                <?php echo esc_html($sitelock_language_tokens['var']['enabled']); ?>
                                                            <?php } else { ?>
                                                                <span class="icon circle w-5 mr-2 <?php echo esc_attr($sitelock_language_tokens['service_status_map']['disabled']); ?>"></span>
                                                                <?php echo esc_html($sitelock_language_tokens['var']['disabled']); ?>
                                                            <?php } ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php endif; endforeach; ?>
            <div class="flex items-center gap-4">
                <a href='<?php echo esc_url(sitelock_api_url() . '/sites/' . esc_attr($sitelock_site_info['id']) . '/'); ?>' target="_blank"
                    class="px-6 text-[14px] text-center py-[7px] bg-[#2D68C4] text-[#fff] hover:text-[#fff] focus:text-[#fff] rounded ml-3 sm:ml-0 my-3">
                    <?php echo esc_html($sitelock_language_tokens['var']['viewFullReport']); ?>
                </a>
            </div>
        </div>

    <?php } ?>
</div>

<script>
    jQuery(document).ready(function($) {
        $(document).ready(function() {

            function hasTimezone(dateString) {
                // Check for timezone offset like -0500, +0530, or a Z for UTC
                return /([+-]\d{4}|Z)$/.test(dateString);
            }

            function hasTimeComponent(dateStr) {
                return /\d{2}:\d{2}:\d{2}/.test(dateStr);
            }

            function convertToISOWithOffset(dateString) {
                // If the string already has timezone info, return as is
                if (hasTimezone(dateString)) {
                    return dateString;
                }
                // Check if the date string is in "YYYY-MM-DD" format
                if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
                    dateString = dateString + "T00:00:00"; // Append time for ISO conversion
                }
                const isoLike = dateString.replace(' ', 'T');

                const date = new Date(isoLike);

                // Get offset in minutes for America/Chicago
                const formatter = new Intl.DateTimeFormat('en-US', {
                    timeZone: 'America/Chicago',
                    hour12: false,
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });

                const parts = formatter.formatToParts(date);
                const getPart = type => parts.find(p => p.type === type)?.value;

                const chicagoDateStr = `${getPart('year')}-${getPart('month')}-${getPart('day')}T${getPart('hour')}:${getPart('minute')}:${getPart('second')}`;
                const chicagoDate = new Date(chicagoDateStr + 'Z');
                const offsetMinutes = (date - chicagoDate) / 60000;

                const offsetSign = offsetMinutes > 0 ? '-' : '+';
                const offsetHours = String(Math.floor(Math.abs(offsetMinutes) / 60)).padStart(2, '0');
                const offsetMins = String(Math.abs(offsetMinutes) % 60).padStart(2, '0');

                return isoLike + offsetSign + offsetHours + ':' + offsetMins;
            }

            document.querySelectorAll('.last-scan').forEach(function(el) {
                let rawText = el.textContent.trim();
                let displayText = "";

                if (rawText) {
                    // Check if the date string has a timezone
                    if (!hasTimeComponent(rawText)) {
                        displayText = new Date(rawText).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: '2-digit',
                            timeZone: 'UTC'
                        });
                        el.textContent = displayText;
                        return;
                    }

                    // Convert to ISO format with timezone offset
                    rawText = convertToISOWithOffset(rawText);
                    let date;

                    // Handle full datetime with timezone offset
                    if (rawText.includes('T') && /[-+]\d{4}$/.test(rawText)) {
                        const fixedDateStr = rawText.replace(/([-+]\d{2})(\d{2})$/, '$1:$2');
                        date = new Date(fixedDateStr);
                    } else {
                        date = new Date(rawText);
                    }

                    const now = new Date();
                    const diffInMs = now - date;

                    const diffInSeconds = Math.floor(diffInMs / 1000);
                    const diffInMinutes = Math.floor(diffInMs / (1000 * 60));
                    const diffInHours = Math.floor(diffInMs / (1000 * 60 * 60));
                    const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24));

                    // Determine the display text based on the time difference
                    if (diffInSeconds < 60) {
                        displayText = `${diffInSeconds} second${diffInSeconds > 1 ? 's' : ''} ago`;
                    } else if (diffInMinutes < 60) {
                        displayText = `${diffInMinutes} minute${diffInMinutes > 1 ? 's' : ''} ago`;
                    } else if (diffInHours < 24) {
                        displayText = `${diffInHours} hour${diffInHours > 1 ? 's' : ''} ago`;
                    } else if (diffInDays === 1) {
                        displayText = "1 day ago";
                    } else if (diffInDays <= 7) {
                        displayText = `${diffInDays} days ago`;
                    } else {
                        displayText = date ? date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: '2-digit',
                            timeZone: 'UTC'
                        }) : "";
                    }
                }

                el.textContent = displayText;
            });

            document.querySelectorAll('.email-last-scan').forEach(function(el) {
                const rawLastScan = el.textContent.trim();
                let emailLastScanDate;
                emailLastScanDate = new Date(rawLastScan);
                let emailLastScandisplay;
                emailLastScandisplay = emailLastScanDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: '2-digit',
                    timeZone: 'UTC'
                });
                el.textContent = emailLastScandisplay;
            });

            document.querySelectorAll('.schedule-scan').forEach(function(el) {
                let rawText = el.textContent.trim();
                let displayText = "";

                if (rawText) {
                    // Check if the date string has a timezone
                    if (!hasTimeComponent(rawText)) {
                        displayText = new Date(rawText).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: '2-digit',
                            timeZone: 'UTC'
                        });
                        el.textContent = displayText;
                        return;
                    }

                    // Convert to ISO format with timezone offset
                    rawText = convertToISOWithOffset(rawText);
                    let date;

                    // Handle full datetime with timezone offset
                    if (rawText.includes('T') && /[-+]\d{4}$/.test(rawText)) {
                        const fixedDateStr = rawText.replace(/([-+]\d{2})(\d{2})$/, '$1:$2');
                        date = new Date(fixedDateStr);
                    } else {
                        date = new Date(rawText);
                    }

                    const now = new Date();
                    const diffInMs = date - now;

                    if (diffInMs > 0) {
                        const diffInSeconds = Math.floor(diffInMs / 1000);
                        const diffInMinutes = Math.floor(diffInMs / (1000 * 60));
                        const diffInHours = Math.floor(diffInMs / (1000 * 60 * 60));
                        const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24));

                        // Determine the display text based on the time difference
                        if (diffInSeconds < 60) {
                            displayText = `${diffInSeconds} second${diffInSeconds > 1 ? 's' : ''}`;
                        } else if (diffInMinutes < 60) {
                            displayText = `${diffInMinutes} minute${diffInMinutes > 1 ? 's' : ''}`;
                        } else if (diffInHours < 24) {
                            const remainingMinutes = diffInMinutes % 60;
                            displayText = `${diffInHours} hour${diffInHours !== 1 ? 's' : ''}` + (remainingMinutes > 0 ? `, ${remainingMinutes} minute${remainingMinutes !== 1 ? 's' : ''}` : '');
                        } else if (diffInDays <= 90) {
                            const remainingHours = diffInHours % 24;
                            const totalMinutes = Math.floor(diffInMs / (1000 * 60));
                            const remainingScheduleMinutes = totalMinutes - (diffInDays * 24 * 60 + remainingHours * 60);
                            displayText = `${diffInDays} day${diffInDays !== 1 ? 's' : ''}`;
                            if (remainingHours > 0) {
                                displayText += `, ${remainingHours} hour${remainingHours !== 1 ? 's' : ''}`;
                            } else if (remainingScheduleMinutes > 0) {
                                displayText += `, ${remainingScheduleMinutes} minute${remainingScheduleMinutes !== 1 ? 's' : ''}`;
                            }
                        } else {
                            displayText = date ? date.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: '2-digit',
                                timeZone: 'UTC'
                            }) : "";
                        }
                    } else {
                        el.parentElement.classList.add('hidden');
                    }
                }

                el.textContent = displayText;
            });

            // Prevent link redirect when clicking on the arrow
            $('.dropdown-arrow').click(function(e) {
                e.preventDefault(); // Prevent the anchor <a> link from navigating
                e.stopPropagation(); // Prevent the click event from bubbling up
                $(this).find('.arrow-toggle').toggleClass('arrow-rotate');

                const $wrapper = $(this).closest('.relative-wrapper');
                const $dropdown = $wrapper.find('.base-dropdown');
                const $arrow = $(this).find('.arrow-toggle');

                const isVisible = $dropdown.is(':visible');

                // Hide all others
                $('.base-dropdown').slideUp();
                $('.arrow-toggle').removeClass('arrow-rotate');

                if (!isVisible) {
                    $dropdown.slideDown();
                    $arrow.addClass('arrow-rotate');
                }
            });

            $(document).click(function(e) {
                if (!$(e.target).closest('.relative-wrapper').length) {
                    $('.base-dropdown').slideUp();
                    $('.arrow-toggle').removeClass('arrow-rotate');
                }
            });
        });
    });
</script>
