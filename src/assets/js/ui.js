(() => {
    const app = window.tinycms = window.tinycms || {};
    const ui = app.ui = app.ui || {};
    const t = app.i18n?.t || (() => '');

    let confirmModal = null;
    let confirmResolve = null;
    let initialized = false;

    const open = (modal) => {
        if (modal) {
            modal.classList.add('open');
        }
    };

    const close = (modal) => {
        if (modal) {
            modal.classList.remove('open');
        }
    };

    const submitForm = (form) => {
        if (!form) {
            return;
        }
        if (form.hasAttribute('data-api-submit')) {
            form.requestSubmit();
            return;
        }
        form.submit();
    };

    const ensureConfirmModal = () => {
        if (confirmModal) {
            return confirmModal;
        }

        confirmModal = document.createElement('div');
        confirmModal.className = 'modal-overlay';
        confirmModal.setAttribute('data-ui-confirm-modal', '');
        confirmModal.innerHTML = `
        <div class="modal">
            <p data-ui-confirm-text></p>
            <div class="modal-actions">
                <button class="btn btn-light" type="button" data-ui-confirm-cancel></button>
                <button class="btn btn-primary" type="button" data-ui-confirm-ok></button>
            </div>
        </div>
    `;
        document.body.appendChild(confirmModal);
        return confirmModal;
    };

    const resolveConfirm = (value) => {
        if (typeof confirmResolve === 'function') {
            confirmResolve(value);
        }
        confirmResolve = null;
        close(confirmModal);
    };

    const confirm = (options = {}) => new Promise((resolve) => {
        const modal = ensureConfirmModal();
        const text = modal.querySelector('[data-ui-confirm-text]');
        const cancel = modal.querySelector('[data-ui-confirm-cancel]');
        const ok = modal.querySelector('[data-ui-confirm-ok]');
        const message = String(options.message || t('modal.confirm_delete_type').replace('%s', t('modal.default_type')) || '').trim();

        confirmResolve = resolve;
        if (text) {
            text.textContent = message;
        }
        if (cancel) {
            cancel.textContent = String(options.cancelLabel || t('common.cancel'));
        }
        if (ok) {
            ok.textContent = String(options.confirmLabel || t('common.confirm'));
        }
        open(modal);
        if (ok) {
            ok.focus();
        }
    });

    const handleClick = async (event) => {
        const cancel = event.target.closest('[data-ui-confirm-cancel]');
        if (cancel) {
            event.preventDefault();
            resolveConfirm(false);
            return;
        }

        const ok = event.target.closest('[data-ui-confirm-ok]');
        if (ok) {
            event.preventDefault();
            resolveConfirm(true);
            return;
        }

        const closeButton = event.target.closest('[data-ui-modal-close]');
        if (closeButton) {
            close(closeButton.closest('.modal-overlay'));
            return;
        }

        const confirmButton = event.target.closest('[data-ui-confirm-form]');
        if (!confirmButton) {
            return;
        }

        event.preventDefault();
        const formId = confirmButton.getAttribute('data-ui-confirm-form') || '';
        if (formId && await confirm({ message: confirmButton.getAttribute('data-ui-confirm-message') || '' })) {
            submitForm(document.getElementById(formId));
        }
    };

    const handleKeydown = (event) => {
        if (!confirmModal?.classList.contains('open')) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            resolveConfirm(false);
            return;
        }

        if (event.key !== 'Enter' || event.shiftKey || event.ctrlKey || event.altKey || event.metaKey) {
            return;
        }

        if (document.activeElement instanceof HTMLTextAreaElement) {
            return;
        }

        event.preventDefault();
        resolveConfirm(true);
    };

    const init = () => {
        if (initialized) {
            return;
        }
        initialized = true;
        document.addEventListener('click', handleClick);
        document.addEventListener('keydown', handleKeydown);
    };

    ui.modal = {
        open,
        close,
        confirm,
        init,
    };

    init();
})();

(() => {
const FLASH_AUTO_CLOSE_MS = 3500;

const closeFlash = (flash) => {
    if (flash instanceof HTMLElement) {
        flash.remove();
    }
};

const scheduleFlashClose = (flash) => {
    if (!(flash instanceof HTMLElement) || flash.dataset.flashAutoclose === '1') {
        return;
    }

    flash.dataset.flashAutoclose = '1';
    window.setTimeout(() => closeFlash(flash), FLASH_AUTO_CLOSE_MS);
};

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-flash-close]');
    if (!button) {
        return;
    }

    closeFlash(button.closest('.flash'));
});

document.querySelectorAll('.flash').forEach(scheduleFlashClose);

new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }

            if (node.matches('.flash')) {
                scheduleFlashClose(node);
            }

            node.querySelectorAll('.flash').forEach(scheduleFlashClose);
        });
    });
}).observe(document.body, { childList: true, subtree: true });
})();
