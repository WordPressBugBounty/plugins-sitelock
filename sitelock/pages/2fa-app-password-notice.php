<?php defined('ABSPATH') || exit; ?>
<div class="application-passwords-section">
    <h2><?php esc_html_e('Application Passwords', 'sitelock-wordpress-plugin'); ?></h2>
    <p><?php esc_html_e('Application Passwords allow you to authenticate via non-interactive systems, such as XML-RPC or the REST API, without providing your actual password. Application Passwords can be easily revoked. They cannot be used for traditional logins to your website.', 'sitelock-wordpress-plugin'); ?></p>
    
    <div class="notice notice-error inline">
        <p>
            <strong><?php esc_html_e('Application Passwords are currently disabled.', 'sitelock-wordpress-plugin'); ?></strong><br>
            <?php esc_html_e('To ensure the security of your account, Application Passwords cannot be used while Two-Factor Authentication (2FA) is active.', 'sitelock-wordpress-plugin'); ?>
        </p>
        <?php if (!empty($user_2fa_status['role_requires_2fa'])): ?>
            <p>
                <em><?php esc_html_e('Note: 2FA is mandatory for your user role, so Application Passwords cannot be enabled.', 'sitelock-wordpress-plugin'); ?></em>
            </p>
        <?php else: ?>
            <p>
                <?php esc_html_e('If you need to use Application Passwords, you must disable 2FA for your account.', 'sitelock-wordpress-plugin'); ?>
            </p>
        <?php endif; ?>
    </div>
</div>
