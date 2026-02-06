document.addEventListener('DOMContentLoaded', function () {
  const menuItem = document.querySelector(
    '#toplevel_page_sitelock-plugin ul.wp-submenu a.sitelock-give-feedback-link'
  );

  if (!menuItem) return;

  // Make link open in a new tab
  menuItem.setAttribute('target', '_blank');
  menuItem.setAttribute('rel', 'noopener noreferrer');
});
