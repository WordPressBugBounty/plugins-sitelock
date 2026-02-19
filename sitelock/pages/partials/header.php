<?php
if (!defined('ABSPATH')) 
{
    exit;
}
$sitelock_language_tokens = sitelock_get_language_tokens();
?>
<!doctype html>
<html lang="<?php echo esc_attr(get_bloginfo('language') ?: 'en-US'); ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo esc_html($sitelock_language_tokens['two_factor_authentication']['title'] ?? 'Two-Factor Authentication'); ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php wp_head();?>
</head>
<body class="sitelock-verify-page">
    <div class="sitelock-verify-page-section">
        <div class="sitelock-logo"><img src="<?php echo esc_url($data['logo']); ?>" alt="<?php echo esc_attr($data['site_name']); ?>" width="64" height="64" /></div>
        <main class="sitelock-card" role="main" aria-labelledby="sitelock-title">
