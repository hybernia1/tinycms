const openModal = (trigger) => {
    const modal = document.querySelector('[data-modal]');
    if (!modal) {
        return;
    }

    const type = trigger.getAttribute('data-type') || 'záznam';
    const formId = trigger.getAttribute('data-form-id') || '';
    const text = modal.querySelector('[data-modal-text]');
    const confirm = modal.querySelector('[data-modal-confirm]');

    if (text) {
        text.textContent = `Skutečně smazat tento ${type}?`;
    }

    if (confirm) {
        confirm.setAttribute('data-form-id', formId);
    }

    modal.classList.add('open');
};

document.addEventListener('click', (event) => {
    const openTrigger = event.target.closest('[data-modal-open]');
    if (openTrigger) {
        event.preventDefault();
        openModal(openTrigger);
        return;
    }

    if (event.target.closest('[data-modal-close]')) {
        const modal = document.querySelector('[data-modal]');
        if (modal) {
            modal.classList.remove('open');
        }
        return;
    }

    const confirmTrigger = event.target.closest('[data-modal-confirm]');
    if (!confirmTrigger) {
        return;
    }

    const formId = confirmTrigger.getAttribute('data-form-id') || '';
    const form = formId ? document.getElementById(formId) : null;

    if (form) {
        form.submit();
    }

    const modal = document.querySelector('[data-modal]');
    if (modal) {
        modal.classList.remove('open');
    }
});
