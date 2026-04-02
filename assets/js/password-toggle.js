document.addEventListener('click', function (event) {
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

    const nextType = input.type === 'password' ? 'text' : 'password';

    input.type = nextType;
    icon.setAttribute('href', nextType === 'password' ? '#icon-show' : '#icon-hide');
    button.setAttribute('aria-label', nextType === 'password' ? 'Zobrazit heslo' : 'Skrýt heslo');
    button.setAttribute('title', nextType === 'password' ? 'Zobrazit heslo' : 'Skrýt heslo');
});
