const getModal = (trigger) => {
    const target = trigger?.getAttribute('data-modal-target') || '';
    if (target) {
        return document.querySelector(target);
    }
    return document.querySelector('[data-modal]');
};

const closeModal = (modal) => {
    if (modal) {
        modal.classList.remove('open');
    }
};

const openModal = (trigger) => {
    const modal = getModal(trigger);
    if (!modal) {
        return;
    }

    const type = trigger.getAttribute('data-type') || 'záznam';
    const formId = trigger.getAttribute('data-form-id') || '';
    const text = modal.querySelector('[data-modal-text]');
    const confirm = modal.querySelector('[data-modal-confirm]');

    if (text && trigger.hasAttribute('data-type')) {
        text.textContent = `Skutečně smazat tento ${type}?`;
    }

    if (confirm && formId) {
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

    const closeTrigger = event.target.closest('[data-modal-close]');
    if (closeTrigger) {
        closeModal(closeTrigger.closest('[data-modal]'));
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

    closeModal(confirmTrigger.closest('[data-modal]'));
});
