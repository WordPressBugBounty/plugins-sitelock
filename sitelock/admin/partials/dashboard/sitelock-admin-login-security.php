<div id="login-security" class="simple-box border-[#DDDDDD] bg-[#fff] mb-6">
    <!-- Standard header -->
    <div class="header flex items-center justify-between px-3 sm:px-5 py-3">
        <h4 class="title flex items-center"><?php echo esc_html($sitelock_language_tokens['var']['loginSecurity']) ?></h4>
    </div>

    <!-- Report Body Section -->
    <div class="p-0 sm:px-5 min-h-[150px]">
    
        <!-- Row 1 -->
        <div class="border-b border-[#EEEEEE] px-3 sm:px-0">
            <div class="grid grid-cols-12 gap-4 py-[10px]">
                <!-- Plus/minus toggle button -->
                <div class="c1 flex items-center col-span-8">
                    <div>
                        <button type="button" class="toggle mr-3" data-id="2fa">
                            <span><span></span> </span>
                        </button>
                    </div>
                    <a href="#" class="heading" data-id="2fa"><?php echo esc_html($sitelock_language_tokens['login_security_list']['2FA']) ?></a>
                </div>

                <!-- Heading -->
                <div class="c2 flex items-center col-span-4">
                    <!-- Standard color circle -->
                    <div class="pr-2">
                        <span class="icon circle w-5 <?php echo isset($sitelock_security_2fa_options['enable_2fa']) && $sitelock_security_2fa_options['enable_2fa'] == 1 ? 'bg-[#00AA6B]' : 'bg-[#DB1010]' ?>">
                        </span>
                    </div>
                    <!-- Text -->
                    <span class="capitalize analyzing status">
                        <?php echo isset($sitelock_security_2fa_options['enable_2fa']) && $sitelock_security_2fa_options['enable_2fa'] == 1 ? esc_html($sitelock_language_tokens['var']['enabled']) : esc_html($sitelock_language_tokens['var']['disabled']) ?>
                    </span>
                </div>
    
            </div>
            <div id="2fa" class="collapsed">
                <p class="mx-10 text-[14px] leading-[150%] mb-4 font-normal text-[#6A6A6A]"><?php echo esc_html($sitelock_language_tokens['login_security_description']['2FA']) ?></p>
            </div>
        </div>
        <!-- Row 2 -->
        <div class="border-b border-[#EEEEEE] px-3 sm:px-0">
            <div class="grid grid-cols-12 gap-4 py-[10px] ">
                <!-- Plus/minus toggle button -->
                <div class="c1 flex items-center col-span-8">
                    <div>
                        <button type="button" class="toggle mr-3" data-id="login-lockout">
                            <span><span></span> </span>
                        </button>
                    </div>
                    <a href="#" class="heading" data-id="login-lockout"><?php echo esc_html($sitelock_language_tokens['login_security_list']['loginLockout']) ?></a>
                </div>
    
                <!-- Heading -->
                <div class="c2 flex items-center col-span-4">
                    <!-- Standard color circle -->
                    <div class="pr-2">
                        <span class="icon circle w-5 <?php echo $sitelock_login_lockout_enabled == 1 ? 'bg-[#00AA6B]' : 'bg-[#DB1010]' ?>">
                        </span>
                    </div>
                    <!-- Text -->
                    <span class="capitalize analyzing status">
                        <?php echo $sitelock_login_lockout_enabled == 1 ? esc_html($sitelock_language_tokens['var']['enabled']) : esc_html($sitelock_language_tokens['var']['disabled']) ?>
                    </span>
                </div>
    
            </div>
            <div id="login-lockout" class="collapsed">
                <p class="mx-10 text-[14px] leading-[150%] mb-4 font-normal text-[#6A6A6A]"><?php echo esc_html($sitelock_language_tokens['login_security_description']['loginLockout']) ?></p>
            </div>
        </div>

         <!-- Row 3 -->
         <div class="border-b border-[#EEEEEE] px-3 sm:px-0">
            <div class="grid grid-cols-12 gap-4 py-[10px] ">
                <!-- Plus/minus toggle button -->
                <div class="c1 flex items-center col-span-8">
                    <div>
                        <button type="button" class="toggle mr-3" data-id="force-logout">
                            <span><span></span> </span>
                        </button>
                    </div>
                    <a href="#" class="heading" data-id="force-logout"><?php echo esc_html($sitelock_language_tokens['login_security_list']['forceLogoutByTimePeriod']) ?></a>
                </div>
    
                <!-- Heading -->
                <div class="c2 flex items-center col-span-4">
                    <!-- Standard color circle -->
                    <div class="pr-2">
                        <span class="icon circle w-5 <?php echo $sitelock_force_logout_enabled == 1 ? 'bg-[#00AA6B]' : 'bg-[#DB1010]' ?>">
                        </span>
                    </div>
                    <!-- Text -->
                    <span class="capitalize analyzing status">
                        <?php echo $sitelock_force_logout_enabled == 1 ? 'Enabled' : 'Disabled' ?>
                    </span>
                </div>
    
            </div>
            <div id="force-logout" class="collapsed">
                <p class="mx-10 text-[14px] leading-[150%] mb-4 font-normal text-[#6A6A6A]"><?php echo esc_html($sitelock_language_tokens['login_security_description']['forceLogoutByTimePeriod']) ?></p>
            </div>
        </div>

        <!-- Row 4 -->
        <div class="not-last-child:border-b border-[#EEEEEE] px-3 sm:px-0">
            <div class="grid grid-cols-12 gap-4 py-[10px] ">
                <!-- Plus/minus toggle button -->
                <div class="c1 flex items-center col-span-8">
                    <div>
                        <button type="button" class="toggle mr-3" data-id="password-strength">
                            <span><span></span> </span>
                        </button>
                    </div>
                    <a href="#" class="heading" data-id="password-strength"><?php echo esc_html($sitelock_language_tokens['login_security_list']['passwordStrengthToolandEnforcement']) ?></a>
                </div>
    
                <!-- Heading -->
                <div class="c2 flex items-center col-span-4">
                    <!-- Standard color circle -->
                    <div class="pr-2">
                        <span class="icon circle w-5 <?php echo $sitelock_password_strength_enabled == 1 ? 'bg-[#00AA6B]' : 'bg-[#DB1010]' ?>">
                        </span>
                    </div>
                    <!-- Text -->
                    <span class="capitalize analyzing status">
                        <?php echo $sitelock_password_strength_enabled == 1 ? esc_html($sitelock_language_tokens['var']['enabled']) : esc_html($sitelock_language_tokens['var']['disabled']) ?>
                    </span>
                </div>
        
            </div>
            <div id="password-strength" class="collapsed">
                <p class="mx-10 text-[14px] leading-[150%] mb-4 font-normal text-[#6A6A6A]"><?php echo esc_html($sitelock_language_tokens['login_security_description']['passwordStrengthToolandEnforcement']) ?> </p>
            </div>
        </div>
        
        <a href='<?php echo esc_url(admin_url('admin.php?page=sitelock-settings&tab=login-security')) ?>'
            class="w-[132px] h-[32px] btn-secondary ml-3 sm:ml-0 my-3">
            <?php echo esc_html($sitelock_language_tokens['var']['viewSettings']) ?>
        </a>
    </div>
</div>
