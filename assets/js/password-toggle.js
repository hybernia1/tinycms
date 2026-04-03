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

    const label = show ? 'Skrýt heslo' : 'Zobrazit heslo';
    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);
});
