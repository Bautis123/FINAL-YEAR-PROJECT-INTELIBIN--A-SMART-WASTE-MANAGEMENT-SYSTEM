const toggle = document.querySelector('[data-toggle-password]');
const password = document.getElementById('password');

if (toggle && password) {
  toggle.addEventListener('click', () => {
    const hidden = password.type === 'password';
    password.type = hidden ? 'text' : 'password';
    toggle.textContent = hidden ? 'hide' : 'view';
  });
}
