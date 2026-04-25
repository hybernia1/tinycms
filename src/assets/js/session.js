(() => {
    const app = window.tinycms = window.tinycms || {};
    const session = app.session = app.session || {};
    const t = app.i18n?.t || (() => '');
    const icon = app.icons?.icon || (() => '');
    const esc = app.support?.esc || ((value) => String(value || ''));
    const currentCsrf = app.support?.currentCsrf || (() => '');

    const ensureSessionModal = (loginEndpoint) => {
        let modal = document.querySelector('[data-session-login-modal]');
        if (modal) {
            return modal;
        }

        modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.id = 'session-login-modal';
        modal.setAttribute('data-session-login-modal', '');
        modal.innerHTML = `
        <div class="modal session-login-modal">
            <h3 class="m-0 mb-3">${esc(t('auth.login'))}</h3>
            <p class="m-0 mb-3">${esc(t('auth.session_expired'))}</p>
            <p class="m-0 mb-3 text-danger" data-session-login-message hidden></p>
            <form method="post" action="${esc(loginEndpoint)}" data-session-login-form>
                <input type="hidden" name="_csrf" value="${esc(currentCsrf())}">
                <div class="mb-3">
                    <label>${esc(t('common.email'))}</label>
                    <div class="field-with-icon">
                        <span class="field-overlay field-overlay-start field-icon" aria-hidden="true">${icon('email')}</span>
                        <input class="field-control-with-start-icon" type="email" name="email" data-session-login-email required>
                    </div>
                    <small class="text-danger" data-session-login-error="email" hidden></small>
                </div>
                <div class="mb-3">
                    <label>${esc(t('common.password'))}</label>
                    <div class="field-with-icon">
                        <input class="field-control-with-end-icon" type="password" name="password" data-password-input required>
                        <button class="field-overlay field-overlay-end field-icon-button" type="button" data-password-toggle aria-label="${esc(t('auth.show_password'))}" title="${esc(t('auth.show_password'))}">
                            ${icon('show')}
                        </button>
                    </div>
                    <small class="text-danger" data-session-login-error="password" hidden></small>
                </div>
                <div class="mb-4">
                    <label><input type="checkbox" name="remember" value="1"> ${esc(t('auth.remember'))}</label>
                </div>
                <button class="btn btn-primary" type="submit" data-session-login-submit>${esc(t('auth.login'))}</button>
            </form>
        </div>
    `;
        document.body.appendChild(modal);
        return modal;
    };

    const ensureConnectionModal = () => {
        let modal = document.querySelector('[data-connection-lost-modal]');
        if (modal) {
            return modal;
        }

        modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.id = 'connection-lost-modal';
        modal.setAttribute('data-connection-lost-modal', '');
        modal.innerHTML = `
        <div class="modal session-login-modal">
            <h3 class="m-0 mb-3">${esc(t('common.connection_lost'))}</h3>
            <p class="m-0 mb-4">${esc(t('auth.connection_lost'))}</p>
            <button class="btn btn-primary" type="button" data-connection-lost-retry>${esc(t('common.retry'))}</button>
        </div>
    `;
        document.body.appendChild(modal);
        return modal;
    };

    session.template = {
        ensureConnectionModal,
        ensureSessionModal,
    };
})();

(() => {
const app = window.tinycms = window.tinycms || {};
const t = app.i18n?.t || (() => '');
const postForm = app.api?.http?.postForm;
const requestJson = app.api?.http?.requestJson;
const modalUi = app.ui?.modal || {
    open: (modal) => modal?.classList.add('open'),
    close: (modal) => modal?.classList.remove('open'),
};
const template = app.session?.template || {};
const heartbeatEndpoint = document.body.getAttribute('data-heartbeat-endpoint') || '';
const loginEndpoint = document.body.getAttribute('data-heartbeat-login-endpoint') || '';

const modal = template.ensureSessionModal?.(loginEndpoint) || document.querySelector('[data-session-login-modal]');
const form = modal?.querySelector('[data-session-login-form]');
const message = modal?.querySelector('[data-session-login-message]');
const submit = form?.querySelector('[data-session-login-submit]');
const emailInput = form?.querySelector('[data-session-login-email]');
const errorFields = form ? Array.from(form.querySelectorAll('[data-session-login-error]')) : [];
const connectionModal = template.ensureConnectionModal?.() || document.querySelector('[data-connection-lost-modal]');
const retryButton = connectionModal?.querySelector('[data-connection-lost-retry]');
let heartbeatInFlight = false;
let loginInFlight = false;
let connectionLost = false;

if (!modal || !form || !connectionModal || typeof postForm !== 'function' || typeof requestJson !== 'function' || heartbeatEndpoint === '' || loginEndpoint === '') {
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
    modalUi.open(modal);
    if (emailInput) {
        emailInput.focus();
    }
};

const closeModal = () => {
    modalUi.close(modal);
    clearErrors();
    form.reset();
};

const openConnectionModal = () => {
    modalUi.open(connectionModal);
};

const closeConnectionModal = () => {
    modalUi.close(connectionModal);
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
