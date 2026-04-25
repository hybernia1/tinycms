(() => {
    const app = window.tinycms = window.tinycms || {};
    const api = app.api = app.api || {};

    const updateCsrfFields = (payload) => {
        const token = String(payload?.data?.csrf || payload?.error?.csrf || '').trim();
        if (token === '') {
            return;
        }

        document.querySelectorAll('input[name="_csrf"]').forEach((input) => {
            input.value = token;
        });
    };

    const normalizePayload = (payload) => {
        if (!payload || !Object.prototype.hasOwnProperty.call(payload, 'ok')) {
            return {
                success: false,
                message: '',
                errorCode: 'INVALID_PAYLOAD',
                errors: {},
                data: null,
                meta: {},
            };
        }

        return {
            success: payload.ok === true,
            message: String(payload.ok === true ? (payload.data?.message || '') : (payload.error?.message || '')),
            errorCode: String(payload.error?.code || ''),
            errors: payload.error?.errors && typeof payload.error.errors === 'object' ? payload.error.errors : {},
            data: payload.data,
            meta: payload.meta || {},
        };
    };

    const requestJson = async (url, options = {}) => {
        const response = await fetch(url, options);
        const raw = await response.json().catch(() => ({}));
        return { response, data: normalizePayload(raw), raw };
    };

    const postForm = async (url, formOrData, options = {}) => {
        const body = formOrData instanceof FormData ? formOrData : new FormData(formOrData);
        const requestOptions = {
            ...options,
            method: 'POST',
            body,
            headers: {
                Accept: 'application/json',
                ...(options.headers || {}),
            },
        };
        return requestJson(url, requestOptions);
    };

    api.http = {
        normalizePayload,
        postForm,
        requestJson,
        updateCsrfFields,
    };
})();
