(() => {
    const showError = (message) => {
        const text = String(message || '').trim();
        if (text !== '') {
            window.alert(text);
        }
    };

    const submitApiForm = async (form) => {
        const response = await fetch(form.action, {
            method: (form.method || 'POST').toUpperCase(),
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const payload = await response.json().catch(() => null);
        if (!response.ok || payload?.ok !== true) {
            showError(payload?.error?.message || '');
            return;
        }

        const payloadRedirect = String(payload?.data?.redirect || '').trim();
        const fallbackRedirect = String(form.getAttribute('data-redirect-url') || '').trim();
        const redirect = payloadRedirect !== '' ? payloadRedirect : fallbackRedirect;
        if (redirect !== '') {
            const target = /^https?:\/\//i.test(redirect) || redirect.startsWith('/')
                ? redirect
                : '/' + redirect.replace(/^\/+/, '');
            window.location.href = target;
            return;
        }

        window.location.reload();
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-api-submit')) {
            return;
        }

        event.preventDefault();
        submitApiForm(form);
    });
})();
