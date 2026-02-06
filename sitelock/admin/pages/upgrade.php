<?php
$sitelock_language_tokens   = get_language_tokens();
$sitelock_connection_status = $this->api->auth->get_auth_key();
?>

<div class="sitelock-wrapper">
    <div class="container my-3">

        <?php include(plugin_dir_path(__FILE__) . '../partials/common/sitelock-admin-header.php'); ?>
        <?php include(plugin_dir_path(__FILE__) . '../partials/common/sitelock-admin-tabs.php'); ?>

        <?php
        $phone_sales = $this->wpslp_partner_data['customer_support_options']['value']['support_contact_info']['value']['phone_sales'] ?? '';
$callus_phone_num    = !empty($phone_sales) ? explode('|', $phone_sales) : [];
$callus_phone_num_1  = isset($callus_phone_num[0]) ? trim($callus_phone_num[0]) : '(877) 846 6639';
$callus_phone_num_2  = isset($callus_phone_num[1]) ? trim($callus_phone_num[1]) : '+1 (415) 390 2500';
?>

        <div class="relative overflow-hidden border border-grey-light rounded upgrade-background-img">
            <div class="relative z-20 text-center px-5 py-6 sm:py-8 md:py-10 lg:py-12">
                <div class="max-w-2xl mx-auto">
                    <!-- Custom heading -->
                    <h2 class="upgrade-heading mb-[19px]"><?php echo esc_html($sitelock_language_tokens['upgrade_page']['title']) ?></h2>

                    <?php
    // Remove alphabets from the phone number
    $callus_phone_num_1_cleaned = preg_replace('/[a-zA-Z]/', '', $callus_phone_num_1);
$callus_phone_num_2_cleaned     = preg_replace('/[a-zA-Z]/', '', $callus_phone_num_2);
?>
                    <h2 class="font-semibold flex gap-1 justify-center upgrade-second-text mb-1"><?php echo esc_html($sitelock_language_tokens['upgrade_page']['callUs']) ?><a
                            class="hover:underline" href="<?php echo esc_html('tel:'.str_replace(' ', '', $callus_phone_num_1_cleaned)); ?>"><?php echo esc_html($callus_phone_num_1_cleaned); ?></a></h2>
                    <?php if ($callus_phone_num_2):?>
                    <h3 class="font-semibold flex gap-1 justify-center upgrade-third-text mb-5"><?php echo esc_html($sitelock_language_tokens['upgrade_page']['international']) ?><a class="hover:underline"
                            href="<?php echo esc_html('tel:'.str_replace(' ', '', $callus_phone_num_2_cleaned)); ?>"><?php echo esc_html($callus_phone_num_2_cleaned); ?></a></h3>
                    <?php endif; ?>
                    <?php
// Replace the placeholder with the dynamic value
$content_with_dynamic_value = str_replace(
    '{{phone_number}}',
    '<a class="hover:underline inline cursor-pointer font-semibold" href="' . esc_url('tel:' . str_replace(' ', '', $callus_phone_num_1_cleaned)) . '">' . esc_html($callus_phone_num_1_cleaned) . '</a>',
    $sitelock_language_tokens['upgrade_page']['description']
);
?>

                    <div class="rich-text mt-5 mb-8">
                        <?php echo wp_kses_post($content_with_dynamic_value); // Output the content with dynamic value?>
                    </div>

                    <div class="flex justify-center">
                    <a href="<?php echo esc_html('tel:'.str_replace(' ', '', $callus_phone_num_1)); ?>"
                        class="w-[124px] h-[32px] flex items-center justify-center bg-[#2D68C4] text-[#fff] hover:text-[#fff] focus:text-[#fff] rounded">
                        <?php echo esc_html($sitelock_language_tokens['upgrade_page']['callUsNow']) ?>
                    </a>
                    </div>

                </div>

            </div>
        </div>