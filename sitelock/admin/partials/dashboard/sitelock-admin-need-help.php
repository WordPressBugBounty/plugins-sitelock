<?php defined( 'ABSPATH' ) || exit; ?>

<div id="need-help" class="simple-box border-[#DDDDDD] bg-[#fff] mb-6">
    <?php
    $sitelock_support_contact_info = isset($this->wpslp_partner_data['customer_support_options']['value']['support_contact_info'])
        ? $this->wpslp_partner_data['customer_support_options']['value']['support_contact_info']
        : null;
    ?>
    <!-- Standard header -->
    <div class="header flex items-center justify-start gap-2 px-3 sm:px-5 py-3">
        <h4 class="title"><?php echo esc_html($sitelock_language_tokens['var']['needHelp']); ?></h4>
    </div>

    <!-- Report Body Section -->
    <div class="px-3 sm:px-5 min-h-[200px] pb-0 sm:pb-5">
        <p class="help-content my-5 md:pr-8">
            <?php echo esc_html($sitelock_language_tokens['need_help']['description']); ?>
        </p>
        <?php if (!is_null($sitelock_support_contact_info) && isset($sitelock_support_contact_info['action']) && $sitelock_support_contact_info['action'] === 'replace'): ?>
            
            <?php if (isset($sitelock_support_contact_info['value']['url']) && !empty($sitelock_support_contact_info['value']['url']) && isset($sitelock_support_contact_info['value']['replacement_text']) && !empty($sitelock_support_contact_info['value']['replacement_text'])) : ?>
            <div class="mb-3">
                <p class="toll-free-title">
                    <?php echo esc_html($sitelock_language_tokens['var']['website']); ?>
                </p>
                <a href="<?php echo esc_url($sitelock_support_contact_info['value']['url']); ?>" class="text-[#2161CC] text-[14px]" target="_blank">
                    <?php echo esc_html($sitelock_support_contact_info['value']['replacement_text']); ?>
                </a>
            </div>
            <?php endif; ?>

            <?php if (isset($sitelock_support_contact_info['value']['phone_support']) && !empty($sitelock_support_contact_info['value']['phone_support'])): ?>
                <?php $sitelock_phone_support = explode(":", $sitelock_support_contact_info['value']['phone_support']);
                if (count($sitelock_phone_support) >= 2):
                $sitelock_support_phone_number = str_replace(
                    [' ', '(', ')','-'], // remove spaces + brackets
                    '',
                    $sitelock_phone_support[1]
                );
                ?>

                <div class="mb-3">
                    <p class="toll-free-title">
                        <?php echo esc_html($sitelock_phone_support[0]); ?>
                    </p>
                    <a href="<?php echo esc_attr('tel:' . $sitelock_support_phone_number); ?>" class="text-[#2161CC] text-[14px]">
                        <?php echo esc_html($sitelock_phone_support[1]); ?>
                    </a>
                </div>

                <?php else: 
                     $sitelock_support_phone_number = str_replace(
                        [' ', '(', ')','-'], // remove spaces + brackets
                        '',
                        $sitelock_support_contact_info['value']['phone_support']
                    );
                    ?>                    
                    <div class="mb-3">
                    <p class="toll-free-title">
                        <?php echo esc_html($sitelock_language_tokens['var']['support']); ?>
                        </p>
                        <a href="<?php echo esc_attr('tel:' . $sitelock_support_phone_number); ?>" class="text-[#2161CC] text-[14px]">
                            <?php echo esc_html($sitelock_support_contact_info['value']['phone_support']); ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($sitelock_support_contact_info['value']['email']) && !empty($sitelock_support_contact_info['value']['email'])): ?>
            <div class="mb-3">
                <p class="toll-free-title">
                    <?php echo esc_html($sitelock_language_tokens['var']['email']); ?>
                </p>
                <a href="<?php echo esc_attr('mailto:' . $sitelock_support_contact_info['value']['email']); ?>"
                    class="text-[#2161CC] text-[14px]">
                    <?php echo esc_html($sitelock_support_contact_info['value']['email']); ?>
                </a>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="mb-3">
                <p class="toll-free-title">
                    <?php echo esc_html($sitelock_language_tokens['var']['tollFree']); ?>
                </p>
                <a href="<?php echo esc_attr($sitelock_language_tokens['need_help']['tollFreeUrl']); ?>"
                    class="text-[#2161CC] text-[14px]">
                    <?php echo esc_html($sitelock_language_tokens['need_help']['tollFree']); ?>
                </a>
            </div>

            <div class="mb-3">
                <p class="toll-free-international">
                    <?php echo esc_html($sitelock_language_tokens['var']['international']); ?>
                </p>
                <a href="<?php echo esc_attr($sitelock_language_tokens['need_help']['internationalUrl']); ?>"
                    class="text-[#2161CC] text-[14px]">
                    <?php echo esc_html($sitelock_language_tokens['need_help']['international']); ?>
                </a>
            </div>

        <?php endif; ?>
    </div>
</div>