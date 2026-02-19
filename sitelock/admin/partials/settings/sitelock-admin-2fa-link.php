<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="_2fa-profile-page">
    <h4 class="_2fa-profile-page-title">
        <?php echo esc_html__('Two-Factor Authentication (2FA)', 'sitelock-wordpress-plugin'); ?>
    </h4>
    <?php
    if ($is_2fa_enabled) {
        ?>
        <p class="_2fa-profile-page-description">
            <?php echo esc_html__('SiteLock 2FA is active.', 'sitelock-wordpress-plugin'); ?>
        </p>
    <?php
    }
?>
   <div class="_2fa-profile-page-activation">
        <a href="<?php echo esc_url(admin_url('admin.php?page=sitelock-your-2fa')); ?>">
            <button id="activate-your-2fa" type="button" class="button button-lite mb-3">
                <?php
            if ($is_2fa_enabled) {
                echo esc_html__('Manage 2FA for Your Account', 'sitelock-wordpress-plugin');
            } else {
                echo esc_html__('Activate 2FA for Your Account', 'sitelock-wordpress-plugin');
            }
?>
            </button>
        </a>
   </div>
   <?php if (current_user_can('manage_options')) : ?>
  <div class="_2fa-profile-page-manage-users">
        <a href="<?php echo esc_url(admin_url('admin.php?page=sitelock-settings&tab=login-security')); ?>">
            <button id="manage-2fa-settings" type="button" class="button button-lite">
                <?php echo esc_html__('Manage 2FA Settings for All Users', 'sitelock-wordpress-plugin'); ?>
            </button>
        </a>
  </div>
   <?php endif; ?>
</div>