(() => {
const t = window.tinycms?.i18n?.t || (() => '');

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');
    if (!button) {
        return;
    }

    const wrapper = button.closest('.field-with-icon');
    const input = wrapper ? wrapper.querySelector('input[data-password-input]') : null;
    const icon = button.querySelector('use');

    if (!input || !icon) {
        return;
    }

    const show = input.getAttribute('type') === 'password';
    input.setAttribute('type', show ? 'text' : 'password');
    const iconHref = icon.getAttribute('href') || icon.getAttribute('xlink:href') || '';
    const iconBase = iconHref.includes('#') ? iconHref.split('#')[0] : '';
    const nextIcon = `${iconBase}#icon-${show ? 'hide' : 'show'}`;
    icon.setAttribute('href', nextIcon);
    icon.setAttribute('xlink:href', nextIcon);

    const label = show ? t('auth.hide_password') : t('auth.show_password');
    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);
});
})();
