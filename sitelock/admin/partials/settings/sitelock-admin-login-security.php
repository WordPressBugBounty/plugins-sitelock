<div id="login-security" class="hidden simple-box border-grey-light bg-[#fff] mb-5 p-8 min-h-full xl:min-h-[500px]">
    <?php
    $two_fa_settings = get_option('sitelock_2fa_settings', [
        'enable_2fa'      => false,
        'mandatory_roles' => [],
        'grace_period'    => 7,
    ]);
    $two_fa_settings['enable_2fa']      = isset($two_fa_settings['enable_2fa']) ? $two_fa_settings['enable_2fa'] : false;
    $two_fa_settings['mandatory_roles'] = isset($two_fa_settings['mandatory_roles']) ? $two_fa_settings['mandatory_roles'] : [];
    $two_fa_settings['grace_period']    = isset($two_fa_settings['grace_period']) ? $two_fa_settings['grace_period'] : 7;
    ?>
    
    <div class="w-full">
    <h3 class="box-title mb-8"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['title']) ?></h3>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="sitelock_security_form_data">
    <input type="hidden" name="tab" value="sitelock_login_security">
        <?php
        wp_nonce_field('sitelock_login_security_action', 'sitelock_login_security_nonce');?>

        <div class="security-options">

            <div class="login-lockout-setting mb-5 border-b border-b-[#dddddd]">
                <div class="flex mb-5 py-[5px]">
                    <div class="lightswitch" data-id="login-lockout">
                        <input type="checkbox" id="sitelock_login_lockout_enabled" name="sitelock_login_lockout_enabled" value="1" <?php echo $sitelock_login_lockout_enabled == 1 ? 'checked' : '' ?> />
                        <span class="switch"><span></span></span>
                    </div>
                    <div class="option-info ml-2 w-full xl:w-[70%]">
                        <label for="sitelock_login_lockout_enabled" class="cursor-pointer">
                            <h2 class="tab-title mb-1 flex item-center"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_lockout']['title']) ?></h2>
                            <p class="tab-content-field-content"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_lockout']['description']) ?></p>
                        </label>
                    </div>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 pl-14 collapsed login-lockout-content" id="login-lockout">
                    <div class="w-full md:w-[234px]">
                        <h3 class="tab-title mb-2"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_lockout']['maxLoginAttempts']) ?></h3>
                        <p class="tab-content-field-content mb-4"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_lockout']['maxLoginDescription']) ?></p>
                        <div class="flex items-center mb-4">
                            <div class="number w-[80px] rounded">
                                <input type="number" name="sitelock_login_lockout_max_attempts"
                                    value="<?php echo esc_attr($sitelock_login_lockout_max_attempts); ?>" min="0" class="input-number h-[36px]" />
                                <span class="switch"><span></span></span>
                            </div>
                        </div>
                    </div>

                   <div class="w-full md:w-[234px]">
                    <h3 class="tab-title mb-2 flex item-center"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_lockout']['lockoutDuration']) ?></h3>
                        <p class="tab-content-field-content mb-4"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_lockout']['lockoutDescription']) ?></p>
                        <div class="flex items-center mb-4">
                            <div class="number w-[80px] rounded">
                                <input type="number" name="sitelock_login_lockout_duration"
                                    value="<?php echo esc_attr($sitelock_login_lockout_duration); ?>" min="0" class="input-number h-[36px]" />
                                <span class="switch"><span></span></span>
                            </div>
                            <div class="option-info ml-2">
                                <h2><?php echo esc_html($sitelock_language_tokens['var']['minutes']) ?></h2>
                            </div>
                        </div>
                   </div>

                   <div class="w-full md:w-[234px]">
                   <h3 class="tab-title mb-2"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_lockout']['resetTime']) ?></h3>
                    <p class="tab-content-field-content mb-4"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_lockout']['resetTimeDescription']) ?></p>
                    <div class="flex items-center mb-8">
                        <div class="number w-[80px] rounded">
                            <input type="number" name="sitelock_login_lockout_reset_time" value="<?php echo esc_attr($sitelock_login_lockout_reset_time); ?>"
                                min="0" class="input-number h-[36px]" />
                            <span class="switch"><span></span></span>
                        </div>
                        <div class="option-info ml-2">
                            <h2><?php echo esc_html($sitelock_language_tokens['var']['minutes']) ?></h2>
                        </div>
                    </div>
                   </div>
                </div>
            </div>


            <div class="force-logout-setting mb-5 border-b border-b-[#dddddd]">
                <div class="flex mb-5 py-[5px]">
                    <div class="lightswitch" data-id="force-logout">
                        <input type="checkbox" id="sitelock_force_logout_enabled" name="sitelock_force_logout_enabled" value="1"
                            <?php checked(1, $sitelock_force_logout_enabled, true); ?> />
                        <span class="switch"><span></span></span>
                    </div>
                    <div class="option-info ml-2 w-full xl:w-[70%]">
                        <label for="sitelock_force_logout_enabled" class="cursor-pointer">
                            <h2 class="tab-title mb-1 flex item-center"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['force_logouts']['title']) ?></h2>
                            <p class="tab-content-field-content"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['force_logouts']['description']) ?></p>
                        </label>
                    </div>

                </div>

                <div class="force-logout-content pl-14 collapsed" id="force-logout">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div class="mb-4">
                            <h3 class="tab-title mb-1"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['force_logouts']['excludedRoles']) ?></h3>
                            <p class="tab-content-field-content"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['force_logouts']['excludeRolesDescription']) ?></p>
                            <div class="block mt-5">
                                <?php
                                global $wp_roles;
    foreach ($wp_roles->roles as $role_slug => $role_info) {
        $checked = '';
        if ($sitelock_force_logout_excluded_roles) {
            $checked = in_array($role_slug, $sitelock_force_logout_excluded_roles) ? 'checked' : '';
        } ?>
                                <div class="mb-3">
                                    <div class="flex">
                                        <input type="checkbox" id="sitelock_force_logout_role_<?php echo esc_attr($role_slug); ?>" name="sitelock_force_logout_excluded_roles[]" value="<?php echo esc_attr($role_slug); ?>" 
                                        <?php echo esc_attr($checked); ?> />
                                        <label for="sitelock_force_logout_role_<?php echo esc_attr($role_slug); ?>" class="ml-3 cursor-pointer">
                                            <h2 class="tab-content-field-title"><?php echo esc_html($role_info['name']); ?></h2>
                                        </label>
                                    </div>
                                </div>
                                <?php
    }
    ?>
                            </div>
                        </div>

                        <div class="mb-4 w-full md:w-[70%]">
                            <h3 class="tab-title mb-2"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['force_logouts']['timePeriod']) ?></h3>
                            <p class="tab-content-field-content"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['force_logouts']['timePeriodDescription']) ?></p>
                            <div class="flex items-center my-5">
                                <div class="number">
                                    <select name="sitelock_force_logout_duration" class="w-[160px] h-[36px]">
                                        <?php
            $current_value = get_option('sitelock_force_logout_duration', 12); // 12 as default
    foreach ([4, 8, 12, 24] as $hours): ?>
                                            <option value="<?php echo esc_attr($hours); ?>" <?php selected($current_value, $hours); ?>>
                                                <?php echo esc_html($hours . ' ' . $sitelock_language_tokens['var']['hours']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="password-strength-setting mb-6 border-b border-b-[#dddddd]">
                <div class="flex py-[5px]">
                    <div class="lightswitch" data-id="password-strength">
                        <input type="checkbox" id="sitelock_password_strength_enabled" name="sitelock_password_strength_enabled" value="1"
                            <?php checked(1, $sitelock_password_strength_enabled, true); ?> />
                        <span class="switch"><span></span></span>
                    </div>
                    <div class="option-info ml-2 w-full xl:w-[70%]">
                        <label for="sitelock_password_strength_enabled" class="cursor-pointer">
                            <h2 class="tab-title mb-1 flex item-center"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['password_strength_enforcement']['title']) ?></h2>
                            <p class="tab-content-field-content"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['password_strength_enforcement']['description']) ?></p>
                        </label>
                    </div>

                </div>

                <div class="password-strength-content ml-14 mb-4 pt-2 collapsed" id="password-strength">
                    <h2 class="tab-title mb-1"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['password_strength_enforcement']['minimunStrength']) ?></h2>
                    <p class="tab-content-field-content mb-3"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['password_strength_enforcement']['minimumStrengthDescription']) ?></p>
                    <div class="overflow-x-auto">
                        <table class="password-user-roles mt-5 mb-3 min-w-max table-fixed border-separate border-spacing-0">
                            <thead>
                                <tr class="bg-white">
                                    <th class="w-32 p-3 text-left "></th>
                                    <?php foreach (['Disabled', 'Medium', 'Strong'] as $strength): ?>
                                        <th class="p-3 text-center border-l border-[#F0F0F0] font-normal text-[16px]">
                                            <?php echo esc_html($strength); ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                global $wp_roles;
    foreach ($wp_roles->roles as $role_slug => $role_info) {
        $selected_role_strength = $sitelock_password_strength_user_roles[$role_slug] ?? 'medium';
        $strength_levels        = ['weak', 'medium', 'strong']; ?>
                                    <tr>
                                        <td class="px-1 lg:pr-12 pl-3 py-3 font-medium tab-content-field-title ">
                                            <?php echo esc_html($role_info['name']); ?>
                                        </td>

                                        <!-- radio columns -->
                                        <?php foreach ($strength_levels as $level): ?>
                                            <td class="p-3 text-center border-l border-[#F0F0F0]">
                                                <input 
                                                    type="radio"
                                                    id="sitelock_password_strength_user_roles_<?php echo esc_attr($role_slug); ?>_<?php echo esc_attr($level); ?>"
                                                    name="sitelock_password_strength_user_roles[<?php echo esc_attr($role_slug); ?>]" 
                                                    value="<?php echo esc_attr($level); ?>" 
                                                    <?php echo $selected_role_strength === $level ? 'checked' : ''; ?> 
                                                />
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php
    } ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
            <div class="tab-login-activity my-5">
                <h3 class="mb-2 flex item-center"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_activity_log']['title']) ?></h3>
                <p class="mb-2"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_activity_log']['description']) ?></p>
            </div>
            <div class="mb-7">
                <h3 class="tab-content-field mb-5 flex item-center"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_activity_log']['enableRoles']) ?></h3>
                <?php foreach ($roles as $role_key => $role_data): ?>
                <div class="mb-3">
                    <div class="flex">
                        <input type="checkbox" id="sitelock_login_logger_roles_<?php echo esc_attr($role_key); ?>" name="sitelock_login_logger_roles[]" value="<?php echo esc_attr($role_key); ?>" 
                        <?php checked(in_array($role_key, $sitelock_enabled_roles)); ?> />
                        <label for="sitelock_login_logger_roles_<?php echo esc_attr($role_key); ?>" class="ml-3 cursor-pointer">
                        <h2 class="tab-content-field-title"><?php echo esc_html($role_data['name']); ?></h2>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <h3 class="tab-content-field flex item-center mt-4 mb-3"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_activity_log']['loginRetentionPeriod']) ?></h3>
            <div class="mb-7">
                <div class="number mb-3">
                    <select name="sitelock_login_logger_retention" class="w-[160px] h-[36px] py-[8px] rounded">
                        <option class="sitelock_login_logger_retention_period mb-2" disabled><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_activity_log']['selectPeriod']) ?></option>
                        <?php foreach ([7, 30, 90] as $days): ?>
                            <option value="<?php echo esc_attr($days); ?>" <?php selected(get_option('sitelock_login_logger_retention'), $days); ?>>
                                <?php echo esc_html($days . ' ' . $sitelock_language_tokens['var']['days']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="tab-content-field-content text-[#575757]"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_activity_log']['loginRetentionPeriodDescription']) ?></p>
            </div>
        </div>
        <div class="mb-7">
            <button type="submit" class="w-[118px] h-[36px] bg-[#2D68C4] text-[#fff] rounded"><?php echo esc_html($sitelock_language_tokens['var']['saveChanges']) ?></button>
        </div>             
    </form>
    </div>
</div>


<script>
jQuery(document).ready(function($) {

    $('#login-security .lightswitch').each(function() {
        var input = $(this).find('input').first();
        if (input.prop('checked')) {
            var dataid = $(this).data('id');
            const $defaultActiveElement = $('#' + dataid);
            $defaultActiveElement.toggleClass('expanded');
        }
    });

    // Use event delegation for better performance and to avoid multiple bindings
    $('#login-security').on('click', '.lightswitch', function() {
        const id = $(this).data('id');
        const $activeElement = $('#' + id);
        $activeElement.toggleClass('expanded');
    });

    // Add functionality for label click
    $('#login-security').on('click', '.option-info', function() {
        const inputId = $(this).find('label').attr('for');
        if (inputId) {
            const $input = $('#' + inputId);
            const $activeElement = $('#' + $input);
            $activeElement.toggleClass('expanded');          
        }
    });

});
</script>
