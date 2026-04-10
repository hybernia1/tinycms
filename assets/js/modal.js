(() => {
const i18n = window.tinycmsI18n || {};
const t = (path, fallback = '') => {
    const value = path.split('.').reduce((acc, key) => (acc && Object.prototype.hasOwnProperty.call(acc, key) ? acc[key] : undefined), i18n);
    return typeof value === 'string' && value !== '' ? value : fallback;
};

const registry = new Map();

const normalizeName = (name) => String(name || '').trim();
const getNameByElement = (element) => {
    if (!element) {
        return '';
    }
    for (const [name, entry] of registry.entries()) {
        if (entry.element === element) {
            return name;
        }
    }
    return '';
};

const resolveEntry = (target) => {
    if (!target) {
        return null;
    }
    if (target instanceof Element) {
        return {
            name: getNameByElement(target),
            entry: { element: target, closeSelector: '[data-modal-close]', confirmSelector: '[data-modal-confirm]' },
        };
    }
    const name = normalizeName(target);
    if (name === '' || !registry.has(name)) {
        return null;
    }
    return { name, entry: registry.get(name) };
};

const findOpenModalFromTarget = (target) => {
    let node = target instanceof Element ? target : null;
    while (node && node !== document.body) {
        if (node.classList.contains('open') && getNameByElement(node) !== '') {
            return node;
        }
        node = node.parentElement;
    }
    return null;
};

const applyTriggerPayload = (modal, trigger) => {
    if (!modal || !trigger) {
        return;
    }
    const type = trigger.getAttribute('data-type') || t('modal.default_type', 'item');
    const formId = trigger.getAttribute('data-form-id') || '';
    const text = modal.querySelector('[data-modal-text]');
    const confirm = modal.querySelector('[data-modal-confirm]');
    if (text && trigger.hasAttribute('data-type')) {
        text.textContent = t('modal.confirm_delete_type', 'Do you really want to delete this %s?').replace('%s', type);
    }
    if (confirm && formId) {
        confirm.setAttribute('data-form-id', formId);
    }
};

const closeModal = (target, reason = 'close') => {
    const resolved = resolveEntry(target);
    if (!resolved || !resolved.entry.element) {
        return;
    }
    const { entry, name } = resolved;
    entry.element.classList.remove('open');
    if (typeof entry.onClose === 'function') {
        entry.onClose(reason);
    }
    if (entry.pendingResolve) {
        const resolve = entry.pendingResolve;
        entry.pendingResolve = null;
        resolve(false);
    }
    if (name !== '') {
        registry.set(name, entry);
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

const registerModal = (name, options = {}) => {
    const modalName = normalizeName(name);
    const element = options.element instanceof Element
        ? options.element
        : document.querySelector(options.selector || '');
    if (modalName === '' || !element) {
        return;
    }
    registry.set(modalName, {
        element,
        closeSelector: options.closeSelector || '[data-modal-close]',
        confirmSelector: options.confirmSelector || '[data-modal-confirm]',
        closeOnBackdrop: options.closeOnBackdrop !== false,
        onOpen: options.onOpen || null,
        onClose: options.onClose || null,
        pendingResolve: null,
    });
};

const openModal = (target, payload = {}) => {
    const resolved = resolveEntry(target);
    if (!resolved || !resolved.entry.element) {
        return null;
    }
    const { entry, name } = resolved;
    entry.element.classList.add('open');
    if (typeof entry.onOpen === 'function') {
        entry.onOpen(payload);
    }
    if (name !== '') {
        registry.set(name, entry);
    }
    return entry.element;
};

const confirmModal = (target, payload = {}) => {
    const resolved = resolveEntry(target);
    if (!resolved || !resolved.entry.element) {
        return Promise.resolve(false);
    }
    openModal(target, payload);
    return new Promise((resolve) => {
        resolved.entry.pendingResolve = resolve;
        if (resolved.name !== '') {
            registry.set(resolved.name, resolved.entry);
        }
    });
};

document.querySelectorAll('[data-modal]').forEach((modal, index) => {
    const id = modal.getAttribute('id') || `modal-${index + 1}`;
    registerModal(id, { element: modal });
});

const modalApi = {
    register: registerModal,
    open: openModal,
    close: closeModal,
    confirm: confirmModal,
    isOpen(target) {
        const resolved = resolveEntry(target);
        return !!(resolved && resolved.entry.element.classList.contains('open'));
    },
};
window.tinycmsModal = modalApi;

document.addEventListener('click', (event) => {
    const openTrigger = event.target.closest('[data-modal-open]');
    if (openTrigger) {
        event.preventDefault();
        const target = openTrigger.getAttribute('data-modal-target') || '';
        const modal = target ? document.querySelector(target) : document.querySelector('[data-modal]');
        if (!modal) {
            return;
        }
        applyTriggerPayload(modal, openTrigger);
        const modalName = modal.getAttribute('id') || getNameByElement(modal) || '';
        openModal(modalName !== '' ? modalName : modal);
        return;
    }

    const openedModal = findOpenModalFromTarget(event.target);
    if (!openedModal) {
        return;
    }
    const name = openedModal.getAttribute('id') || getNameByElement(openedModal);
    const entry = name ? registry.get(name) : null;

    if (entry && entry.closeOnBackdrop && event.target === openedModal) {
        closeModal(name !== '' ? name : openedModal, 'backdrop');
        return;
    }

    const closeSelector = entry?.closeSelector || '[data-modal-close]';
    const confirmSelector = entry?.confirmSelector || '[data-modal-confirm]';

    const closeTrigger = event.target.closest(closeSelector);
    if (closeTrigger && openedModal.contains(closeTrigger)) {
        closeModal(name !== '' ? name : openedModal, 'cancel');
        return;
    }

    const confirmTrigger = event.target.closest(confirmSelector);
    if (!confirmTrigger || !openedModal.contains(confirmTrigger)) {
        return;
    }
    if (entry?.pendingResolve) {
        const resolve = entry.pendingResolve;
        entry.pendingResolve = null;
        if (name !== '') {
            registry.set(name, entry);
        }
        closeModal(name !== '' ? name : openedModal, 'confirm');
        resolve(true);
        return;
    }
    const formId = confirmTrigger.getAttribute('data-form-id') || '';
    const form = formId ? document.getElementById(formId) : null;
    if (form) {
        form.submit();
    }
    closeModal(name !== '' ? name : openedModal, 'confirm');
});
})();
