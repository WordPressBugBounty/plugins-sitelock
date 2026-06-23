<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Standard 2FA Page Header
 * Variables expected: 
 * - $title: Page title
 * - $sitelock_wrapper_class: (Optional) CSS class for the wrapper
 */

$sitelock_icon_url = includes_url('images/w-logo-blue.png');
// Use robust relative paths from the current file location
$sitelock_admin_css_dir = plugins_url('../../css/', __FILE__);

?>
<!DOCTYPE html>
<html class="sitelock-wrapper" <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($title); ?></title>
    
    <?php 
    wp_print_styles(['sitelock-tailwind-css', 'sitelock-style-css', 'sitelock-sans-css', 'sitelock-2fa-style']); 
    ?>

    <?php 
    // Manual script inclusion as user removed wp_head/wp_footer to avoid bloat
    wp_print_scripts('jquery'); 
    ?>

    <?php if (isset($extra_head))
    {
        echo wp_kses_post($extra_head); 
    }
    ?>
</head>
<body class="sitelock-2fa-onboarding">
    <div class="sitelock-onboarding-wrapper <?php echo esc_attr($sitelock_wrapper_class ?? ''); ?>">
        <div class="sitelock-logo w-[64px]">
            <img src="<?php echo esc_url($sitelock_icon_url); ?>" alt="WordPress Logo">
        </div>
        <div class="sitelock-card rounded-md <?php echo esc_attr(isset($sitelock_custom_header_width) ? $sitelock_custom_header_width : 'max-w-[648px]'); ?> p-6">
