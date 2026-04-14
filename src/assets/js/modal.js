(() => {
const t = window.tinycms?.i18n?.t || (() => '');

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

const hoistModalsToBody = () => {
    document.querySelectorAll('[data-modal], [data-content-leave-modal], [data-media-library-modal]').forEach((modal) => {
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    });
};

hoistModalsToBody();

const openModal = (trigger) => {
    const modal = getModal(trigger);
    if (!modal) {
        return;
    }

    const type = trigger.getAttribute('data-type') || t('modal.default_type');
    const formId = trigger.getAttribute('data-form-id') || '';
    const text = modal.querySelector('[data-modal-text]');
    const confirm = modal.querySelector('[data-modal-confirm]');

    if (text && trigger.hasAttribute('data-type')) {
        text.textContent = t('modal.confirm_delete_type').replace('%s', type);
    }

    if (confirm && formId) {
        confirm.setAttribute('data-form-id', formId);
    }

    modal.classList.add('open');
};

const getOpenConfirmModal = () => {
    const modals = document.querySelectorAll('[data-modal].open');
    for (let index = modals.length - 1; index >= 0; index -= 1) {
        const modal = modals[index];
        if (modal.querySelector('[data-modal-confirm]')) {
            return modal;
        }
    }
    return null;
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
        if (form.hasAttribute('data-api-submit')) {
            closeModal(confirmTrigger.closest('[data-modal]'));
            form.requestSubmit();
            return;
        }
        form.submit();
    }

    closeModal(confirmTrigger.closest('[data-modal]'));
});

document.addEventListener('keydown', (event) => {
    const modal = getOpenConfirmModal();
    if (!modal) {
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        const closeTrigger = modal.querySelector('[data-modal-close]');
        if (closeTrigger) {
            closeTrigger.click();
            return;
        }
        closeModal(modal);
        return;
    }

    if (event.key !== 'Enter' || event.shiftKey || event.ctrlKey || event.altKey || event.metaKey) {
        return;
    }

    const activeElement = document.activeElement;
    if (activeElement instanceof HTMLTextAreaElement) {
        return;
    }

    const confirmTrigger = modal.querySelector('[data-modal-confirm]');
    if (!confirmTrigger) {
        return;
    }

    event.preventDefault();
    confirmTrigger.click();
});
})();
