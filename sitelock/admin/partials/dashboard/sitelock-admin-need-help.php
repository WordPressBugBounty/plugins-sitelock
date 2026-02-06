<div id="need-help" class="simple-box border-grey-light bg-[#fff] mb-6">
    <!-- Standard header -->
    <div class="header flex items-center justify-start gap-2 px-3 sm:px-5 py-3">
        <h4 class="title"><?php echo esc_html($sitelock_language_tokens['var']['needHelp']) ?></h4>
    </div>

    <!-- Report Body Section -->
    <div class="px-3 sm:px-5 min-h-[200px] pb-0 sm:pb-5">
        <p class="help-content my-5 md:pr-8"><?php echo esc_html($sitelock_language_tokens['need_help']['description']) ?></p>

        <div class="mb-3">
            <p class="toll-free-title "><?php echo esc_html($sitelock_language_tokens['var']['tollFree']) ?></p>
            <a href="<?php echo esc_attr($sitelock_language_tokens['need_help']['tollFreeUrl']) ?>"
                class="text-[#2D68C4] text-[14px]"><?php echo esc_html($sitelock_language_tokens['need_help']['tollFree']) ?></a>
        </div>

        <div class="mb-3">
            <p class="toll-free-international"><?php echo esc_html($sitelock_language_tokens['var']['international']) ?></p>
            <a href="<?php echo esc_attr($sitelock_language_tokens['need_help']['internationalUrl']) ?>"
                class="text-[#2D68C4] text-[14px]"><?php echo esc_html($sitelock_language_tokens['need_help']['international']) ?></a>
        </div>
    </div>
</div>
