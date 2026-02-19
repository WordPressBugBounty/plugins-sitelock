<?php defined( 'ABSPATH' ) || exit; ?>

<div id="security-enhancement" class="simple-box border-grey-light bg-[#fff] mb-6">
    <!-- Standard header -->
    <div class="header flex items-center justify-between px-3 sm:px-5 py-3">
        <h4 class="title"><?php echo esc_html($sitelock_language_tokens['var']['websiteSecurity']) ?></h4>
    </div>

    <!-- Report Body Section -->
    <div class="p-0 sm:px-5">
        <!-- Row 1 -->
        <!-- <div class="border-b border-[#EEEEEE] px-3 sm:px-0">
            <div class="grid grid-cols-12 gap-4 py-[10px]"> -->
                <!-- Plus/minus toggle button -->
                <!-- <div class="col-span-8 c1 flex items-center">
                    <button type="button" class="toggle mr-3" data-id="security-enhancement-1">
                        <span><span></span> </span>
                    </button>
                    <a href="#" class="heading" data-id="security-enhancement-1"><?php //echo esc_html($sitelock_language_tokens['security_enhancements_list']['forceHttps'])?></a>
                </div> -->

                <!-- Heading -->
                <!-- <div class="col-span-4 c2 flex items-center"> -->
                    <!-- Standard color circle -->
                    <!-- <div class="pr-2">
                        <span class="icon circle <?php //echo esc_attr(isset($sitelock_security_enhancements_options['force_https']) && $sitelock_security_enhancements_options['force_https'] == 1 ? 'bg-[#00AA6B]' : 'bg-[#DB1010]')?>">
                        </span>
                    </div> -->
                    <!-- Text -->
                    <!-- <span class="capitalize analyzing status">
                        <?php //echo esc_html(isset($sitelock_security_enhancements_options['force_https']) && $sitelock_security_enhancements_options['force_https'] == 1 ? $sitelock_language_tokens['var']['enabled'] : $sitelock_language_tokens['var']['disabled'])?>
                    </span>
                </div>

            </div>
            <div id="security-enhancement-1" class="collapsed">
                <p class="mx-10 text-[14px] leading-[150%] mb-4 font-normal text-[#6A6A6A]"><?php //echo esc_html($sitelock_language_tokens['security_enhancements_description']['forceHttps'])?></p>
            </div>
        </div> -->
        <!-- Row 2 -->
        <div class="border-b border-[#EEEEEE] px-3 sm:px-0">
            <div class="grid grid-cols-12 gap-4 py-[10px] ">
                <!-- Plus/minus toggle button -->
                <div class="col-span-8 c1 flex items-center">
                    <div>
                        <button type="button" class="toggle mr-3" data-id="security-enhancement-2">
                            <span><span></span> </span>
                        </button>
                    </div>
                    <a href="#" class="heading" data-id="security-enhancement-2"><?php echo esc_html($sitelock_language_tokens['security_enhancements_list']['disableDirectoryListing']) ?></a>
                </div>

                <!-- Heading -->
                <div class="col-span-4 c2 flex items-center">
                    <!-- Standard color circle -->
                    <div class="pr-2">
                        <span class="icon circle <?php echo esc_attr(isset($sitelock_security_enhancements_options['disable_dir_listing']) && $sitelock_security_enhancements_options['disable_dir_listing'] == 1 ? 'bg-[#00AA6B]' : 'bg-[#DB1010]') ?>">
                        </span>
                    </div>
                    <!-- Text -->
                    <span class="capitalize analyzing status">
                        <?php echo esc_html(isset($sitelock_security_enhancements_options['disable_dir_listing']) && $sitelock_security_enhancements_options['disable_dir_listing'] == 1 ? $sitelock_language_tokens['var']['enabled'] : $sitelock_language_tokens['var']['disabled']) ?>
                    </span>
                </div>
            </div>
            <div id="security-enhancement-2" class="collapsed">
                <p class="mx-10 text-[14px] leading-[150%] mb-4 font-normal text-[#6A6A6A]"><?php echo esc_html($sitelock_language_tokens['security_enhancements_description']['disableDirectoryListing']) ?></p>
            </div>
        </div>
        <!-- Row 3 -->
        <div class="border-b border-[#EEEEEE] px-3 sm:px-0">
            <div class="grid grid-cols-12 gap-4 py-[10px] ">
                <!-- Plus/minus toggle button -->
                <div class="col-span-8 c1 flex items-center">
                    <div>
                        <button type="button" class="toggle mr-3" data-id="security-enhancement-3">
                            <span><span></span> </span>
                        </button>
                    </div>
                    <a href="#" class="heading" data-id="security-enhancement-3"><?php echo esc_html($sitelock_language_tokens['security_enhancements_list']['limitPhpExecution']) ?></a>
                </div>

                <!-- Heading -->
                <div class="col-span-4 c2 flex items-center">
                    <!-- Standard color circle -->
                    <div class="pr-2">
                        <span class="icon circle <?php echo esc_attr(isset($sitelock_security_enhancements_options['limit_php_execution']) && $sitelock_security_enhancements_options['limit_php_execution'] == 1 ? 'bg-[#00AA6B]' : 'bg-[#DB1010]') ?>">
                        </span>
                    </div>
                    <!-- Text -->
                    <span class="capitalize analyzing status">
                        <?php echo esc_html(isset($sitelock_security_enhancements_options['limit_php_execution']) && $sitelock_security_enhancements_options['limit_php_execution'] == 1 ? $sitelock_language_tokens['var']['enabled'] : $sitelock_language_tokens['var']['disabled']) ?>
                    </span>
                </div>
            </div>
            <div id="security-enhancement-3" class="collapsed">
                <p class="mx-10 text-[14px] leading-[150%] mb-4 font-normal text-[#6A6A6A]"><?php echo esc_html($sitelock_language_tokens['security_enhancements_description']['limitPhpExecution']) ?></p>
            </div>
        </div>

        <!-- Row 4 -->
        <div class="border-b border-[#EEEEEE] px-3 sm:px-0">
            <div class="grid grid-cols-12 gap-4 py-[10px] ">
                <!-- Plus/minus toggle button -->
                <div class="col-span-8 c1 flex items-center">
                    <div>
                        <button type="button" class="toggle mr-3" data-id="security-enhancement-4">
                            <span><span></span> </span>
                        </button>
                    </div>
                    <a href="#" class="heading" data-id="security-enhancement-4"><?php echo esc_html($sitelock_language_tokens['security_enhancements_list']['xssSqliProtection']) ?></a>
                </div>

                <!-- Heading -->
                <div class="col-span-4 c2 flex items-center">
                    <!-- Standard color circle -->
                    <div class="pr-2">
                        <span class="icon circle <?php echo esc_attr(isset($sitelock_security_enhancements_options['xss_sqli_protection']) && $sitelock_security_enhancements_options['xss_sqli_protection'] == 1 ? 'bg-[#00AA6B]' : 'bg-[#DB1010]') ?>">
                        </span>
                    </div>
                    <!-- Text -->
                    <span class="capitalize analyzing status">
                        <?php echo esc_html(isset($sitelock_security_enhancements_options['xss_sqli_protection']) && $sitelock_security_enhancements_options['xss_sqli_protection'] == 1 ? $sitelock_language_tokens['var']['enabled'] : $sitelock_language_tokens['var']['disabled']) ?>
                    </span>
                </div>

            </div>
            <div id="security-enhancement-4" class="collapsed">
                <p class="mx-10 text-[14px] leading-[150%] mb-4 font-normal text-[#6A6A6A]"><?php echo esc_html($sitelock_language_tokens['security_enhancements_description']['xssSqliProtection']) ?></p>
            </div>
        </div>
        
        <!-- Row 5 -->
        <div class="not-last-child:border-b border-[#EEEEEE] px-3 sm:px-0">
            <div class="grid grid-cols-12 gap-4 py-[10px] ">
                <!-- Plus/minus toggle button -->
                <div class="col-span-8 c1 flex items-center">
                    <div>
                        <button type="button" class="toggle mr-3" data-id="security-enhancement-5">
                            <span><span></span> </span>
                        </button>
                    </div>
                    <a href="#" class="heading" data-id="security-enhancement-5"><?php echo esc_html($sitelock_language_tokens['security_enhancements_list']['hardenWritableDirs']) ?></a>
                </div>

                <!-- Heading -->
                <div class="col-span-4 c2 flex items-center">
                    <!-- Standard color circle -->
                    <div class="pr-2">
                        <span class="icon circle <?php echo esc_attr(isset($sitelock_security_enhancements_options['blocked_directories']) && $sitelock_security_enhancements_options['blocked_directories'] == 1 ? 'bg-[#00AA6B]' : 'bg-[#DB1010]') ?>">
                        </span>
                    </div>
                    <!-- Text -->
                    <span class="capitalize analyzing status">
                        <?php echo esc_html(isset($sitelock_security_enhancements_options['blocked_directories']) && $sitelock_security_enhancements_options['blocked_directories'] == 1 ? $sitelock_language_tokens['var']['enabled'] : $sitelock_language_tokens['var']['disabled']) ?>
                    </span>
                </div>

            </div>
            <div id="security-enhancement-5" class="collapsed">
                <p class="mx-10 text-[14px] text-[#6A6A6A] leading-[150%] mb-4"><?php echo esc_html($sitelock_language_tokens['security_enhancements_description']['hardenWritableDirs']) ?></p>
            </div>
        </div>

        <a href="<?php echo esc_url(admin_url('admin.php?page=sitelock-settings&tab=security-enhancements')) ?>"

            class="w-[132px] px-6 text-[14px] text-center py-[6px] bg-[#F6F9FE] text-[#083C8C] border-blue rounded ml-3 sm:ml-0 my-3">
            <?php echo esc_html($sitelock_language_tokens['var']['viewSettings']) ?>
        </a>
    </div>

</div>