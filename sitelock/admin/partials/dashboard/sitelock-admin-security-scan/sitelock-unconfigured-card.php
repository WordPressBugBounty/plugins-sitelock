<?php defined( 'ABSPATH' ) || exit; ?>

<div class="border py-6 rounded border-grey-light mt-5 mb-10">
    <p class="my-2 scan-result-data text-[#333333] flex items-center justify-center">
        <span class="icon circle w-5 mr-2 <?php echo esc_attr($sitelock_language_tokens['service_status_map']['unconfigured']) ?>"></span>
        <?php echo esc_html($sitelock_language_tokens['service_status_tokens']['unconfigured']) ?>
    </p>
</div>