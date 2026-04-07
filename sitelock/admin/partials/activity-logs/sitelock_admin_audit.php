<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div id="need-help" class="simple-box border-[#DDDDDD] bg-[#fff] mb-5 mt-5">
    <!-- Report Body Section -->
    <div class="px-3 sm:px-5 min-h-[100px] pb-0 sm:pb-5 mt-5">
        <div class="overflow-x-auto">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html($sitelock_language_tokens['var']['timestamp']); ?></th>
                        <th scope="col"><?php echo esc_html($sitelock_language_tokens['var']['user']); ?></th>
                        <th scope="col"><?php echo esc_html($sitelock_language_tokens['var']['action']); ?></th>
                        <th scope="col"><?php echo esc_html($sitelock_language_tokens['var']['roleChange']); ?></th>
                        <th scope="col"><?php echo esc_html($sitelock_language_tokens['var']['source']); ?></th>
                        <th scope="col"><?php echo esc_html($sitelock_language_tokens['var']['trustLevel']); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($logs)) {
                        echo '<tr><td colspan="8" style="text-align: center;">' . esc_html($sitelock_language_tokens['var']['noAdminUserChanges']) . '</td></tr>';

                        return;
                    }
                    foreach ($logs as $sitelock_audit_log): ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($sitelock_audit_log->logged_at))); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $sitelock_audit_log->user_id)); ?>">
                                    <?php echo esc_html($sitelock_audit_log->username . ' (' . $sitelock_audit_log->user_id . ')'); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $sitelock_audit_log->action))); ?></td>
                            <?php
                            if ($sitelock_audit_log->action == 'role_changed') {
                                $sitelock_audit_role_before_text = empty(trim($sitelock_audit_log->role_before)) ? 'N/A' : ucfirst($sitelock_audit_log->role_before);
                                $sitelock_audit_role_after_text  = empty(trim($sitelock_audit_log->role_after)) ? 'N/A' : ucfirst($sitelock_audit_log->role_after);
                                $sitelock_audit_role_change_text = $sitelock_audit_role_before_text.' -> ' . $sitelock_audit_role_after_text;
                            } else {
                                $sitelock_audit_role_change_text = 'N/A';
                            }
                        ?>
                            <td><?php echo esc_html($sitelock_audit_role_change_text); ?></td>
                            <td><?php echo esc_html($sitelock_audit_log->context ?? 'unknown'); ?></td>
                            <td>
                                <?php
                            $sitelock_audit_log_color = ($sitelock_audit_log->is_suspicious === '0') ? '#00AA6B' : '#DB1010';
                        ?>
                                <div class="c2 flex items-center col-span-4">
                                    <div class="pr-2">
                                        <span class="icon circle w-5 bg-[<?php echo esc_attr($sitelock_audit_log_color); ?>]">
                                        </span>
                                    </div>
                                    <span class="capitalize analyzing status">
                                    <?php echo esc_html(($sitelock_audit_log->is_suspicious === '0') ? $sitelock_language_tokens['var']['trusted'] : $sitelock_language_tokens['var']['suspicious']); ?>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            $sitelock_audit_total_pages = ceil($total_items / $items_per_page);
 
if ($sitelock_audit_total_pages > 1) {
    $sitelock_audit_page_links = paginate_links([
        'base'      => add_query_arg(['paged' => '%#%', '_wpnonce' => $sitelock_nonce_global, 'log-type' => 'admin-audit']),
        'format'    => '',
        'prev_text' => esc_html('« '.$sitelock_language_tokens['var']['previous']),
        'next_text' => esc_html($sitelock_language_tokens['var']['next'].' »'),
        'total'     => $sitelock_audit_total_pages,
        'current'   => $paged,
        'type'      => 'array',
    ]);
 
    if (is_array($sitelock_audit_page_links)) {
        echo '<div class="tablenav"><div class="tablenav-pages"><span class="pagination-links" style="display: flex; justify-content: center;">';
        foreach ($sitelock_audit_page_links as $sitelock_audit_link) {
            echo '<span class="page-numbers" style="padding: 0px 10px 0px 10px;">' . wp_kses_post($sitelock_audit_link) . '</span> ';
        }
        echo '</span></div></div>';
    }
}
?>
        </div>
    </div>
</div>
