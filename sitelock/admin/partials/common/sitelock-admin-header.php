<?php
/**
 * The class responsible for all methods related to using our external API
 */
require_once plugin_dir_path(dirname(__FILE__)) . '../../includes/api/class-sitelock-api.php';
?>
<div class="flex items-center justify-between mt-6 mb-8">
<div class="flex items-center">
    <img src="<?php echo esc_url(plugin_dir_url(__DIR__) . '../images/sitelock-sectigo-logo.svg'); ?>" alt="SiteLock Logo" class="max-w-[90%] sm:max-w-full w-[180px]" />
    <h1 class="hidden logo-header sm:block pl-4 leading-none whitespace-nowrap">
        <?php echo esc_html($sitelock_language_tokens['var']['wpSecurity']); ?>
    </h1>
</div>


    <?php 
    if (current_user_can('manage_options')) {
        if ($sitelock_connection_status) { ?>
        <div class="bg-[#E4E4E4] rounded flex justify-between items-center">
            <div class="px-6 py-[10px]">
                <h5 class="my-[3px] text-[13px] leading-[14px] text-[#4F4F4F] font-normal"><?php echo esc_html($sitelock_language_tokens['var']['status']) ?></h5>
                <div class="flex items-center" style="white-space: nowrap;">
                    <div class="flex items-center justify-between">
                        <span class="icon circle mr-1 bg-[#00AA6B]"></span>
                        <h5 class="my-[3px] text-[13px] leading-[16px] mr-1"><?php echo esc_html($sitelock_language_tokens['var']['connected']) ?> </h5>
                    </div>
                    (&nbsp; <a href="<?php echo esc_url(admin_url() . 'admin.php?page=sitelock-settings&tab=connection-to-sitelock') ?>"
                        style="white-space: nowrap;text-decoration:underline; " class="text-[#2d68c4]"> <?php echo esc_html($sitelock_language_tokens['var']['settings']) ?> </a>
                    &nbsp; )
                </div>
            </div>
        </div>

    <?php } else { ?>
        <div class="bg-[#E4E4E4] rounded !flex items-center">
            <div class="px-6 py-[10px]">
                <h5 class="my-[3px] text-[13px] leading-[14px] text-[#4F4F4F] font-normal"><?php echo esc_html($sitelock_language_tokens['var']['status']) ?></h5>
                <div class="flex items-center" style="white-space: nowrap;">
                    <div class="flex items-center justify-between">
                        <span class="icon circle mr-1 bg-[#DB1010]"></span>
                        <h5 class="my-[3px] text-[14px] leading-[16px] mr-1"><?php echo esc_html($sitelock_language_tokens['var']['disconnected']) ?> </h5>
                    </div>
                </div>
            </div>
        </div>
    <?php } 
    }
    ?>
</div>
