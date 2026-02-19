<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shared 2FA Recovery Codes Partial (Step 3)
 * 
 * Variables expected:
 * - $backup_codes: Array of plain-text backup codes
 * - $is_wizard: (Optional) Boolean, true for full-page wizard context
 */

$sitelock_language_tokens = sitelock_get_language_tokens();
$step_three_tokens = $sitelock_language_tokens['two_factor_authentication_settings_steps']['stepThree'];
?>

<div class="sitelock-2fa-step sitelock-2fa-step-3 text-left">
    <p class="sitelock-step-title mb-2 text-left text-[16px] font-normal"><?php echo esc_html($step_three_tokens['title']); ?></p>
    <p class="sitelock-step-desc mb-6 text-[14px] leading-relaxed text-left"><?php echo esc_html($step_three_tokens['description']); ?></p>
    <?php 
    $notice = $step_three_tokens['recoveryCodeNoticeLine1'];
    $time_str = !empty($code_expiration) ? sprintf('%d minutes', $code_expiration) : '30 minutes';
    $notice = str_replace('##remaining-time##', '<strong>' . $time_str . '</strong>', $notice);
    $notice = str_replace('#b#', '<strong>', $notice);
    $notice = str_replace('#/b#', '</strong>', $notice);
    ?>

    <div class="flex items-center max-w-[870px] border border-[#FFD601] px-4 py-3 rounded relative overflow-hidden mb-4">
        <span class="absolute left-0 top-0 h-full w-[15px] bg-[#FFD601] opacity-20 pointer-events-none"></span>
        <div class="text-[#FFD601] pl-4 pr-4 flex-shrink-0 icon type-warning">
            <svg fill="none" viewBox="0 0 24 21" width="24" height="20" xmlns="http://www.w3.org/2000/svg">
                <path d="M23.6908 17.3689L13.8464 1.04273C13.4585 0.399563 12.7511 0 12 0C11.2489 0 10.5414 0.399563 10.1535 1.04278L0.309161 17.3689C-0.0916669 18.0337 -0.103526 18.8666 0.278177 19.5425C0.659974 20.2185 1.37932 20.6384 2.15557 20.6384H21.8444C22.6206 20.6384 23.34 20.2185 23.7218 19.5425C24.1035 18.8665 24.0917 18.0336 23.6908 17.3689ZM22.3612 18.7741C22.2561 18.9602 22.0581 19.0758 21.8444 19.0758H2.15557C1.94187 19.0758 1.74382 18.9602 1.63877 18.7742C1.53368 18.5881 1.53696 18.3588 1.64726 18.1758L11.4917 1.84964C11.5985 1.67259 11.7933 1.56258 12 1.56258C12.2067 1.56258 12.4015 1.67259 12.5083 1.84964L22.3527 18.1758C22.463 18.3588 22.4663 18.5881 22.3612 18.7741Z" fill="currentColor"></path>
                <path d="M12.006 6.42871C11.4116 6.42871 10.9478 6.74765 10.9478 7.31305C10.9478 9.03815 11.1507 11.5171 11.1507 13.2422C11.1507 13.6916 11.5422 13.8801 12.0061 13.8801C12.354 13.8801 12.8468 13.6916 12.8468 13.2422C12.8468 11.5171 13.0498 9.0382 13.0498 7.31305C13.0498 6.7477 12.5714 6.42871 12.006 6.42871Z" fill="#000000"></path>
                <path d="M12.0216 14.8809C11.3837 14.8809 10.9053 15.3882 10.9053 15.9971C10.9053 16.5915 11.3837 17.1134 12.0216 17.1134C12.6159 17.1134 13.1234 16.5915 13.1234 15.9971C13.1234 15.3882 12.6159 14.8809 12.0216 14.8809Z" fill="#000000"></path>
            </svg>
        </div>
        <div class="text-sm leading-5 break-words items-center pl-2">
            <?php echo wp_kses_post($notice); ?>
        </div>
    </div>
    
    <div class="sitelock-recovery-code-box <?php echo !empty($is_wizard) ? 'text-center' : ''; ?>">
        <i class="block mb-4 italic font-semibold text-[14px]"><?php echo esc_html($step_three_tokens['storeCodes']); ?></i>
        
        <div class="flex justify-center mb-6">
            <ul id="backup-codes" class="grid gap-3 w-full max-w-[250px]">
            <?php 
            if (!empty($backup_codes)) {
                foreach ($backup_codes as $code) {
                    $display_code = chunk_split($code, 4, ' ');
                    echo '<li class="bg-[#F7F7F7] px-4 py-2 border rounded font-code-save text-[16px] flex items-center justify-center font-mono" data-code="' . esc_attr($code) . '">' . esc_html($display_code) . '</li>';
                }
            }
            else 
            {
                $redirect_to = !empty($_REQUEST['redirect_to']) ? esc_url_raw($_REQUEST['redirect_to']) : esc_url(admin_url());
                wp_safe_redirect($redirect_to);
                exit;
            }
            ?>
            </ul>
        </div>

        <div class="sitelock-recovery-actions gap-4">
            <button type="button" onclick="downloadBackupCodes()" class="sitelock-btn-download w-[164px] h-[32px] text-[#2D68C4] bg-[#F6F9FE] border border-solid border-[#A2BEEB] rounded text-[14px] hover:bg-gray-50 transition-colors">
                <?php echo esc_html($step_three_tokens['downloadCodes']); ?>
            </button>
            
            <p class="text-[14px] text-left max-w-[500px] mt-4 leading-relaxed">
                <?php esc_html_e('When you’re done saving your recovery codes, continue to your account.', 'sitelock-wordpress-plugin'); ?>
            </p>

            <a href="<?php echo esc_url(admin_url()); ?>" class="sitelock-btn-continue inline-block w-[164px] h-[32px] bg-[#2D68C4] text-white rounded text-[14px] flex items-center justify-center no-underline hover:bg-[#1e4a8c] transition-colors">
                <?php esc_html_e('Continue to Dashboard', 'sitelock-wordpress-plugin'); ?>
            </a>
        </div>
    </div>
</div>
