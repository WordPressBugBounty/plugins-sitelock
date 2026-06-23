<?php defined( 'ABSPATH' ) || exit; ?>
<div class="banner flex items-center compact mt-4 <?php echo esc_attr($sitelock_status == 'error' ? 'type-error' : 'type-success') ?>">
    <div class="pr-4">
        <img src="<?php echo esc_url($sitelock_status == 'error' ? plugin_dir_url(__DIR__) . '../images/circle-failed.svg' : plugin_dir_url(__DIR__) . '../images/tick-circle-icon.svg') ?>" alt="close" class="closebtn pl-2 cursor-pointer"/>
    </div>
    <div class="w-full">
        <div class="flex items-center">
            <div class="w-full"><?php echo esc_html($sitelock_message) ?></div>
        </div>
    </div>
    <img src="<?php echo esc_url(plugin_dir_url(__DIR__) . '../images/x.svg') ?>" alt="close" class="closebtn pl-2 cursor-pointer" />
</div>