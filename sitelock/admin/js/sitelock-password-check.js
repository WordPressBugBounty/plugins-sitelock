jQuery(document).ready(function ($) {
  function updatePasswordStrength() {
    var pass1 = $('#pass1').val() || '';
    var username = $('#user_login').val() || '';
    var role = $('#role').val() ? $('#role').val() : sitelockUserRoles;
    var passwordStrengthLevel = 0;
    switch (sitelockRoles[role]) {
      case 'weak':
        passwordStrengthLevel = 2;
        break;
      case 'medium':
        passwordStrengthLevel = 3;
        break;
      case 'strong':
        passwordStrengthLevel = 4;
        break;
      default:
        passwordStrengthLevel = 0;
    }
    // Ensure wp.passwordStrength is defined
    if (typeof wp !== 'undefined' && wp.passwordStrength) {
      var strength = wp.passwordStrength.meter(pass1, username);
      var submitBtn = $('#createusersub, #submit, #wp-submit');
      if (pass1.length === 0 || strength >= passwordStrengthLevel) {
        submitBtn.prop('disabled', false);
      } else {
        submitBtn.prop('disabled', true);
      }
    }
  }

  // Watch for changes in password input
  $(document).on('keyup change', '#pass1', updatePasswordStrength);

  // Run once on page load
  updatePasswordStrength();
});
