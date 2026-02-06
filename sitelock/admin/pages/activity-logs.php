<div class="sitelock-wrapper">
    <div class="container px-3 my-3">
        <?php include plugin_dir_path(__FILE__) . '../partials/common/sitelock-admin-header.php'; ?>
        <?php include plugin_dir_path(__FILE__) . '../partials/common/sitelock-admin-tabs.php'; ?>
        <div id="report-section">
            <?php
            // Create a nonce for security
            $nonce = wp_create_nonce('sitelock_activity_logs_nonce');
        ?>
            <script>
                // Pass the nonce to the JavaScript file
                const sitelockActivityLogsNonce = "<?php echo esc_js($nonce); ?>";
            </script>
            <div class="mt-5 mb-10">
                <label for="select-log" class="font-semibold text-[15px]">Select Log: </label>
                <select name="report_type" id="select-log" class="min-w-[240px] w-60 pl-[8px] ml-2 text-[15px] leading-[18px] h-[31px]">
                    <option value="login-activity" <?php echo selected($data['report_type'], 'login-activity', false); ?>><?php echo esc_html__('Login Activity', 'sitelock-wordpress-plugin') . ' (' . esc_html($data['login_logs_total_rows']) . ')'; ?></option>
                    <option value="admin-audit" <?php echo selected($data['report_type'], 'admin-audit', false); ?>><?php echo esc_html__('Admin Audit Log', 'sitelock-wordpress-plugin') . ' (' . esc_html($data['admin_audit_total_rows']) . ')'; ?></option>
                </select>
            </div>
            <!-- Report Title Section -->
            <div class="simple-box mt-5 mb-5 border-none">
                <h4 class="box-title mb-4">
                    <?php echo esc_html($data['report_title']); ?>
                </h4>
                <p class="mb-8 text-[16px] leading-[14px] max-[576px]:leading-[18px]">
                <?php echo esc_html($data['report_description']); ?>
                </p>
            </div>
            <div class="filters-row flex items-center mb-3">
                <!-- Date filter dropdown -->
                <div class="filter-section max-w-[300px] mr-1">
                    <select name="date_filter" id="date-filter" class="min-w-[102px] pl-[8px] text-[15px] leading-[18px] h-[31px]">
                        <option value="" <?php echo selected($data['date_filter'] ?? '', '', false); ?>>
                            <?php echo esc_html__('All dates', 'sitelock-wordpress-plugin'); ?>
                        </option>
                        <option value="last_7" <?php echo selected($data['date_filter'] ?? '', 'last_7', false); ?>>
                            <?php echo esc_html__('Last 7 days', 'sitelock-wordpress-plugin'); ?>
                        </option>
                        <option value="custom" <?php echo selected($data['date_filter'] ?? '', 'custom', false); ?>>
                            <?php echo esc_html__('Custom', 'sitelock-wordpress-plugin'); ?>
                        </option>
                    </select>
                </div>

                <!-- Custom date range (hidden unless Custom selected) -->
                <div id="custom-date-range" class="filter-section flex items-center mr-1" style="<?php echo (($data['date_filter'] ?? '') === 'custom') ? '' : 'display:none;'; ?>">
                    <label for="start-date" class="sr-only"><?php esc_html_e('Start date', 'sitelock-wordpress-plugin'); ?></label>
                    <input type="date" id="start-date" name="start_date" class="border px-2 text-[15px] leading-[14px] h-[31px]" value="<?php echo esc_attr($data['start_date'] ?? ''); ?>" />

                    <span aria-hidden="true">—</span>

                    <label for="end-date" class="sr-only"><?php esc_html_e('End date', 'sitelock-wordpress-plugin'); ?></label>
                    <input type="date" id="end-date" name="end_date" class="border px-2 text-[15px] leading-[14px] h-[31px]" value="<?php echo esc_attr($data['end_date'] ?? ''); ?>" />
                </div>

                <!-- Trust filter (no onchange) -->
                <div class="filter-section max-w-[250px] mr-1">
                    <select name="status_filter" id="status-filter" class="min-w-[129px] pl-[8px] text-[15px] leading-[18px] h-[31px]">
                        <?php
                    foreach ($status_options as $index => $option) {
                        ?>
                        <option value="<?php echo esc_html($data['status_values'][$index]); ?>" <?php echo selected($data['status_filter'] ?? '', esc_html($data['status_values'][$index]), false); ?>>
                            <?php echo esc_html($option); ?>
                        </option>
                        <?php
                    }
        ?>
                    </select>
                </div>


                <!-- Filter button -->
                <div class="filter-section">
                    <button id="apply-filters" type="button" class="button button-lite">
                        <?php echo esc_html__('Filter', 'sitelock-wordpress-plugin'); ?>
                    </button>
                </div>
                <?php
                $items = 0;
        if ($data['report_type'] == 'admin-audit') {
            $items = $data['admin_audit_total_rows'];
        } elseif ($data['report_type'] == 'login-activity') {
            $items = $data['login_logs_total_rows'];
        }
        ?>
                <div class="filter-section ml-auto pl-[10px]">
                    <p class="text-[14px] leading-[14px]"><?php echo esc_html($items). ' ' . esc_html__('items', 'sitelock-wordpress-plugin'); ?></p>
                </div>
            </div>
            <!-- Report Content Section -->
            <?php include $data['report_file']; ?>
        </div>

    </div>
</div>