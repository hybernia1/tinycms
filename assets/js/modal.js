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

const submitApiForm = async (form) => {
    const response = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    const payload = await response.json().catch(() => null);
    if (!response.ok || payload?.ok !== true) {
        return false;
    }

    const redirectUrl = String(form.getAttribute('data-redirect-url') || '').trim();
    if (redirectUrl !== '') {
        window.location.href = redirectUrl;
        return true;
    }

    window.location.reload();
    return true;
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

document.addEventListener('click', async (event) => {
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
            const success = await submitApiForm(form);
            if (success) {
                closeModal(confirmTrigger.closest('[data-modal]'));
            }
            return;
        }
        form.submit();
    }

    closeModal(confirmTrigger.closest('[data-modal]'));
});
})();
