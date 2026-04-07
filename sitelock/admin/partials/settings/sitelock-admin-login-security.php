<div id="login-security" class="hidden simple-box border-[#DDDDDD] bg-[#fff] mb-5 p-8 min-h-full xl:min-h-[500px]">
  <div class="mb-6 w-full xl:w-[595px] box-title text-[30px]">
        <h3 class="mb-5"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['title']) ?></h3>
        <p class="text-[14px]"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['description']) ?></p>
  </div>
    <?php
    $sitelock_two_fa_settings = get_option('sitelock_2fa_settings', [
        'enable_2fa'      => false,
        'mandatory_roles' => [],
        'grace_period'    => 7,
    ]);
    $sitelock_two_fa_settings['enable_2fa']      = isset($sitelock_two_fa_settings['enable_2fa']) ? $sitelock_two_fa_settings['enable_2fa'] : false;
    $sitelock_two_fa_settings['mandatory_roles'] = isset($sitelock_two_fa_settings['mandatory_roles']) ? $sitelock_two_fa_settings['mandatory_roles'] : [];
    $sitelock_two_fa_settings['grace_period']    = isset($sitelock_two_fa_settings['grace_period']) ? $sitelock_two_fa_settings['grace_period'] : 7;
    ?>
    <div class="w-full">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="sitelock_security_form_data">
    <input type="hidden" name="tab" value="sitelock_login_security">
        <?php
        wp_nonce_field('sitelock_login_security_action', 'sitelock_login_security_nonce');?>

        <div class="security-options">
            <div class="2fa-setting mb-5 border-b border-b-[#dddddd]">
                <div class="flex mb-5 py-[5px]">
                    <div class="lightswitch" data-id="2fa">
                        <input type="checkbox" id="sitelock_2fa_enable" name="sitelock_2fa_settings[enable_2fa]" value="1"
                            <?php checked(1, $sitelock_two_fa_settings['enable_2fa'], true); ?> />
                        <span class="switch"><span>
                    </div>
                    <div class="option-info two-fa ml-2 w-full xl:w-[70%]">
                        <label for="sitelock_2fa_enable" class="cursor-pointer">
                            <h2 class="tab-title mb-1 flex item-center"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['2fa']['title']) ?></h2>
                            <p class="tab-content-field-content"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['2fa']['description']) ?></p>
                        </label>
                    </div>
                </div>
                <div class="2fa-contents pl-14 collapsed" id="2fa">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div class="mb-4">
                            <h3 class="tab-title mb-1"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['2fa']['requiredRole']) ?></h3>
                            <p class="tab-content-field-content"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['2fa']['enabledRoles']) ?></p>
                            <div class="block mt-5">
                                <?php
                                global $wp_roles;

                                $allowed_roles = ['administrator', 'editor', 'author', 'contributor', 'shop_manager'];

                                foreach ($wp_roles->roles as $role_slug => $role_info) {
                                    $checked = in_array($role_slug, $sitelock_two_fa_settings['mandatory_roles']) ? 'checked' : '';
                                ?> 
                                <div class="mb-3">
                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            id="sitelock_2fa_role_<?php echo esc_attr($role_slug); ?>"
                                            name="sitelock_2fa_settings[mandatory_roles][]"
                                            value="<?php echo esc_attr($role_slug); ?>"
                                            <?php echo esc_attr($checked); ?>
                                            <?php echo !in_array($role_slug, $allowed_roles) ? 'disabled' : ''; ?>
                                        />

                                        <label
                                            for="sitelock_2fa_role_<?php echo esc_attr($role_slug); ?>"
                                            class="ml-3 <?php echo !in_array($role_slug, $allowed_roles) ? 'cursor-not-allowed' : 'cursor-pointer'; ?>"
                                        >
                                            <h2 class="tab-content-field-title <?php echo !in_array($role_slug, $allowed_roles) ? '!text-[#0000006B]' : ''; ?>">
                                                <?php echo esc_html($role_info['name']); ?>
                                                <?php echo !in_array($role_slug, $allowed_roles) ? '<span>*</span>' : ''; ?>
                                            </h2>
                                        </label>
                                    </div>
                                </div>
                                <?php
                                }
                                ?>
                                <p class="w-full md:w-[291px] my-2"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['2fa']['disableRoleInfo']) ?></p>
                            </div>
                        </div>
                        <div class="mb-4 w-full md:w-[80%]">
                            <h3 class="tab-title mb-1"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['2fa']['gracePeriod']) ?></h3>
                            <p class="tab-content-field-content"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['2fa']['gracePeriodDescription']) ?></p>
                            <div class="flex items-center mt-5">
                                <div class="number w-[80px] rounded">
                                    <input type="number" name="sitelock_2fa_settings[grace_period]"
                                        value="<?php echo esc_attr($sitelock_two_fa_settings['grace_period']); ?>" min="0" class="input-number h-[36px]" />
                                    <span class="switch"><span>
                                </div>
                                <div class="option-info ml-3">
                                    <h2 class="tab-content-field-content mt-2"><?php echo esc_html($sitelock_language_tokens['var']['days']) ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
             <!-- 2FA Disable Confirmation Modal -->
            <!-- 2FA Disable Confirmation Modal -->
            <?php
            $modal_id            = '2fa-disable-confirmation';
            $title               = $sitelock_language_tokens['login_security_list_settings']['2fa_disable_modal']['title'];
            $warning_text        = $sitelock_language_tokens['login_security_list_settings']['2fa_disable_modal']['description'];
            $message_text        = $sitelock_language_tokens['login_security_list_settings']['2fa_disable_modal']['message'];
            $show_list           = true;
            $list_items          = $sitelock_language_tokens['login_security_list_settings']['2fa_disable_modal']['disableFactors'];
            $show_input          = true;
            $input_label         = $sitelock_language_tokens['login_security_list_settings']['2fa_disable_modal']['confirmationLabel'];
            $confirm_button_id   = 'disable-confirmation-button';
            $confirm_button_type = 'button';
            $cancel_button_text  = $sitelock_language_tokens['login_security_list_settings']['2fa_disable_modal']['cancelButton'];
            $confirm_button_text = $sitelock_language_tokens['login_security_list_settings']['2fa_disable_modal']['confirmButton'];

            include __DIR__ . '/sitelock-admin-disable-2fa-modal.php';
            ?>
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
    foreach ($wp_roles->roles as $sitelock_role_slug => $sitelock_role_info) {
        $sitelock_checked_state = '';
        if ($sitelock_force_logout_excluded_roles) {
            $sitelock_checked_state = in_array($sitelock_role_slug, $sitelock_force_logout_excluded_roles) ? 'checked' : '';
        } ?>
                                <div class="mb-3">
                                    <div class="flex">
                                        <input type="checkbox" id="sitelock_force_logout_role_<?php echo esc_attr($sitelock_role_slug); ?>" name="sitelock_force_logout_excluded_roles[]" value="<?php echo esc_attr($sitelock_role_slug); ?>" 
                                        <?php echo esc_attr($sitelock_checked_state); ?> />
                                        <label for="sitelock_force_logout_role_<?php echo esc_attr($sitelock_role_slug); ?>" class="ml-3 cursor-pointer">
                                            <h2 class="tab-content-field-title"><?php echo esc_html($sitelock_role_info['name']); ?></h2>
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
            $sitelock_current_duration = get_option('sitelock_force_logout_duration', 12); // 12 as default
    foreach ([4, 8, 12, 24] as $sitelock_hour_option): ?>
                                            <option value="<?php echo esc_attr($sitelock_hour_option); ?>" <?php selected($sitelock_current_duration, $sitelock_hour_option); ?>>
                                                <?php echo esc_html($sitelock_hour_option . ' ' . $sitelock_language_tokens['var']['hours']); ?>
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
                                    <?php foreach (['Disabled', 'Medium', 'Strong'] as $sitelock_strength_label): ?>
                                        <th class="p-3 text-center border-l border-[#F0F0F0] font-normal text-[16px]">
                                            <?php echo esc_html($sitelock_strength_label); ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                global $wp_roles;
    foreach ($wp_roles->roles as $sitelock_role_slug => $sitelock_role_info) {
        $sitelock_selected_role_strength = $sitelock_password_strength_user_roles[$sitelock_role_slug] ?? 'medium';
        $sitelock_strength_levels        = ['weak', 'medium', 'strong']; ?>
                                    <tr>
                                        <td class="px-1 lg:pr-12 pl-3 py-3 font-medium tab-content-field-title ">
                                            <?php echo esc_html($sitelock_role_info['name']); ?>
                                        </td>
 
                                        <!-- radio columns -->
                                        <?php foreach ($sitelock_strength_levels as $sitelock_strength_level): ?>
                                            <td class="p-3 text-center border-l border-[#F0F0F0]">
                                                <input 
                                                    type="radio"
                                                    id="sitelock_password_strength_user_roles_<?php echo esc_attr($sitelock_role_slug); ?>_<?php echo esc_attr($sitelock_strength_level); ?>"
                                                    name="sitelock_password_strength_user_roles[<?php echo esc_attr($sitelock_role_slug); ?>]" 
                                                    value="<?php echo esc_attr($sitelock_strength_level); ?>" 
                                                    <?php echo $sitelock_selected_role_strength === $sitelock_strength_level ? 'checked' : ''; ?> 
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
                <?php foreach ($sitelock_roles as $sitelock_role_key => $sitelock_role_data): ?>
                <div class="mb-3">
                    <div class="flex">
                        <input type="checkbox" id="sitelock_login_logger_roles_<?php echo esc_attr($sitelock_role_key); ?>" name="sitelock_login_logger_roles[]" value="<?php echo esc_attr($sitelock_role_key); ?>" 
                        <?php checked(in_array($sitelock_role_key, $sitelock_enabled_roles)); ?> />
                        <label for="sitelock_login_logger_roles_<?php echo esc_attr($sitelock_role_key); ?>" class="ml-3 cursor-pointer">
                        <h2 class="tab-content-field-title"><?php echo esc_html($sitelock_role_data['name']); ?></h2>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <h3 class="tab-content-field flex item-center mt-4 mb-3"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_activity_log']['loginRetentionPeriod']) ?></h3>
            <div class="mb-7">
                <div class="number mb-3">
                    <select name="sitelock_login_logger_retention" class="w-[160px] h-[36px] py-[8px] rounded">
                        <?php foreach ([7, 30, 90] as $sitelock_retention_days): ?>
                            <option value="<?php echo esc_attr($sitelock_retention_days); ?>" <?php selected(get_option('sitelock_login_logger_retention'), $sitelock_retention_days); ?>>
                                <?php echo esc_html($sitelock_retention_days . ' ' . $sitelock_language_tokens['var']['days']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="tab-content-field-content text-[#575757]"><?php echo esc_html($sitelock_language_tokens['login_security_list_settings']['login_activity_log']['loginRetentionPeriodDescription']) ?></p>
            </div>
        </div>
        <div class="mb-7">
            <button type="submit" class="w-[131px] h-[32px] btn-primary"><?php echo esc_html($sitelock_language_tokens['var']['saveChanges']) ?></button>
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

   // 2FA Disable Confirmation Modal
   const $disableInput = $('#disable-confirmation-input');
    const $confirmButton = $('#disable-confirmation-button');
    const $twofaExpandable = $('#2fa');
    const $modal = $('#2fa-disable-confirmation');
    const $checkbox = $('#sitelock_2fa_enable');

    const setConfirmState = (disabled) => {
        $confirmButton
            .prop('disabled', disabled)
            .toggleClass('cursor-not-allowed', disabled)
            .css('opacity', disabled ? '0.5' : '');
    };

    const closeModal = () => {
        $modal.addClass('hidden');
        $twofaExpandable.addClass('expanded');
        $('body').css('overflow', '');
        $disableInput.val('');
        setConfirmState(true);
        $checkbox.prop('checked', true);
    };

    const disableTwofa = () => {
        $disableInput.val('');
        $modal.addClass('hidden');
        $twofaExpandable.removeClass('expanded');
        $checkbox.prop('checked', false);
        $('body').css('overflow', '');
    };

    // Initial state
    setConfirmState(true);

    // Enable button only when user types "DISABLE"
    $disableInput.on('input', function () {
        setConfirmState($(this).val().trim().toUpperCase() !== 'DISABLE');
    });

    // Show modal when disabling 2FA
    $(document).on('click', '.lightswitch[data-id="2fa"], .two-fa', function () {
        // Delay allows checkbox state to update first
        setTimeout(() => {
            if (!$checkbox.prop('checked')) {
                $modal.removeClass('hidden');
                $('body').css('overflow', 'hidden');
            }
        }, 0);
    });
    
    // Confirm disable 2FA
    $confirmButton.on('click', disableTwofa);

    // Close modal (button)
    $('.close-modal').on('click', closeModal);

    // Close modal (Escape key)
    $(document).on('keydown', function (e) {
        if ((e.key === 'Escape' || e.keyCode === 27) && !$modal.hasClass('hidden')) {
            closeModal();
        }
    });
});
</script>
