<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shared 2FA QR Setup Partial (Steps 1 & 2)
 * 
 * Variables expected:
 * - $qr_src: QR code image source
 * - $user_secret: Manual setup key
 * - $sitelock_show_skip: (Optional) Whether to show "Skip for Now" link
 * - $sitelock_skip_url: (Optional) URL for "Skip for Now" link
 */

$sitelock_language_tokens = sitelock_get_language_tokens();
$sitelock_2fa_is_wizard = isset($sitelock_2fa_is_wizard) ? $sitelock_2fa_is_wizard : false;

// Configuration based on mode
$sitelock_qr_config = $sitelock_2fa_is_wizard ? [
    'step1_wrapper'      => 'sitelock-2fa-step sitelock-2fa-step-1 mb-8 border-0',
    'step2_wrapper'      => 'sitelock-2fa-step sitelock-2fa-step-2 mb-4',
    'title_tag'          => 'p',
    'title_class'        => 'sitelock-step-title mb-2 text-left font-[400] text-[16px]',
    'desc_class'         => 'sitelock-step-desc text-[14px] leading-relaxed mb-2 text-left',
    'wrap_header'        => false, // Wizard doesn't wrap title/desc in a div
    'qr_img_wrapper'     => 'sitelock-qr-image p-2 border border-gray-200 rounded mb-4 bg-white',
    // Wizard Specifics
    'show_manual_key_ui' => 'wizard', // 'wizard' or 'settings'
    'use_form'           => true,
    'step2_input_class'  => 'sitelock-input-code w-[120px] text-lg border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none text-left',
    'step2_btn_class'    => 'w-[164px] sitelock-btn-primary',
    'step2_flex_class'   => 'mb-6 flex justify-between items-baseline',
] : [
    'step1_wrapper'      => '2fa-scan-code step-1 p-5',
    'step2_wrapper'      => 'step-2 px-5 pb-5',
    'title_tag'          => 'h2',
    'title_class'        => 'title mb-2',
    'desc_class'         => 'pr-3 text-[14px]',
    'wrap_header'        => true, // Settings wraps title/desc in .mb-4
    'qr_img_wrapper'     => 'flex justify-center',
    // Settings Specifics
    'show_manual_key_ui' => 'settings',
    'use_form'           => false,
    'step2_input_class'  => 'input-number',
    'step2_btn_class'    => 'w-[164px] h-[32px] btn-primary',
    'step2_flex_class'   => 'number flex justify-between items-baseline mb-4',
];

// Helper to render title/desc
$sitelock_qr_render_header = function($step_key) use ($sitelock_qr_config, $sitelock_language_tokens) {
    $title = esc_html($sitelock_language_tokens['two_factor_authentication_settings_steps'][$step_key]['title']);
    $desc  = esc_html($sitelock_language_tokens['two_factor_authentication_settings_steps'][$step_key]['description']);
    
    $html = '';
    $html .= sprintf('<%1$s class="%2$s">%3$s</%1$s>', $sitelock_qr_config['title_tag'], $sitelock_qr_config['title_class'], $title);
    $html .= sprintf('<p class="%1$s">%2$s</p>', $sitelock_qr_config['desc_class'], $desc);
    
    if ($sitelock_qr_config['wrap_header']) {
        return '<div class="mb-4">' . $html . '</div>';
    }
    return $html;
};
?>

<!-- Step 1: Scan Code -->
<div class="<?php echo esc_attr($sitelock_qr_config['step1_wrapper']); ?>">
    <?php 
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $sitelock_qr_render_header('stepOne');
    ?>

    <?php if ($sitelock_qr_config['show_manual_key_ui'] === 'wizard'): ?>
        <!-- Wizard QR & Key UI -->
        <div class="sitelock-qr-container flex flex-col items-center mb-6">
            <div class="<?php echo esc_attr($sitelock_qr_config['qr_img_wrapper']); ?>">
                <img src="<?php echo esc_attr($qr_src); ?>" alt="QR Code">
            </div>
            
            <p class="text-center text-[14px] max-w-[353px] leading-relaxed mb-4">
                <span class="font-semibold"><?php echo esc_html($sitelock_language_tokens['two_factor_authentication_settings_steps']['stepOne']['notWorking']); ?></span> 
                <?php echo esc_html($sitelock_language_tokens['two_factor_authentication_settings_steps']['stepOne']['copyManually']); ?>
            </p>
            
            <div id="sitelock-manual-key-container" class="sitelock-manual-key bg-[#F7F7F7] px-6 rounded font-mono text-[14px] border w-full max-w-[286px] text-center h-[36px] truncate leading-[16px] cursor-pointer hover:bg-gray-50 items-center" data-secret="<?php echo esc_attr($user_secret); ?>" title="<?php echo esc_attr($user_secret); ?>">
                <span class="sitelock-key-display"><?php echo esc_html($user_secret); ?></span>
            </div>
        </div>
    <?php else: ?>
        <!-- Settings QR & Key UI -->
        <div class="flex justify-center items-center flex-col">
            <div class="<?php echo esc_attr($sitelock_qr_config['qr_img_wrapper']); ?>">
                <img src="<?php echo esc_attr($qr_src); ?>" alt="Scan this QR code" width="256" height="256" />
            </div>
            <p class="text-center text-[14px] my-2 px-12 leading-[20px] w-[400px]">
                <span class="font-semibold"><?php echo esc_html($sitelock_language_tokens['two_factor_authentication_settings_steps']['stepOne']['notWorking']); ?></span> <?php echo esc_html($sitelock_language_tokens['two_factor_authentication_settings_steps']['stepOne']['copyManually']); ?>
            </p>
            <div class="number rounded mb-6 w-[300px] md:w-[400px] mt-2 bg-[#F7F7F7]">
                <input type="text" id="sitelock-2fa-setup-code" value="<?php echo esc_html($user_secret); ?>" disabled readonly class="input-number h-[36px] overflow-x-auto cursor-text px-[10px]" />
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Step 2: Enter Code -->
<div class="<?php echo esc_attr($sitelock_qr_config['step2_wrapper']); ?>">
    <?php 
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $sitelock_qr_render_header('stepTwo');
    ?>

    <?php 
    // Open Form if needed
    if ($sitelock_qr_config['use_form']) {
        echo '<form id="sitelock-2fa-verify-form" class="max-w-[400px]">';
    }
    ?>
    
    <div class="<?php echo esc_attr($sitelock_qr_config['step2_flex_class']); ?>">
        <?php if (!$sitelock_2fa_is_wizard): ?><div class="w-[120px]"><?php endif; ?>
            <input type="text" id="sitelock-2fa-code" placeholder="123456" <?php echo $sitelock_2fa_is_wizard ? 'autocomplete="off"' : 'min="0"'; ?>
                class="<?php echo esc_attr($sitelock_qr_config['step2_input_class']); ?>" />
        <?php if (!$sitelock_2fa_is_wizard): ?></div><?php endif; ?>
        
        <div id="sitelock-2fa-message" class="sitelock-error hidden ml-2 <?php echo !$sitelock_2fa_is_wizard ? 'h-[36px] px-[10px] leading-[36px] !w-fit' : 'h-[40px] leading-[40px] mb-0'; ?>" role="alert" aria-live="assertive"></div>
    </div>
    
    <div class="<?php echo $sitelock_2fa_is_wizard ? 'sitelock-actions-container flex items-center gap-6' : 'mb-7'; ?>">
        <button type="button" id="sitelock-verify-2fa" class="<?php echo esc_attr($sitelock_qr_config['step2_btn_class']); ?>">
            <?php echo esc_html($sitelock_language_tokens['two_factor_authentication_settings_steps']['stepTwo']['verify']); ?>
        </button>
        
        <?php if ($sitelock_qr_config['use_form'] && !empty($sitelock_show_skip) && !empty($sitelock_skip_url)): ?>
            <a href="<?php echo esc_url($sitelock_skip_url); ?>" class="sitelock-link-skip w-[132px] h-[32px] btn-secondary">
                <?php esc_html_e('Skip for Now', 'sitelock-wordpress-plugin'); ?>
            </a>
        <?php endif; ?>
    </div>

    <?php 
    // Close Form if needed
    if ($sitelock_qr_config['use_form']) {
        echo '</form>';
    }
    ?>
</div>

