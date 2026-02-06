jQuery(document).ready(function ($) {
  // Add nonce to all redirections
  document.getElementById('select-log').addEventListener('change', function () {
    window.location.href =
      '?page=sitelock-activity-logs&report_type=' +
      this.value +
      '&_wpnonce=' +
      encodeURIComponent(sitelockActivityLogsNonce);
  });
  // Show/hide custom date range when date-filter changes
  $('#date-filter').on('change', function () {
    if ($(this).val() === 'custom') {
      $('#custom-date-range').show();
    } else {
      $('#custom-date-range').hide();
    }
  });

  // Build URL with selected params and redirect
  $('#apply-filters').on('click', function (e) {
    e.preventDefault();

    var report_type = $('#select-log').val();
    var base = '?page=sitelock-activity-logs&report_type=' + encodeURIComponent(report_type);
    var params = [];

    var status = $('#status-filter').val();
    if (status !== '' && status !== null) {
      params.push('status_filter=' + encodeURIComponent(status));
    }

    var dateFilter = $('#date-filter').val();
    if (dateFilter !== '' && dateFilter !== null) {
      params.push('date_filter=' + encodeURIComponent(dateFilter));

      if (dateFilter === 'custom') {
        var start = $('#start-date').val();
        var end = $('#end-date').val();

        // Basic validation: ensure both dates present (you can extend this)
        if (!start || !end) {
          alert('Please select both start and end dates for a custom range.');
          return;
        }

        // Optional: validate chronology
        if (new Date(start) > new Date(end)) {
          alert('Start date cannot be after end date.');
          return;
        }

        params.push('start_date=' + encodeURIComponent(start));
        params.push('end_date=' + encodeURIComponent(end));
      }
    }

    // Include nonce for security
    params.push('_wpnonce=' + encodeURIComponent(sitelockActivityLogsNonce));

    // Build final URL and redirect
    var url = base;
    if (params.length) {
      url += '&' + params.join('&');
    }
    window.location.href = url;
  });

  // Allow Enter key in date inputs to trigger filter
  $('#start-date, #end-date').on('keypress', function (e) {
    if (e.which === 13) {
      $('#apply-filters').trigger('click');
    }
  });
});
