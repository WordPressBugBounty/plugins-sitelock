<?php
if (!defined('ABSPATH')) {
    exit;
}

$user = wp_get_current_user();
$user_2fa_status = sitelock_get_user_2fa_status($user);
$grace_period_expired = $user_2fa_status['grace_period_expired'];
$remaining_days = $user_2fa_status['remaining_days'];
$custom_header_width = 'w-[420px]';

$title = __('Two-Factor Authentication Setup', 'sitelock-wordpress-plugin');
include dirname(__DIR__) . '/partials/2fa/header.php';
?>

<h2 class="text-2xl font-normal text-[30px] mb-6"><?php esc_html_e('Two-Factor Authentication', 'sitelock-wordpress-plugin'); ?></h2>
<p class="text-center text-[14px] leading-relaxed mb-8">
    <?php 
    if ($grace_period_expired) {
        echo wp_kses_post(sprintf(
            __('For security, <strong>2FA is required</strong> on all accounts.<br>Please complete setup now to continue.', 'sitelock-wordpress-plugin')
        ));
    } else {
        echo wp_kses_post(sprintf(
            /* translators: %d: remaining days of grace period to setup 2FA */
            // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
            _n(
                'For security, <strong>2FA is required</strong> on all accounts.<br>Please complete setup within the next <strong>%d day</strong>.',
                'For security, <strong>2FA is required</strong> on all accounts.<br>Please complete setup within the next <strong>%d days</strong>.',
                $remaining_days,
                'sitelock-wordpress-plugin'
            ),
            $remaining_days
        ));
    }
    ?>
</p>

<div class="items-center gap-4 w-[120px] mx-auto">
    <a href="<?php echo esc_url(admin_url('admin.php?page=sitelock-your-2fa&setup_wizard=1')); ?>" class="sitelock-btn-primary w-full mb-6 flex">
        <?php esc_html_e('Set Up 2FA', 'sitelock-wordpress-plugin'); ?>
    </a>
    
    <?php if (!$grace_period_expired): ?>
        <?php 
        $skip_url = wp_nonce_url(add_query_arg(['action' => 'sitelock_2fa_skip']), 'sitelock_2fa_skip_action'); 
        ?>
        <a href="<?php echo esc_url($skip_url); ?>" class="text-[14px] text-[#2D68C4] hover:underline no-underline">
            <?php esc_html_e('Skip for Now', 'sitelock-wordpress-plugin'); ?>
        </a>
    <?php endif; ?>
</div>

<?php 
include dirname(__DIR__) . '/partials/2fa/footer.php';
?>
