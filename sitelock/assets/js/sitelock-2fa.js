document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.getElementById('sitelock-toggle-recovery');
  const recovery = document.getElementById('recovery-group');
  const totp = document.getElementById('totp-group');
  const totpInputs = document.querySelectorAll('#totp-group input[type="text"]');
  const codeText = document.getElementById('sitelock-2fa-text');
  const recoveryText = document.getElementById('sitelock-recovery-text');
  const hiddenInput = document.getElementById('totp_code');
  const recInput = document.getElementById('recovery_code');
  const form = document.getElementById('sitelock-2fa-form');
  const submitButton = document.getElementById('sitelock-2fa-submit');

  // Load method from localStorage (either 'totp' or 'recovery')
  const savedMethod = localStorage.getItem('sitelock-2fa-method');

  function getCode() {
    return Array.from(totpInputs)
      .map((input) => input.value.trim())
      .join('');
  }

  function checkInputs() {
    const code = getCode();
    if (code.length === 6) {
      hiddenInput.value = code;
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.classList.add('disabled');
      }
      form.submit();
    }
  }

  function showRecovery(e, recoveryVisible = recovery.classList.contains('hidden')) {
    e && e.preventDefault();
    if (recoveryVisible) {
      // Switch to Recovery
      totp.classList.add('hidden');
      totpInputs.forEach((input) => input.removeAttribute('required'));
      recovery.classList.remove('hidden');
      codeText.classList.add('hidden');
      recoveryText.classList.remove('hidden');
      recInput.setAttribute('required', 'required');
      toggle.textContent = sitelock2fa.i18n.back;
      recInput.focus();
    } else {
      // Switch to TOTP
      recovery.classList.add('hidden');
      recInput.removeAttribute('required');
      totp.classList.remove('hidden');
      codeText.classList.remove('hidden');
      recoveryText.classList.add('hidden');
      totpInputs.forEach((input) => input.setAttribute('required', 'required'));
      toggle.textContent = sitelock2fa.i18n.cant_access;
      totpInputs[0].focus();
    }
  }

  const recoveryErrorElement = document.querySelector('#recovery-group .sitelock-recovery-error');
  const recoveryVisible = recoveryErrorElement !== null ? recoveryErrorElement : false;
  showRecovery(null, recoveryVisible);

  // Attach toggle listener
  if (toggle) toggle.addEventListener('click', showRecovery);

  // Handle TOTP input behavior
  totpInputs.forEach((input, index) => {
    input.addEventListener('input', () => {
      const value = input.value.replace(/\D/g, ''); // Allow only digits
      input.value = value;

      if (value.length === 1 && index < totpInputs.length - 1) {
        totpInputs[index + 1].focus();
      }
      checkInputs();
    });

    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && input.value === '' && index > 0) {
        totpInputs[index - 1].focus();
      }
    });

    input.addEventListener('paste', (e) => {
      const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6); // Allow only digits
      if (pasted.length === 6) {
        e.preventDefault();
        totpInputs.forEach((field, i) => {
          field.value = pasted[i] || '';
        });
        totpInputs[5].focus();
        checkInputs();
      }
    });
  });

  // Prevent form submission if required fields are empty
  if (form) {
    form.addEventListener('submit', function (e) {
      const usingTOTP = !totp.classList.contains('hidden');
      const usingRecovery = !recovery.classList.contains('hidden');

      if (usingTOTP) {
        const code = getCode();
        if (code.length < 6) {
          e.preventDefault();
          totpInputs[0].focus();
          return false;
        }
      }

      if (usingRecovery) {
        if (!recInput.value || recInput.value.trim().length === 0) {
          e.preventDefault();
          recInput.focus();
          return false;
        }
      }

      return true;
    });
  }
});
