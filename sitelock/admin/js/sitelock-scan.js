jQuery(function ($) {
  $('.security-scan-now-button').on('click', function () {
    var button = $(this); // Store reference to the button
    button.text('Scanning...');
    $.post(
      sitelockPlugin.ajax_url,
      {
        action: 'sitelock_scan',
        nonce: sitelockPlugin.nonce,
        scan_type: this.getAttribute('data-type'),
      },
      function (response) {
        if (response.success) {
          button.attr('disabled', true); // Use the stored reference
          button.html(
            '<img src="' +
              sitelockPlugin.pluginUrl +
              '/images/pending.svg" class="mr-2" alt="Pending Icon" /> Scan Pending'
          );
          if (button.attr('data-scan') === 'vulnerability-scan') {
            $('.security-scan-now-button[data-scan="malware-scan"]').attr('disabled', true);
            $('.security-scan-now-button[data-scan="malware-scan"]').html(
              '<img src="' +
                sitelockPlugin.pluginUrl +
                '/images/pending.svg" class="mr-2" alt="Pending Icon" /> Scan Pending'
            );
          }
          if (button.attr('data-scan') === 'malware-scan') {
            $('.security-scan-now-button[data-scan="vulnerability-scan"]').html(
              '<img src="' +
                sitelockPlugin.pluginUrl +
                '/images/pending.svg" class="mr-2" alt="Pending Icon" /> Scan Pending'
            );
            $('.security-scan-now-button[data-scan="vulnerability-scan"]').attr('disabled', true);
          }
          if (button.attr('data-scan') === 'smart-scan') {
            $('.scan-now-button[data-type="patchman"]').attr('disabled', true);
            $('.scan-now-button[data-type="patchman"]').html(
              '<img src="' +
                sitelockPlugin.pluginUrl +
                '/images/pending.svg" class="mr-2" alt="Pending Icon" /> Scan Pending'
            );
          }
        } else {
          button.text('Scan Now'); // Revert button text on failure
        }
        var existingMessageDiv = $('#sitelock-notification-section');
        var bannerCount = existingMessageDiv.find('.scan-now-banner').length + 1;
        existingMessageDiv.append(`
          <div class="banner scan-now-banner scan-now-banner-${bannerCount} flex items-center compact mt-4 ${response.success ? 'type-success' : 'type-error'}">
              <div class="pr-4">
            <img src="${response.success ? sitelockPlugin.pluginUrl + '/images/tick-circle-icon.svg' : sitelockPlugin.pluginUrl + '/images/red-warning.svg'}" alt="status-icon" class="status-icon pl-2 cursor-pointer"/>
              </div>
              <div class="w-full">
            <div class="flex items-center">
                <div class="w-full">  
                 ${
                   response.success
                     ? 'Your scan has been successfully scheduled and should begin shortly.'
                     : 'We were unable to initiate a scan. Please ' +
                       (response?.data?.partner_data?.dashboard_visibility?.value?.help?.action ===
                       '[default]'
                         ? '<a href="https://www.sitelock.com/help-center/" target="_blank" rel="noopener noreferrer" class="inline underline text-[#2161CC]">contact support</a> for more assistance.'
                         : response?.data?.partner_data?.dashboard_visibility?.value?.help
                               ?.action === 'redirect'
                           ? `<a href="${response?.data?.partner_data?.dashboard_visibility?.value?.help?.url ? response.data.partner_data.dashboard_visibility.value.help.url : ''}" target="_blank" rel="noopener noreferrer" class="inline underline text-[#2161CC]">contact support</a> for more assistance.`
                           : 'contact support for more assistance.')
                 }
            </div>
            </div>
              </div>
              <img src="${sitelockPlugin.pluginUrl + 'images/x.svg'}" alt="close" class="closebtn pl-2 cursor-pointer" />
          </div>
            `);
        // Trigger the scan automatically after 5 seconds
        setTimeout(function () {
          $(`.scan-now-banner-${bannerCount}`).remove(); // Remove the banner element
        }, 5000);

        $('html, body').animate(
          {
            scrollTop: 0,
          },
          'slow'
        );
      }
    );
  });

  $('.scan-now-button').on('click', function () {
    var button = $(this); // Store reference to the button
    button.text('Scanning...');
    $.post(
      sitelockPlugin.ajax_url,
      {
        action: 'sitelock_scan',
        nonce: sitelockPlugin.nonce,
        scan_type: this.getAttribute('data-type'),
      },
      function (response) {
        if (response.success) {
          button.attr('disabled', true); // Use the stored reference
          button.html(
            '<img src="' +
              sitelockPlugin.pluginUrl +
              '/images/pending.svg" class="mr-2" alt="Pending Icon" /> Scan Pending'
          );
          if (button.attr('data-type') === 'patchman') {
            $('.security-scan-now-button[data-scan="smart-scan"]').html(
              '<img src="' +
                sitelockPlugin.pluginUrl +
                '/images/pending.svg" class="mr-2" alt="Pending Icon" /> Scan Pending'
            );
            $('.security-scan-now-button[data-scan="smart-scan"]').attr('disabled', true);
          }
        } else {
          button.text('Scan Now'); // Revert button text on failure
        }
        var existingMessageDiv = $('#sitelock-notification-section');
        var bannerCount = existingMessageDiv.find('.scan-now-banner').length + 1;
        existingMessageDiv.append(`
          <div class="banner scan-now-banner scan-now-banner-${bannerCount} flex items-center compact mt-4 ${response.success ? 'type-success' : 'type-error'}">
              <div class="pr-4">
            <img src="${response.success ? sitelockPlugin.pluginUrl + '/images/tick-circle-icon.svg' : sitelockPlugin.pluginUrl + '/images/red-warning.svg'}" alt="status-icon" class="status-icon pl-2 cursor-pointer"/>
              </div>
              <div class="w-full">
            <div class="flex items-center">
          <div class="w-full">
                  ${
                    response.success
                      ? 'Your scan has been successfully scheduled and should begin shortly.'
                      : 'We were unable to initiate a scan. Please ' +
                        (response?.data?.partner_data?.dashboard_visibility?.value?.help?.action ===
                        '[default]'
                          ? '<a href="https://www.sitelock.com/help-center/" target="_blank" rel="noopener noreferrer" class="inline underline text-[#2161CC]">contact support</a> for more assistance.'
                          : response?.data?.partner_data?.dashboard_visibility?.value?.help
                                ?.action === 'redirect'
                            ? `<a href="${response?.data?.partner_data?.dashboard_visibility?.value?.help?.url ? response.data.partner_data.dashboard_visibility.value.help.url : ''}" target="_blank" rel="noopener noreferrer" class="inline underline text-[#2161CC]">contact support</a> for more assistance.`
                            : 'contact support for more assistance.')
                  }
          </div>
            </div>
              </div>
              <img src="${sitelockPlugin.pluginUrl + 'images/x.svg'}" alt="close" class="closebtn pl-2 cursor-pointer" />
          </div>
            `);
        // Trigger the scan automatically after 5 seconds
        setTimeout(function () {
          $(`.scan-now-banner-${bannerCount}`).remove(); // Remove the banner element
        }, 5000);

        $('html, body').animate(
          {
            scrollTop: 0,
          },
          'slow'
        );
      }
    );
  });

  // Add this event listener to close the banner
  $(document).on('click', '.closebtn', function () {
    $(this).closest('.banner').remove(); // Remove the closest banner element
  });
});
