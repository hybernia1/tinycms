document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-flash-close]');

    if (!button) {
        return;
    }

    const item = button.closest('.flash');

    if (item) {
        item.remove();
    }
});
