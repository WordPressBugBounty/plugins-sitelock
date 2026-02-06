<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div id="need-help" class="simple-box border-grey-light bg-[#fff] mb-5 mt-5">
    <!-- Report Body Section -->
    <div class="px-3 sm:px-5 min-h-[100px] pb-0 sm:pb-5 mt-5">
        <div class="overflow-x-auto">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__('Timestamp', 'sitelock-wordpress-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('User', 'sitelock-wordpress-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('Action', 'sitelock-wordpress-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('Role Change', 'sitelock-wordpress-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('Source', 'sitelock-wordpress-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('Trust Level', 'sitelock-wordpress-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($logs)) {
                        echo '<tr><td colspan="8" style="text-align: center;">' . esc_html__('No admin user changes detected yet.', 'sitelock-wordpress-plugin') . '</td></tr>';

                        return;
                    }
                    foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->logged_at))); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $log->user_id)); ?>">
                                    <?php echo esc_html($log->username . ' (' . $log->user_id . ')'); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $log->action))); ?></td>
                            <?php
                            if ($log->action == 'role_changed') {
                                $role_before_text = empty(trim($log->role_before)) ? 'N/A' : ucfirst($log->role_before);
                                $role_after_text  = empty(trim($log->role_after)) ? 'N/A' : ucfirst($log->role_after);
                                $role_change_text = $role_before_text.' -> ' . $role_after_text;
                            } else {
                                $role_change_text = 'N/A';
                            }
                        ?>
                            <td><?php echo esc_html($role_change_text); ?></td>
                            <td><?php echo esc_html($log->context ?? 'unknown'); ?></td>
                            <td>
                                <?php
                            $color = ($log->is_suspicious === '0') ? '#00AA6B' : '#DB1010';
                        ?>
                                <div class="c2 flex items-center col-span-4">
                                    <div class="pr-2">
                                        <span class="icon circle w-5 bg-[<?php echo esc_attr($color); ?>]">
                                        </span>
                                    </div>
                                    <span class="capitalize analyzing status">
                                    <?php echo esc_html(($log->is_suspicious === '0') ? __('Trusted', 'sitelock-wordpress-plugin') : __('Suspicious', 'sitelock-wordpress-plugin')); ?>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            $total_pages = ceil($total_items / $items_per_page);

if ($total_pages > 1) {
    $page_links = paginate_links([
        'base'      => add_query_arg(['paged' => '%#%', '_wpnonce' => $nonce, 'log-type' => 'admin-audit']),
        'format'    => '',
        'prev_text' => __('« Previous', 'sitelock-wordpress-plugin'),
        'next_text' => __('Next »', 'sitelock-wordpress-plugin'),
        'total'     => $total_pages,
        'current'   => $paged,
        'type'      => 'array',
    ]);

    if (is_array($page_links)) {
        echo '<div class="tablenav"><div class="tablenav-pages"><span class="pagination-links" style="display: flex; justify-content: center;">';
        foreach ($page_links as $link) {
            echo '<span class="page-numbers" style="padding: 0px 10px 0px 10px;">' . wp_kses_post($link) . '</span> ';
        }
        echo '</span></div></div>';
    }
}
?>
        </div>
    </div>
</div>
