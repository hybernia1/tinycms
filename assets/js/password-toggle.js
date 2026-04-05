(() => {
const i18n = window.tinycmsI18n || {};
const t = (path, fallback = '') => {
    const value = path.split('.').reduce((acc, key) => (acc && Object.prototype.hasOwnProperty.call(acc, key) ? acc[key] : undefined), i18n);
    return typeof value === 'string' && value !== '' ? value : fallback;
};

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');
    if (!button) {
        return;
    }

    const wrapper = button.closest('.input-with-icon');
    const input = wrapper ? wrapper.querySelector('input[data-password-input]') : null;
    const icon = button.querySelector('use');

    if (!input || !icon) {
        return;
    }

    const show = input.getAttribute('type') === 'password';
    input.setAttribute('type', show ? 'text' : 'password');
    icon.setAttribute('href', show ? '#icon-hide' : '#icon-show');

    const label = show ? t('auth.hide_password', 'Hide password') : t('auth.show_password', 'Show password');
    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);
});
})();
