(() => {
const t = window.tinycms?.i18n?.t || (() => '');
const postForm = window.tinycms?.api?.http?.postForm;
const requestJson = window.tinycms?.api?.http?.requestJson;

const modal = document.querySelector('[data-session-login-modal]');
const form = modal?.querySelector('[data-session-login-form]');
const message = modal?.querySelector('[data-session-login-message]');
const submit = form?.querySelector('[data-session-login-submit]');
const emailInput = form?.querySelector('[data-session-login-email]');
const errorFields = form ? Array.from(form.querySelectorAll('[data-session-login-error]')) : [];
const heartbeatEndpoint = document.body.getAttribute('data-heartbeat-endpoint') || '';
const loginEndpoint = document.body.getAttribute('data-heartbeat-login-endpoint') || '';

if (!modal || !form || typeof postForm !== 'function' || typeof requestJson !== 'function' || heartbeatEndpoint === '' || loginEndpoint === '') {
    return;
}

const updateCsrfToken = (token) => {
    const value = String(token || '').trim();
    if (value === '') {
        return;
    }
    document.querySelectorAll('input[name="_csrf"]').forEach((input) => {
        input.value = value;
    });
};

const clearErrors = () => {
    errorFields.forEach((field) => {
        field.textContent = '';
        field.hidden = true;
    });
    if (message) {
        message.textContent = '';
        message.hidden = true;
    }
};

const setMessage = (text) => {
    if (!message) {
        return;
    }
    message.textContent = String(text || '');
    message.hidden = message.textContent.trim() === '';
};

const openModal = (payload) => {
    clearErrors();
    updateCsrfToken(payload?.error?.csrf || payload?.data?.csrf);
    setMessage(payload?.error?.message || t('auth.session_expired'));
    modal.classList.add('open');
    if (emailInput) {
        emailInput.focus();
    }
};

const closeModal = () => {
    modal.classList.remove('open');
    clearErrors();
    form.reset();
};

const setLoading = (loading) => {
    form.querySelectorAll('input, button').forEach((field) => {
        field.disabled = loading;
    });
    if (submit) {
        submit.textContent = loading ? t('common.loading') : t('auth.login');
    }
};

const heartbeat = async () => {
    try {
        const { response, raw } = await requestJson(heartbeatEndpoint, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        updateCsrfToken(raw?.data?.csrf || raw?.error?.csrf);

        if (response.ok && raw?.ok === true) {
            return;
        }

        if (response.status === 401 || response.status === 403 || raw?.error?.code === 'UNAUTHENTICATED') {
            openModal(raw);
        }
    } catch (_) {
    }
};

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearErrors();
    setLoading(true);

    try {
        const { response, raw } = await postForm(loginEndpoint, form, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        setLoading(false);
        updateCsrfToken(raw?.data?.csrf || raw?.error?.csrf);

        if (response.ok && raw?.ok === true) {
            closeModal();
            return;
        }

        const errors = raw?.error?.errors && typeof raw.error.errors === 'object' ? raw.error.errors : {};
        errorFields.forEach((field) => {
            const name = field.getAttribute('data-session-login-error') || '';
            const text = String(errors[name] || '').trim();
            field.textContent = text;
            field.hidden = text === '';
        });
        setMessage(raw?.error?.message || t('auth.login_failed'));
    } catch (_) {
        setLoading(false);
        setMessage(t('auth.login_failed'));
    }
});

heartbeat();
window.setInterval(heartbeat, 30000);
})();
