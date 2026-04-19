(() => {
const t = window.tinycms?.i18n?.t || (() => '');
const postForm = window.tinycms?.api?.http?.postForm;
const requestJson = window.tinycms?.api?.http?.requestJson;
const modalApi = window.tinycms?.modal;
const openModalElement = modalApi?.open;
const closeModalElement = modalApi?.close;

const modal = document.querySelector('[data-session-login-modal]');
const form = modal?.querySelector('[data-session-login-form]');
const message = modal?.querySelector('[data-session-login-message]');
const submit = form?.querySelector('[data-session-login-submit]');
const emailInput = form?.querySelector('[data-session-login-email]');
const errorFields = form ? Array.from(form.querySelectorAll('[data-session-login-error]')) : [];
const connectionModal = document.querySelector('[data-connection-lost-modal]');
const retryButton = connectionModal?.querySelector('[data-connection-lost-retry]');
const heartbeatEndpoint = document.body.getAttribute('data-heartbeat-endpoint') || '';
const loginEndpoint = document.body.getAttribute('data-heartbeat-login-endpoint') || '';
let heartbeatInFlight = false;
let loginInFlight = false;
let connectionLost = false;

if (!modal || !form || !connectionModal || typeof postForm !== 'function' || typeof requestJson !== 'function' || typeof openModalElement !== 'function' || typeof closeModalElement !== 'function' || heartbeatEndpoint === '' || loginEndpoint === '') {
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
    setMessage(payload?.error?.message || '');
    openModalElement(modal);
    if (emailInput) {
        emailInput.focus();
    }
};

const closeModal = () => {
    closeModalElement(modal);
    clearErrors();
    form.reset();
};

const openConnectionModal = () => {
    openModalElement(connectionModal);
};

const closeConnectionModal = () => {
    closeModalElement(connectionModal);
};

const setLoading = (loading) => {
    form.querySelectorAll('input:not([type="hidden"]), button').forEach((field) => {
        field.disabled = loading;
    });
    if (submit) {
        submit.textContent = loading ? t('common.loading') : t('auth.login');
    }
};

const refreshCsrfToken = async () => {
    try {
        const { raw } = await requestJson(heartbeatEndpoint, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        updateCsrfToken(raw?.data?.csrf || raw?.error?.csrf);
    } catch (_) {
    }
};

const heartbeat = async (ignoreConnectionModal = false) => {
    if (modal.classList.contains('open') || (!ignoreConnectionModal && connectionModal.classList.contains('open')) || heartbeatInFlight || loginInFlight) {
        return;
    }

    heartbeatInFlight = true;
    try {
        const { response, raw } = await requestJson(heartbeatEndpoint, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        updateCsrfToken(raw?.data?.csrf || raw?.error?.csrf);
        connectionLost = false;
        closeConnectionModal();

        if (response.ok && raw?.ok === true) {
            return;
        }

        if (response.status === 401 || response.status === 403 || raw?.error?.code === 'UNAUTHENTICATED') {
            openModal(raw);
        }
    } catch (_) {
        if (connectionLost || modal.classList.contains('open')) {
            return;
        }
        connectionLost = true;
        openConnectionModal();
    } finally {
        heartbeatInFlight = false;
    }
};

retryButton?.addEventListener('click', () => {
    heartbeat(true);
});

form.addEventListener('submit', async (event) => {
    if (loginInFlight) {
        return;
    }

    event.preventDefault();
    clearErrors();
    const payload = new FormData(form);
    setLoading(true);
    loginInFlight = true;

    try {
        const { response, raw } = await postForm(loginEndpoint, payload, {
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

        if (response.status === 419) {
            await refreshCsrfToken();
            setMessage(t('auth.session_expired'));
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
    } finally {
        loginInFlight = false;
    }
});

heartbeat();
window.setInterval(heartbeat, 30000);
})();
