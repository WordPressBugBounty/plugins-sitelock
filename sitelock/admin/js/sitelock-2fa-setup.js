jQuery(document).ready(function ($) {
  // Verify & Enable 2FA via AJAX
  $('#sitelock-verify-2fa').on('click', function (e) {
    e.preventDefault();
    var code = $('#sitelock-2fa-code').val();
    var button = $(this);

    if (!code) {
      alert('Please enter the 2FA code.');
      return;
    }

    $('#sitelock-2fa-message').addClass('hidden').text('');
    button.prop('disabled', true).text('Verifying...');

    $.ajax({
      url: sitelock_2fa_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'sitelock_verify_2fa',
        code: code,
        security: sitelock_2fa_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          location.reload();
        } else {
          $('#sitelock-2fa-message')
            .removeClass('hidden')
            .text(response.data.message || 'Invalid 2FA Code. Please try again.');
        }
        button.prop('disabled', false).text('Verify & Activate');
      },
      error: function () {
        $('#sitelock-2fa-message')
          .removeClass('hidden')
          .text('An error occurred. Please try again.');
        button.prop('disabled', false).text('Verify & Activate');
      },
    });
  });

  // Handle Form Submission (Enter Key)
  $('#sitelock-2fa-verify-form').on('submit', function (e) {
    e.preventDefault();
    $('#sitelock-verify-2fa').click();
  });

  // Regenerate Backup Codes
  $('#sitelock-regenerate-codes').on('click', function () {
    if (
      !confirm(
        'Are you sure you want to generate new backup codes? Your old ones will no longer work.'
      )
    ) {
      return;
    }

    $.ajax({
      url: sitelock_2fa_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'sitelock_regenerate_backup_codes',
        security: sitelock_2fa_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          alert('New backup codes generated!');
          location.reload();
        } else {
          alert('Failed to generate new codes.');
        }
      },
    });
  });

  // Copy 2FA Secret to Clipboard
  $(document).on('click', '.sitelock-manual-key', function () {
    let $container = $(this);
    let secret = $container.data('secret');

    if (!secret) return;

    if (navigator.clipboard) {
      navigator.clipboard
        .writeText(secret)
        .then(function () {
          showToast('Security code copied');
        })
        .catch(function (err) {
          console.error('Failed to copy: ', err);
        });
    } else {
      // Fallback
      let textArea = document.createElement('textarea');
      textArea.value = secret;
      document.body.appendChild(textArea);
      textArea.select();
      try {
        let successful = document.execCommand('copy');
        if (successful) showToast('Security code copied');
      } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
      }
      document.body.removeChild(textArea);
    }
  });

  function showToast(message) {
    let $toast = $(
      '<div class="sitelock-toast" style="position: fixed; top: 32px; left: 50%; transform: translateX(-50%); background-color: #32373c; color: #fff; padding: 12px 24px; border-radius: 4px; z-index: 99999; font-size: 14px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); opacity: 0; transition: opacity 0.3s ease-in-out;">' +
        message +
        '</div>'
    );

    $('body').append($toast);

    // Trigger reflow
    let reflow = $toast[0].offsetHeight;

    // Show
    $toast.css('opacity', '1');

    // Hide and remove
    setTimeout(function () {
      $toast.css('opacity', '0');
      setTimeout(function () {
        $toast.remove();
      }, 300);
    }, 2000);
  }
});
function toggleDisableButton() {
  const checkbox = document.getElementById('disable_2fa');
  const button = document.getElementById('confirm-disable-2fa');
  if (checkbox.checked) {
    button.style.display = 'inline-block';
  } else {
    button.style.display = 'none';
  }
}

function toggleBackupCodes() {
  var codes = document.querySelectorAll('#backup-codes li strong');
  var button = document.getElementById('toggle-codes');

  if (button.innerText === 'Show Codes') {
    codes.forEach((code) => {
      code.innerText = code.parentElement.getAttribute('data-code');
    });
    button.innerText = 'Hide Codes';
  } else {
    codes.forEach((code) => {
      code.innerText = '**** **** **** ****';
    });
    button.innerText = 'Show Codes';
  }
}

function downloadBackupCodes() {
  var codes = document.querySelectorAll('#backup-codes li');
  var text =
    'Your Backup Codes:\n\n' +
    Array.from(codes)
      .map((c) => c.getAttribute('data-code'))
      .join('\n');
  var blob = new Blob([text], { type: 'text/plain' });
  var link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'backup_codes.txt';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}
