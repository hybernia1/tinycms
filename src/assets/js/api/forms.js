(() => {
    const app = window.tinycms = window.tinycms || {};
    const api = app.api = app.api || {};
    const pushFlash = api.pushFlash || (() => {});
    const storeFlash = api.storeFlash || (() => {});
    const postForm = api.http?.postForm;
    const escapeSelector = app.support?.escapeSelector || ((value) => String(value || '').replace(/["\\]/g, '\\$&'));

    const showError = (message) => {
        const text = String(message || '').trim();
        if (text !== '') {
            pushFlash('error', text);
        }
    };

    const setFormMessage = (form, message, tone = 'error') => {
        const container = form.querySelector('[data-api-form-message]')
            || form.parentElement?.querySelector('[data-api-form-message]');
        if (!container) {
            return false;
        }

        const text = String(message || '').trim();
        container.textContent = text;
        container.hidden = text === '';
        container.classList.remove('text-danger', 'text-success');
        if (text !== '') {
            container.classList.add(tone === 'success' ? 'text-success' : 'text-danger');
        }
        return true;
    };

    const clearFieldErrors = (form) => {
        form.querySelectorAll('.api-field-error').forEach((node) => node.remove());
        form.querySelectorAll('[aria-invalid="true"]').forEach((node) => node.removeAttribute('aria-invalid'));
    };

    const findField = (form, name) => {
        const normalized = String(name || '').trim();
        if (normalized === '') {
            return null;
        }

        const direct = form.querySelector(`[name="${escapeSelector(normalized)}"]`);
        if (direct) {
            return direct;
        }

        const indexed = normalized.match(/^([a-zA-Z0-9_-]+)\[(\d+)\]$/);
        if (indexed) {
            const fields = form.querySelectorAll(`[name="${escapeSelector(indexed[1])}[]"]`);
            return fields[Number(indexed[2])] || null;
        }

        if (!normalized.includes('[')) {
            const bracketed = form.querySelector(`[name="settings[${escapeSelector(normalized)}]"]`);
            if (bracketed) {
                return bracketed;
            }
        }

        return null;
    };

    const applyFieldErrors = (form, errors) => {
        if (!errors || typeof errors !== 'object') {
            return;
        }

        const errorAnchor = (field) => {
            if (field.nextElementSibling?.classList.contains('custom-select')) {
                return field.nextElementSibling;
            }
            return field.closest('.field-with-icon') || field;
        };

        Object.entries(errors).forEach(([name, message]) => {
            const field = findField(form, name);
            const text = String(message || '').trim();
            if (!field || text === '') {
                return;
            }

            field.setAttribute('aria-invalid', 'true');
            const error = document.createElement('small');
            error.className = 'text-danger api-field-error';
            error.textContent = text;
            errorAnchor(field).insertAdjacentElement('afterend', error);
        });
    };

    const submitApiForm = async (form, submitter = null) => {
        if (typeof postForm !== 'function') {
            return;
        }

        const payload = new FormData(form);
        if (submitter instanceof HTMLElement) {
            const name = String(submitter.getAttribute('name') || '').trim();
            if (name !== '') {
                payload.set(name, String(submitter.getAttribute('value') || ''));
            }
        }

        const action = submitter instanceof HTMLElement && String(submitter.getAttribute('formaction') || '').trim() !== ''
            ? submitter.getAttribute('formaction')
            : form.action;

        const { response, data: normalized } = await postForm(action, payload, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok || !normalized.success) {
            clearFieldErrors(form);
            applyFieldErrors(form, normalized.errors || {});
            const errorMessage = normalized.message || '';
            const hasInlineMessage = setFormMessage(form, errorMessage, 'error');
            if (!hasInlineMessage) {
                showError(errorMessage);
            }
            return;
        }

        clearFieldErrors(form);
        setFormMessage(form, '');

        const redirect = String(normalized.data?.redirect || '').trim();
        const successMessage = String(normalized.message || '').trim();
        if (redirect !== '') {
            if (successMessage !== '') {
                storeFlash('success', successMessage);
            }
            const target = /^https?:\/\//i.test(redirect) || redirect.startsWith('/')
                ? redirect
                : '/' + redirect.replace(/^\/+/, '');
            window.location.href = target;
            return;
        }

        if (form.hasAttribute('data-stay-on-page')) {
            if (successMessage !== '') {
                const hasInlineMessage = setFormMessage(form, successMessage, 'success');
                if (!hasInlineMessage) {
                    pushFlash('success', successMessage);
                }
            }
            return;
        }

        if (successMessage !== '') {
            pushFlash('success', successMessage);
        }

        window.location.reload();
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-api-submit')) {
            return;
        }

        event.preventDefault();
        submitApiForm(form, event.submitter || null);
    });

})();
