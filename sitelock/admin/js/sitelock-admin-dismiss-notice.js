jQuery(document).ready(function ($) {
  $(document).on('click', '.sitelock-admin-warning .notice-dismiss', function () {
    $.post(sitelock_ajax.ajax_url, {
      action: 'sitelock_dismiss_notice',
      _ajax_nonce: sitelock_ajax.nonce,
    })
      .done(function (response) {
        console.log('Notice dismissed successfully:', response);
      })
      .fail(function (jqXHR, textStatus, errorThrown) {
        console.error('Failed to dismiss notice:', textStatus, errorThrown);
      });
  });
});
