<?php
if (!defined('ABSPATH')) {
    exit;
}

$sitelock_language_tokens = sitelock_get_language_tokens();
if ($warning_message) {
    ?>
<div id="sitelock-2fa-warning" class="notice notice-warning my-5 ml-0 px-4 py-3 border-l-4 border-[#dba617] bg-[#fffbe6]">
    <p><?php echo esc_html($warning_message) ?></p>
</div>
<?php
}
?>
<div class="sitelock-2fa-settings simple-box mt-5 mb-5 border-none">
    <h4 class="box-title mb-5">
        <?php echo esc_html($sitelock_language_tokens['two_factor_authentication_settings_steps']['2FA']); ?>
    </h4>
    <?php if (!$is_2fa_enabled || isset($backup_codes)): ?>
        <p class="mb-8 text-[14px] max-w-[600px] leading-[20px]">
            <?php echo esc_html($sitelock_language_tokens['two_factor_authentication_settings_steps']['description']); ?>
        </p>
    <?php else: ?>
        <p class="mb-8 text-[14px] max-w-[600px] leading-[20px]">
            <?php echo esc_html($sitelock_language_tokens['two_factor_authentication_settings_steps']['twofaMessage']); ?>
        </p>
    <?php endif; ?>
</div>

<div class="sitelock-2fa-settings-container max-w-[700px]">
    <!-- Header -->
    <div class="sitelock-2fa-header">
        <h4 class="title flex items-center justify-between">
            <?php echo esc_html($sitelock_language_tokens['two_factor_authentication_settings_steps']['appTitle']); ?>
        </h4>
        <div class="sitelock-2fa-status flex items-center justify-between">
            <span class="icon circle bg-[<?php echo $is_2fa_enabled ? '#00AA6B' : '#DB1010'; ?>] mr-2"></span>
            <span class="my-[3px] text-[14px] leading-[16px] mr-1">
                <?php echo esc_html($is_2fa_enabled ? $sitelock_language_tokens['two_factor_authentication_settings_steps']['enabled'] : $sitelock_language_tokens['two_factor_authentication_settings_steps']['disabled']); ?>
            </span>
        </div>
    </div>

    <?php if (!$is_2fa_enabled) : ?>
        <!-- Step 1 -->
        <!-- Step 1 & 2 -->
        <?php 
        $is_wizard = false;
        include plugin_dir_path(dirname(__DIR__)) . 'partials/2fa/qr-setup.php'; 
        ?>
    <?php else :
        $step_three_tokens = $sitelock_language_tokens['two_factor_authentication_settings_steps']['stepThree'];
        if (isset($backup_codes)):
            ?>
        <!-- Step 3 -->
        <!-- Step 3 -->
        <div class="sitelock-recovery-code step-3 p-5">
            <?php
            // Recovery code notice logic (Steps 3 header area)
            $code_expiration_minutes  = isset($code_expiration) ? (int) $code_expiration : 0;
            $time_suffix              = ($code_expiration_minutes === 1) ? ' minute' : ' minutes';
            $recovery_code_notice_raw = esc_html($step_three_tokens['recoveryCodeNoticeLine1']);
            $recovery_code_notice_raw = str_replace('##remaining-time##', '<b>'.$code_expiration_minutes . $time_suffix.'</b>', $recovery_code_notice_raw);
            $recovery_code_notice     = str_replace(['#b#', '#/b#'], ['<b>', '</b>'], $recovery_code_notice_raw);
            ?>
            <p class="sitelock-step-title mb-2 text-left text-[16px] font-normal"><?php echo esc_html($step_three_tokens['title']); ?></p>
            <p class="sitelock-step-desc mb-6 text-[14px] leading-relaxed text-left"><?php echo esc_html($step_three_tokens['description']); ?></p>
                <div class="flex items-center max-w-[870px] border border-[#FBBF24] px-4 py-3 rounded relative overflow-hidden">
                    <span class="absolute left-0 top-0 h-full w-[15px] bg-[#FBBF24] opacity-20 pointer-events-none"></span>
                    <div class="text-[#FBBF24] pl-4 pr-4 flex-shrink-0 icon type-warning">
                        <svg fill="none" viewBox="0 0 24 21" width="24" height="20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M23.6908 17.3689L13.8464 1.04273C13.4585 0.399563 12.7511 0 12 0C11.2489 0 10.5414 0.399563 10.1535 1.04278L0.309161 17.3689C-0.0916669 18.0337 -0.103526 18.8666 0.278177 19.5425C0.659974 20.2185 1.37932 20.6384 2.15557 20.6384H21.8444C22.6206 20.6384 23.34 20.2185 23.7218 19.5425C24.1035 18.8665 24.0917 18.0336 23.6908 17.3689ZM22.3612 18.7741C22.2561 18.9602 22.0581 19.0758 21.8444 19.0758H2.15557C1.94187 19.0758 1.74382 18.9602 1.63877 18.7742C1.53368 18.5881 1.53696 18.3588 1.64726 18.1758L11.4917 1.84964C11.5985 1.67259 11.7933 1.56258 12 1.56258C12.2067 1.56258 12.4015 1.67259 12.5083 1.84964L22.3527 18.1758C22.463 18.3588 22.4663 18.5881 22.3612 18.7741Z" fill="currentColor"></path>
                            <path d="M12.006 6.42871C11.4116 6.42871 10.9478 6.74765 10.9478 7.31305C10.9478 9.03815 11.1507 11.5171 11.1507 13.2422C11.1507 13.6916 11.5422 13.8801 12.0061 13.8801C12.354 13.8801 12.8468 13.6916 12.8468 13.2422C12.8468 11.5171 13.0498 9.0382 13.0498 7.31305C13.0498 6.7477 12.5714 6.42871 12.006 6.42871Z" fill="#000000"></path>
                            <path d="M12.0216 14.8809C11.3837 14.8809 10.9053 15.3882 10.9053 15.9971C10.9053 16.5915 11.3837 17.1134 12.0216 17.1134C12.6159 17.1134 13.1234 16.5915 13.1234 15.9971C13.1234 15.3882 12.6159 14.8809 12.0216 14.8809Z" fill="#000000"></path>
                        </svg>
                    </div>
                    <div class="text-sm leading-5 break-words items-center pl-2">
                        <?php echo wp_kses($recovery_code_notice, ['b' => []]); ?>
                    </div>
                </div>
            </div>
            <div class="sitelock-recovery-code-box mt-2 item-center">
                <i class="text-center block leading-[20px] font-semibold text-[14px] mb-4 mt-2">
                    <?php echo esc_html($step_three_tokens['storeCodes']); ?>
                </i>
                <!-- Backup codes -->
                <div class="flex justify-center">
                    <ul id="backup-codes" class="text-[#041C2C] mb-3">
                        <?php foreach ($backup_codes as $code) : ?>
                            <li data-code="<?php echo esc_attr(chunk_split($code, 4, ' ')); ?>" class="bg-[#F7F7F7] px-4 py-2 mb-2 border rounded text-[16px] item-center font-mono">
                                <span class="masked"><?php echo esc_html(chunk_split($code, 4, ' ')); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <!-- Generate new codes -->
                <div class="mt-1">
                    <div class="flex justify-center">
                        <button type="submit" onclick="downloadBackupCodes()" class="w-[164px] h-[32px] btn-primary">
                            <?php echo esc_html($step_three_tokens['downloadCodes']); ?>
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="p-5">
            <!-- 2FA Activation -->
            <div class="twofa-activation">
                <h2 class="title mb-2">
                    <?php echo esc_html($step_three_tokens['activate2fa']); ?>
                </h2>

                <?php if (isset($backup_codes) && $is_2fa_enabled ): ?>
                    <p class="text-[14px] mb-7 leading-5">
                        <?php echo esc_html($step_three_tokens['enabled_line1']); ?><br/>
                        <?php echo esc_html($step_three_tokens['enabled_line2']); ?>
                    </p>
                <?php endif; ?>

                <?php if (!isset($backup_codes) && $is_2fa_enabled ): ?>
                    <ul class="font-normal leading-5 text-[14px] mb-7 mt-3">
                        <?php 
                        $activation_steps = $step_three_tokens['activationSteps'];
                        if (is_array($activation_steps)) {
                            foreach ($activation_steps as $point) : 
                                if (is_string($point)) : ?>
                                    <li class="mb-3"><?php echo wp_kses_post($point); ?></li>
                                <?php endif;
                            endforeach;
                        }
                        ?>
                    </ul>
                <?php endif; ?>

                <!-- Disable the 2FA -->
                <div class="mb-5">
                    <button type="button" name="disable_2fa" id="twofa-disable-confirmation" class="w-[120px] h-[32px] btn-secondary">
                        <?php echo esc_html($step_three_tokens['disabled2FA']); ?>
                    </button>
                </div>
            </div>
        </div>
        <!-- 2FA Disable Confirmation Modal -->
        <?php
        $modal_id            = 'twofa-disable-confirmation-modal';
        $title               = $sitelock_language_tokens['login_security_list_settings']['2fa_disable_modal']['title'];
        $warning_text        = $sitelock_language_tokens['two_factor_authentication_settings_steps']['confirmDisable'];
        $message_text        = $sitelock_language_tokens['two_factor_authentication_settings_steps']['confirmMessage'];
        $show_list           = false;
        $show_input          = false;
        $confirm_button_id   = 'confirm-disable-2fa';
        $confirm_button_type = 'submit';
        $cancel_button_text  = $sitelock_language_tokens['login_security_list_settings']['2fa_disable_modal']['cancelButton'];
        $confirm_button_text = $sitelock_language_tokens['login_security_list_settings']['2fa_disable_modal']['confirmButton'];

        include __DIR__ . '/sitelock-admin-disable-2fa-modal.php';
        ?>
    <?php endif; ?>
</div>

<?php
    if (isset($_POST['regenerate_backup_codes'])) {
        check_admin_referer();
        $this->generate_backup_codes($user->ID);
        echo '<div class="updated"><p>New backup codes generated successfully.</p></div>';
    }
?>

<script>
jQuery(document).ready(function ($) {

    /* Open confirmation modal */
    $('#twofa-disable-confirmation').on('click', function (e) {
        $('#twofa-disable-confirmation-modal').removeClass('hidden');
        $('body').css('overflow', 'hidden');
    });

    /* Close modal */
    $('.close-modal').on('click', function () {
        $('#twofa-disable-confirmation-modal').addClass('hidden');
        $('body').css('overflow', '');
    });

    /* Confirm disable 2FA */
    $(document).on('click', '#confirm-disable-2fa', function (e) {
        e.preventDefault(); // Prevent default button behavior
        $.ajax({
            url: sitelock_2fa_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sitelock_disable_2fa',
                security: sitelock_2fa_ajax.nonce,
            },
            success: function (response) {
                if (response.success === true) {
                    window.location.reload(); 
                }
            },
            error: function (xhr) {
                console.error('AJAX ERROR:', xhr.responseText);
                alert('An error occurred while disabling 2FA. Please try again.');
            }
        });
    });

});
</script>