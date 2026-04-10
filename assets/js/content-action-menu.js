(() => {
    const menu = document.querySelector('[data-content-action-menu]');
    const form = document.querySelector('#content-editor-form');
    if (!menu || !form) {
        return;
    }

    const statusField = form.querySelector('[data-content-status-hidden]');
    const submits = menu.querySelectorAll('[data-content-action-submit]');
    const deleteButton = menu.querySelector('[data-content-action-delete]');
    const deleteTrigger = document.querySelector('[data-content-delete-trigger]');
    const contentId = Number((form.querySelector('[data-content-id-hidden]') || {}).value || 0);

    if (deleteButton && contentId <= 0) {
        deleteButton.hidden = true;
    }

    submits.forEach((button) => {
        button.addEventListener('click', () => {
            if (statusField) {
                statusField.value = button.getAttribute('data-content-action-submit') || 'draft';
            }
            menu.removeAttribute('open');
            form.requestSubmit();
        });
    });

    if (deleteButton && deleteTrigger) {
        deleteButton.addEventListener('click', () => {
            menu.removeAttribute('open');
            deleteTrigger.click();
        });
    }
})();
