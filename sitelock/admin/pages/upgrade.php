<?php
defined( 'ABSPATH' ) || exit;
$sitelock_language_tokens   = sitelock_get_language_tokens();
$sitelock_connection_status = $this->api->auth->get_auth_key();
?>

<div class="sitelock-wrapper">
    <div class="container my-3">

        <?php include(plugin_dir_path(__FILE__) . '../partials/common/sitelock-admin-header.php'); ?>
        <?php include(plugin_dir_path(__FILE__) . '../partials/common/sitelock-admin-tabs.php'); ?>

        <?php
        $sitelock_upgrade_phone_sales = $this->wpslp_partner_data['customer_support_options']['value']['support_contact_info']['value']['phone_sales'] ?? '';
        $sitelock_upgrade_callus_phones = !empty($sitelock_upgrade_phone_sales) ? explode('|', $sitelock_upgrade_phone_sales) : [];
        $sitelock_upgrade_phone_1 = isset($sitelock_upgrade_callus_phones[0]) ? trim($sitelock_upgrade_callus_phones[0]) : '(877) 846 6639';
        $sitelock_upgrade_phone_2 = isset($sitelock_upgrade_callus_phones[1]) ? trim($sitelock_upgrade_callus_phones[1]) : '+1 (415) 390 2500';
        ?>

        <div class="relative overflow-hidden border border-[#DDDDDD] rounded upgrade-background-img">
            <div class="relative z-20 text-center px-5 py-6 sm:py-8 md:py-10 lg:py-12">
                <div class="max-w-2xl mx-auto">
                    <!-- Custom heading -->
                    <h2 class="upgrade-heading mb-[19px]"><?php echo esc_html($sitelock_language_tokens['upgrade_page']['title']) ?></h2>

                    <?php
    // Remove alphabets from the phone number
    $sitelock_upgrade_phone_1_clean = preg_replace('/[a-zA-Z]/', '', $sitelock_upgrade_phone_1);
$sitelock_upgrade_phone_2_clean     = preg_replace('/[a-zA-Z]/', '', $sitelock_upgrade_phone_2);
?>
                    <h2 class="font-semibold flex gap-1 justify-center upgrade-second-text mb-1"><?php echo esc_html($sitelock_language_tokens['upgrade_page']['callUs']) ?><a
                            class="hover:underline" href="<?php echo esc_html('tel:'.str_replace(' ', '', $sitelock_upgrade_phone_1_clean)); ?>"><?php echo esc_html($sitelock_upgrade_phone_1_clean); ?></a></h2>
                    <?php if ($sitelock_upgrade_phone_2):?>
                    <h3 class="font-semibold flex gap-1 justify-center upgrade-third-text mb-5"><?php echo esc_html($sitelock_language_tokens['upgrade_page']['international']) ?><a class="hover:underline"
                            href="<?php echo esc_html('tel:'.str_replace(' ', '', $sitelock_upgrade_phone_2_clean)); ?>"><?php echo esc_html($sitelock_upgrade_phone_2_clean); ?></a></h3>
                    <?php endif; ?>
                    <?php
// Replace the placeholder with the dynamic value
$sitelock_upgrade_description_html = str_replace(
    '{{phone_number}}',
    '<a class="hover:underline inline cursor-pointer font-semibold" href="' . esc_url('tel:' . str_replace(' ', '', $sitelock_upgrade_phone_1_clean)) . '">' . esc_html($sitelock_upgrade_phone_1_clean) . '</a>',
    $sitelock_language_tokens['upgrade_page']['description']
);
?>

                    <div class="rich-text mt-5 mb-8">
                        <?php echo wp_kses_post($sitelock_upgrade_description_html); // Output the content with dynamic value?>
                    </div>

                    <div class="flex justify-center">
                    <a href="<?php echo esc_html('tel:'.str_replace(' ', '', $sitelock_upgrade_phone_1)); ?>"
                        class="w-[124px] h-[32px] flex items-center justify-center btn-primary">
                        <?php echo esc_html($sitelock_language_tokens['upgrade_page']['callUsNow']) ?>
                    </a>
                    </div>

                </div>

            </div>
        </div>