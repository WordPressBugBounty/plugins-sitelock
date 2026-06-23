<?php defined( 'ABSPATH' ) || exit; ?>
<!-- Modal Structure -->
<div id="modalOverlay" class="modal-overlay">
  <div class="modal p-6">
    <span class="close-btn close-btn-action">&times;</span>
    <h2 class="text-[24px] text-dark mt-2 mb-5">Upgrade Info</h2>
    <p class="text-[14px] leading-[150%] font-normal text-[#6A6A6A] mb-6"><?php echo esc_html($upgrade_prompt); ?></p>
    <div class="flex justify-between">
        <button class="w-[124px] h-[32px] flex items-center justify-center bg-[#2D68C4] text-[#fff] hover:text-[#fff] focus:text-[#fff] rounded close-btn-action">
            Back
        </button>
        <?php if (!empty($upgrade_redirect_url)): ?>
            <a href="<?php echo esc_url($upgrade_redirect_url); ?>" target="_blank" class="w-[124px] h-[32px] flex items-center justify-center border-blue bg-[#F6F9FE] text-[#083C8C] rounded">
                Continue
            </a>
        <?php endif; ?> </div>
  </div>
</div>


<script>
jQuery(document).ready(function($) {
  $('.upgradeOpenModalBtn').click(function () {
    $('#modalOverlay').fadeIn();
  });

  $('.close-btn-action, #modalOverlay').click(function (e) {
    // Only close if background or close button is clicked
    if ($(e.target).is('.modal-overlay, .close-btn-action')) {
      $('#modalOverlay').fadeOut();
    }
  });
});
</script>