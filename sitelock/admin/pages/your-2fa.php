<?php
defined('ABSPATH') || exit;
$sitelock_language_tokens   = sitelock_get_language_tokens();
$sitelock_connection_status = $this->api->auth->get_auth_key();
?>
<div class="sitelock-wrapper">
    <div class="container px-3 my-3">
        <?php include(plugin_dir_path(__FILE__) . '../partials/common/sitelock-admin-header.php'); ?>
        <?php include(plugin_dir_path(__FILE__) . '../partials/common/sitelock-admin-tabs.php'); ?>
        <?php include(plugin_dir_path(__FILE__) . '../partials/settings/sitelock-admin-2fa-settings.php'); ?>
    </div>
</div>