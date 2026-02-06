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
                        <th scope="col"><?php echo esc_html__('Role(s)', 'sitelock-wordpress-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('IP Address', 'sitelock-wordpress-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('Status', 'sitelock-wordpress-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('User Agent', 'sitelock-wordpress-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($data['logs'])) {
                        echo '<td colspan="6" style="text-align: center;">' . esc_html__('No login logs detected yet.', 'sitelock-wordpress-plugin') . '</td>';

                        return;
                    }
                    $user = [];
foreach ($data['logs'] as $log):
    if (!isset($user[$log->user_id])) {
        $user[$log->user_id] = get_userdata((int) $log->user_id);
    }
    $user_data = $user[$log->user_id];
    $username  = $user_data ? $user_data->user_login : 'Deleted User';
    ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->logged_at))); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $log->user_id)); ?>">
                                    <?php echo esc_html($username . ' (' . $log->user_id . ')'); ?>
                                </a>
                            </td></a>
                            <td><?php echo esc_html(implode(', ', unserialize($log->roles))); ?></td>
                            <td><?php echo esc_html($log->ip_address); ?></td>
                            <td>
                                <?php if ($log->status === 'success'): ?>
                                    <div class="c2 flex items-center col-span-4">
                                        <div class="pr-2">
                                            <span class="icon circle w-5 bg-[#00AA6B]">
                                            </span>
                                        </div>
                                        <span class="capitalize analyzing status">
                                            <?php echo 'Success' ?>
                                        </span>
                                    </div>
                                    
                                <?php elseif ($log->status === 'failure'): ?>
                                    <div class="c2 flex items-center col-span-4">
                                        <div class="pr-2">
                                            <span class="icon circle w-5 bg-[#DB1010]">
                                            </span>
                                        </div>
                                        <span class="capitalize analyzing status">
                                            <?php echo 'Failed' ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span style="color: gray; font-weight: bold;"><?php echo esc_html__('Unknown', 'sitelock-wordpress-plugin'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($log->user_agent); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            // Pagination logic
            $total_pages = ceil($data['total_items'] / $data['items_per_page']);

if ($total_pages > 1) {
    $page_links = paginate_links([
        'base'      => add_query_arg(['paged' => '%#%', '_wpnonce' => $nonce, 'log-type' => 'login-activity']),
        'format'    => '',
        'prev_text' => esc_html__('« Previous', 'sitelock-wordpress-plugin'),
        'next_text' => esc_html__('Next »', 'sitelock-wordpress-plugin'),
        'total'     => $total_pages,
        'current'   => $data['paged'],
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