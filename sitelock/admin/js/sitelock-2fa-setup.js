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
          alert('Invalid 2FA Code. Please try again.');
        }
        button.prop('disabled', false).text('Verify & Enable 2FA');
      },
    });
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

  $('#confirm-disable-2fa').on('click', function () {
    $.ajax({
      url: sitelock_2fa_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'sitelock_disable_2fa',
        security: sitelock_2fa_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          alert('Disabled: ' + response.data.message);
          location.reload();
        } else {
          alert('Failed to disable 2FA: ' + response.data.message);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error('AJAX Error:', textStatus, errorThrown);
        alert('An error occurred while disabling 2FA. Please try again.');
      },
    });
  });
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
