(() => {
const i18n = window.tinycmsI18n || {};
const t = (path, fallback = '') => {
    const value = path.split('.').reduce((acc, key) => (acc && Object.prototype.hasOwnProperty.call(acc, key) ? acc[key] : undefined), i18n);
    return typeof value === 'string' && value !== '' ? value : fallback;
};

const getModalFromTrigger = (trigger) => {
    const target = trigger?.getAttribute('data-modal-target') || '';
    if (target) {
        return document.querySelector(target);
    }
    return document.querySelector('[data-modal]');
};

const getModal = (value) => {
    if (!value) {
        return null;
    }
    if (typeof value === 'string') {
        return document.querySelector(value);
    }
    if (value instanceof Element && value.matches('[data-modal]')) {
        return value;
    }
    return getModalFromTrigger(value);
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

const applyModalState = (modal, trigger, options = {}) => {
    if (!modal) {
        return;
    }

    const typeFromTrigger = trigger?.getAttribute ? (trigger.getAttribute('data-type') || '') : '';
    const type = options.type || typeFromTrigger || t('modal.default_type', 'item');
    const formIdFromTrigger = trigger?.getAttribute ? (trigger.getAttribute('data-form-id') || '') : '';
    const formId = options.formId || formIdFromTrigger;
    const text = modal.querySelector('[data-modal-text]');
    const confirm = modal.querySelector('[data-modal-confirm]');

    if (text) {
        if (typeof options.text === 'string') {
            text.textContent = options.text;
        } else if (typeFromTrigger !== '') {
            text.textContent = t('modal.confirm_delete_type', 'Do you really want to delete this %s?').replace('%s', type);
        }
    }

    if (confirm && formId !== '') {
        confirm.setAttribute('data-form-id', formId);
    }
};

const openModal = (target, options = {}) => {
    const modal = getModal(target);
    if (!modal) {
        return;
    }
    applyModalState(modal, target, options);
    modal.classList.add('open');
};

const modalApi = window.tinycmsModal && typeof window.tinycmsModal === 'object' ? window.tinycmsModal : {};
if (typeof modalApi.open !== 'function') {
    modalApi.open = (target, options = {}) => openModal(target, options);
}
if (typeof modalApi.close !== 'function') {
    modalApi.close = (target) => closeModal(getModal(target));
}
if (typeof modalApi.update !== 'function') {
    modalApi.update = (target, options = {}) => {
        const modal = getModal(target);
        applyModalState(modal, target, options);
    };
}
window.tinycmsModal = modalApi;

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

    const modal = event.target.closest('[data-modal]');
    if (modal && event.target === modal) {
        closeModal(modal);
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
})();
