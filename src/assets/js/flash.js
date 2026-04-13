document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-flash-close]');
    if (!button) {
        return;
    }

    const flash = button.closest('.flash');
    if (flash) {
        flash.remove();
    }
});
