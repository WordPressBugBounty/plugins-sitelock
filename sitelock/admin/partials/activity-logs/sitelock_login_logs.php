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
                        <th scope="col"><?php echo esc_html($sitelock_language_tokens['var']['roles']); ?></th>
                        <th scope="col"><?php echo esc_html($sitelock_language_tokens['var']['ipAddress']); ?></th>
                        <th scope="col"><?php echo esc_html($sitelock_language_tokens['var']['status']); ?></th>
                        <th scope="col"><?php echo esc_html($sitelock_language_tokens['var']['userAgent']); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($data['logs'])) {
                        echo '<td colspan="6" style="text-align: center;">' . esc_html($sitelock_language_tokens['var']['noLoginActivity']) . '</td>';

                        return;
                    }
                    $sitelock_login_user_cache = [];
foreach ($data['logs'] as $sitelock_login_log):
    if (!isset($sitelock_login_user_cache[$sitelock_login_log->user_id])) {
        $sitelock_login_user_cache[$sitelock_login_log->user_id] = get_userdata((int) $sitelock_login_log->user_id);
    }
    $sitelock_login_user_data = $sitelock_login_user_cache[$sitelock_login_log->user_id];
    $sitelock_login_username  = $sitelock_login_user_data ? $sitelock_login_user_data->user_login : 'Deleted User';
    ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($sitelock_login_log->logged_at))); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $sitelock_login_log->user_id)); ?>">
                                    <?php echo esc_html($sitelock_login_username . ' (' . $sitelock_login_log->user_id . ')'); ?>
                                </a>
                            </td></a>
                            <td><?php echo esc_html(implode(', ', unserialize($sitelock_login_log->roles))); ?></td>
                            <td><?php echo esc_html($sitelock_login_log->ip_address); ?></td>
                            <td>
                                <?php if ($sitelock_login_log->status === 'success'): ?>
                                    <div class="c2 flex items-center col-span-4">
                                        <div class="pr-2">
                                            <span class="icon circle w-5 bg-[#00AA6B]">
                                            </span>
                                        </div>
                                        <span class="capitalize analyzing status">
                                            <?php echo 'Success' ?>
                                        </span>
                                    </div>
                                    
                                <?php elseif ($sitelock_login_log->status === 'failure'): ?>
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
                                    <span style="color: gray; font-weight: bold;"><?php echo esc_html($sitelock_language_tokens['var']['unknown']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($sitelock_login_log->user_agent); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            // Pagination logic
            $sitelock_login_total_pages = ceil($data['total_items'] / $data['items_per_page']);

if ($sitelock_login_total_pages > 1) {
    $sitelock_login_page_links = paginate_links([
        'base'      => add_query_arg(['paged' => '%#%', '_wpnonce' => $sitelock_nonce_global, 'log-type' => 'login-activity']),
        'format'    => '',
        'prev_text' => esc_html('« '.$sitelock_language_tokens['var']['previous']),
        'next_text' => esc_html($sitelock_language_tokens['var']['next'].' »'),
        'total'     => $sitelock_login_total_pages,
        'current'   => $data['paged'],
        'type'      => 'array',
    ]);

    if (is_array($sitelock_login_page_links)) {
        echo '<div class="tablenav"><div class="tablenav-pages"><span class="pagination-links" style="display: flex; justify-content: center;">';
        foreach ($sitelock_login_page_links as $sitelock_login_link) {
            echo '<span class="page-numbers" style="padding: 0px 10px 0px 10px;">' . wp_kses_post($sitelock_login_link) . '</span> ';
        }
        echo '</span></div></div>';
    }
}
?>
        </div>
    </div>
</div>