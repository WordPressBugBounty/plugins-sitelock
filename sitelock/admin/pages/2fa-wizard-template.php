<?php
if (!defined('ABSPATH')) {
    exit;
}

// Data already extracted in on_load_your_2fa_page: $qr_src, $user_secret, $is_2fa_enabled, $backup_codes, $grace_period_expired
$title = __('Set Up Two-Factor Authentication', 'sitelock-wordpress-plugin');
$wrapper_class = 'wizard-width';

include dirname(__DIR__) . '/partials/2fa/header.php';

// Skip Link Logic
$show_skip = !($grace_period_expired ?? false) && !$is_2fa_enabled;
$skip_url = wp_nonce_url(add_query_arg(['action' => 'sitelock_2fa_skip']), 'sitelock_2fa_skip_action');
?>

<?php if ($is_2fa_enabled): ?>
    <!-- SUCCESS STATE / RECOVERY CODES -->
    <?php // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?>
    <h2 class="font-normal text-[30px] mb-6"><?php esc_html_e($title, 'sitelock-wordpress-plugin'); ?></h2>
    
    <?php
    $is_wizard = true;
    include dirname(__DIR__) . '/partials/2fa/recovery-codes-list.php';
    ?>

<?php else: ?>

    <!-- SETUP STEPS 1 & 2 -->
    <?php // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?>
    <h2 class="text-2xl font-normal text-[30px] mb-6"><?php esc_html_e($title, 'sitelock-wordpress-plugin'); ?></h2>
    
    <!-- Step 1: Scan Code -->
    <?php 
    $is_wizard = true;
    include dirname(__DIR__) . '/partials/2fa/qr-setup.php'; 
    ?>

<?php endif; ?>

<?php
$plugin_root_url = plugin_dir_url(dirname(dirname(__FILE__)));
$footer_scripts = '
<script type="text/javascript">
    var ajaxurl = "' . admin_url('admin-ajax.php') . '";
    var sitelock_2fa_ajax = {
        ajax_url: ajaxurl,
        nonce: "' . wp_create_nonce('sitelock_2fa_ajax_nonce') . '"
    };
</script>';
// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
$footer_scripts .= '<script src="' . esc_url($plugin_root_url . 'admin/js/sitelock-2fa-setup.js') . '"></script>
';
$extra_footer = $footer_scripts;
include dirname(__DIR__) . '/partials/2fa/footer.php';
?>
