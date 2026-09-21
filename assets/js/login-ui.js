window.IntelibinLogin = (() => {
  function init(options = {}) {
    const root = options.root || document;
    const form = root.querySelector('[data-lg="form"]');
    if (!form) return;

    const user = form.querySelector('[data-lg="user"]');
    const pass = form.querySelector('[data-lg="pass"]');
    const toggle = form.querySelector('[data-lg="toggle"]');
    const caps = form.querySelector('[data-lg="caps"]');
    const userError = form.querySelector('[data-lg="user-error"]');
    const passError = form.querySelector('[data-lg="pass-error"]');
    const submit = form.querySelector('[data-lg="submit"]');
    const hasServerError = !!(form.querySelector('[data-lg="error-box"]:not(.ib-lg-alert-hidden)'));

    const setError = (node, message) => {
      if (node) node.textContent = message || '';
    };

    const clearFieldError = (input, node) => {
      input?.addEventListener('input', () => setError(node, ''));
    };

    clearFieldError(user, userError);
    clearFieldError(pass, passError);

    if (toggle && pass) {
      toggle.addEventListener('click', () => {
        const show = pass.type === 'password';
        pass.type = show ? 'text' : 'password';
        toggle.textContent = show ? 'Hide' : 'Show';
        toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
        pass.focus();
      });
    }

    if (caps && pass) {
      const updateCaps = (event) => {
        const enabled = typeof event.getModifierState === 'function' && event.getModifierState('CapsLock');
        caps.classList.toggle('ib-lg-caps-visible', enabled);
      };
      pass.addEventListener('keydown', updateCaps);
      pass.addEventListener('keyup', updateCaps);
      pass.addEventListener('blur', () => caps.classList.remove('ib-lg-caps-visible'));
    }

    if (hasServerError && pass) {
      pass.focus();
      pass.select();
    } else {
      user?.focus();
    }

    window.addEventListener('pageshow', () => {
      submit?.classList.remove('ib-lg-submit-busy-visible');
      if (submit) submit.disabled = false;
    });

    form.addEventListener('submit', (event) => {
      const userMissing = !user?.value.trim();
      const passMissing = !pass?.value;

      setError(userError, userMissing ? 'Enter your email or username.' : '');
      setError(passError, passMissing ? 'Enter your password.' : '');

      if (userMissing || passMissing) {
        event.preventDefault();
        (userMissing ? user : pass)?.focus();
        return;
      }

      submit?.classList.add('ib-lg-submit-busy-visible');
      if (submit) submit.disabled = true;

      if (typeof options.onSubmit === 'function') {
        event.preventDefault();
        Promise.resolve(options.onSubmit({ form, user, pass }))
          .catch(() => {
            submit?.classList.remove('ib-lg-submit-busy-visible');
            if (submit) submit.disabled = false;
          });
      }
    });
  }

  return { init };
})();
