<?php
defined( 'ABSPATH' ) || exit;
/**
 * Shared partial for 2FA disable confirmation modal.
 *
 * Variables expected:
 * @var string $sitelock_2fa_modal_id              ID for the modal container.
 * @var string $sitelock_2fa_title                 Modal title.
 * @var string $sitelock_2fa_warning_text          Text for the warning box.
 * @var string $sitelock_2fa_message_text          Main message text.
 * @var bool   $sitelock_2fa_show_list             Whether to show the list of disabled factors.
 * @var array  $sitelock_2fa_list_items            Array of list items to display.
 * @var bool   $sitelock_2fa_show_input            Whether to show the confirmation input.
 * @var string $sitelock_2fa_input_label           Label for the input.
 * @var string $sitelock_2fa_confirm_button_id     ID for the confirm button.
 * @var string $sitelock_2fa_confirm_button_type   Type attribute for the confirm button (button|submit).
 * @var string $sitelock_2fa_confirm_button_text   Text for the confirm button.
 * @var string $sitelock_2fa_cancel_button_text    Text for the cancel button.
 */

// Defaults to avoid undefined variable warnings if not passed
$sitelock_2fa_modal_id            = $sitelock_2fa_modal_id ?? '2fa-disable-confirmation';
$sitelock_2fa_title               = $sitelock_2fa_title ?? '';
$sitelock_2fa_warning_text        = $sitelock_2fa_warning_text ?? '';
$sitelock_2fa_message_text        = $sitelock_2fa_message_text ?? '';
$sitelock_2fa_show_list           = $sitelock_2fa_show_list ?? false;
$sitelock_2fa_list_items          = $sitelock_2fa_list_items ?? [];
$sitelock_2fa_show_input          = $sitelock_2fa_show_input ?? false;
$sitelock_2fa_input_label         = $sitelock_2fa_input_label ?? '';
$sitelock_2fa_confirm_button_id   = $sitelock_2fa_confirm_button_id ?? 'confirm-disable-2fa';
$sitelock_2fa_confirm_button_type = $sitelock_2fa_confirm_button_type ?? 'button';
$sitelock_2fa_confirm_button_text = $sitelock_2fa_confirm_button_text ?? 'Confirm';
$sitelock_2fa_cancel_button_text  = $sitelock_2fa_cancel_button_text ?? 'Cancel';
?>

<div id="<?php echo esc_attr($sitelock_2fa_modal_id); ?>"
    class="fixed inset-0 z-50 flex items-start sm:items-center justify-center
            bg-black/50 px-4 overflow-y-auto hidden mt-5 lg:mt-0"
    role="dialog"
    aria-modal="true"
    aria-labelledby="2fa-disable-confirmation-title"
    aria-describedby="2fa-disable-confirmation-description">

    <div class="relative bg-white rounded shadow-lg
                w-full max-w-[450px]
                max-h-[100vh] md:max-h-[90vh]
                overflow-y-auto
                p-3 sm:p-6 mt-10 sm:mt-0">

        <!-- Header -->
        <div class="flex justify-between items-start mb-5">
            <h2 class="text-[20px] sm:text-[24px]" id="2fa-disable-confirmation-title">
                <?php echo esc_html($sitelock_2fa_title); ?>
            </h2>
            <button
                type="button"
                class="cursor-pointer close-modal -mt-1 lg:-mr-2"
                aria-label="Close">
                <img
                    src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../../images/close.svg') ?>"
                    alt=""
                />
            </button>
        </div>

        <!-- Warning box -->
        <p class="bg-[#FBE9E9] mb-5 py-4 px-6 text-[14px] rounded leading-[150%]" id="2fa-disable-confirmation-description">
            <?php echo wp_kses_post($sitelock_2fa_warning_text); ?>
        </p>

        <!-- Message + optional list -->
        <div class="<?php echo $sitelock_2fa_show_list ? 'mb-8' : ''; ?>">
            <p class="mb-3 text-[14px] <?php echo $sitelock_2fa_show_input ? '' : 'w-full md:w-[340px]'; ?>">
                <?php echo wp_kses_post($sitelock_2fa_message_text); ?>
            </p>

            <?php if ($sitelock_2fa_show_list && !empty($sitelock_2fa_list_items)) : ?>
                <ul class="list-disc pl-5 space-y-2 text-[14px]">
                    <?php foreach ($sitelock_2fa_list_items as $sitelock_2fa_item) : ?>
                        <li>
                            <?php echo wp_kses_post($sitelock_2fa_item); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Optional Input -->
        <?php if ($sitelock_2fa_show_input) : ?>
            <div class="mb-6">
                <label for="disable-confirmation-input"
                    class="block mb-2 text-[14px]">
                    <?php echo esc_html($sitelock_2fa_input_label); ?>
                </label>

                <input
                    type="text"
                    id="disable-confirmation-input"
                    name="disable_confirmation_input"
                    aria-required="true"
                    aria-describedby="disable-confirmation-help"
                    class="w-full h-[36px] px-3 border border-[#9E9E9E] rounded
                        focus:outline-none focus:ring-2 focus:ring-[#757575]
                        focus:border-[#757575]" />

            </div>
        <?php endif; ?>

        <!-- Buttons -->
        <div class="flex justify-between gap-3 mt-8">
            <button type="button"
                    class="w-[100px] sm:w-[119px] h-[32px] btn-secondary close-modal">
                <?php echo esc_html($sitelock_2fa_cancel_button_text); ?>
            </button>

            <button type="<?php echo esc_attr($sitelock_2fa_confirm_button_type); ?>"
                    id='<?php echo esc_attr($sitelock_2fa_confirm_button_id); ?>'
                    name="confirm_disable_2fa"
                    class="w-[100px] sm:w-[119px] h-[32px]
                        bg-[#DC2626] text-white rounded-[120px]">
                <?php echo esc_html($sitelock_2fa_confirm_button_text); ?>
            </button>
        </div>

    </div>
</div>
