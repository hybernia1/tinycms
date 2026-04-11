(() => {
const t = (window.tinycmsI18nHelper && typeof window.tinycmsI18nHelper.t === 'function')
    ? window.tinycmsI18nHelper.t
    : ((path, fallback = '') => fallback);

const registry = new Map();
const elementToName = new WeakMap();
const openStack = [];

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
    return elementToName.get(element) || '';
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

const applyModalPayload = (modal, payload = {}) => {
    if (!modal) {
        return;
    }
    const type = String(payload.type || '').trim();
    const formId = String(payload.formId || '').trim();
    const textValue = String(payload.text || '').trim();
    const text = modal.querySelector('[data-modal-text]');
    const confirm = modal.querySelector('[data-modal-confirm]');
    if (text && textValue !== '') {
        text.textContent = textValue;
    } else if (text && type !== '') {
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
    if (entry.opener && typeof entry.opener.focus === 'function') {
        entry.opener.focus();
    }
    const stackIndex = openStack.lastIndexOf(entry.element);
    if (stackIndex >= 0) {
        openStack.splice(stackIndex, 1);
    }
    document.body.classList.toggle('modal-open', openStack.length > 0);
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
        opener: null,
    });
    elementToName.set(element, modalName);
};

const openModal = (target, payload = {}) => {
    const resolved = resolveEntry(target);
    if (!resolved || !resolved.entry.element) {
        return null;
    }
    const { entry, name } = resolved;
    entry.opener = payload?.opener instanceof Element ? payload.opener : entry.opener;
    applyModalPayload(entry.element, payload);
    entry.element.classList.add('open');
    if (!openStack.includes(entry.element)) {
        openStack.push(entry.element);
    }
    document.body.classList.add('modal-open');
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
        const target = (openTrigger.getAttribute('data-modal-target') || '').trim();
        if (target === '') {
            return;
        }
        const modal = document.querySelector(target);
        if (!modal) {
            return;
        }
        applyModalPayload(modal, {
            type: openTrigger.getAttribute('data-type') || '',
            formId: openTrigger.getAttribute('data-form-id') || '',
            text: openTrigger.getAttribute('data-modal-text') || '',
        });
        const modalName = modal.getAttribute('id') || getNameByElement(modal) || '';
        openModal(modalName !== '' ? modalName : modal, { opener: openTrigger });
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
