(() => {
const t = window.tinycms?.i18n?.t || (() => '');
const postForm = window.tinycms?.api?.http?.postForm;

const form = document.querySelector('[data-admin-login-form]');
const message = document.querySelector('[data-admin-login-message]');
const submit = form?.querySelector('[data-admin-login-submit]');
const errors = form ? Array.from(form.querySelectorAll('[data-admin-login-error]')) : [];
const redirect = String(form?.getAttribute('data-admin-login-redirect') || '').trim();

if (!form || typeof postForm !== 'function') {
    return;
}

const setMessage = (text) => {
    if (!message) {
        return;
    }
    message.textContent = String(text || '').trim();
    message.hidden = message.textContent === '';
};

const clearErrors = () => {
    errors.forEach((field) => {
        field.textContent = '';
        field.hidden = true;
    });
};

const setFieldErrors = (fieldErrors) => {
    const mapped = fieldErrors && typeof fieldErrors === 'object' ? fieldErrors : {};
    errors.forEach((field) => {
        const name = field.getAttribute('data-admin-login-error') || '';
        const text = String(mapped[name] || '').trim();
        field.textContent = text;
        field.hidden = text === '';
    });
};

const updateCsrfToken = (payload) => {
    const token = String(payload?.data?.csrf || payload?.error?.csrf || '').trim();
    if (token === '') {
        return;
    }
    document.querySelectorAll('input[name="_csrf"]').forEach((input) => {
        input.value = token;
    });
};

const setLoading = (loading) => {
    form.querySelectorAll('input:not([type="hidden"]), button').forEach((field) => {
        field.disabled = loading;
    });
    if (submit) {
        submit.textContent = loading ? t('common.loading') : t('auth.login');
    }
};

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearErrors();
    setMessage('');
    setLoading(true);

    try {
        const { response, raw } = await postForm(form.action, new FormData(form), {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        updateCsrfToken(raw);

        if (response.ok && raw?.ok === true) {
            window.location.href = redirect !== '' ? redirect : '/';
            return;
        }

        if (response.status === 419) {
            setMessage(t('common.csrf_expired'));
            return;
        }

        setFieldErrors(raw?.error?.errors || {});
        setMessage(raw?.error?.message || t('auth.login_failed'));
    } catch (_) {
        setMessage(t('auth.login_failed'));
    } finally {
        setLoading(false);
    }
});
})();
