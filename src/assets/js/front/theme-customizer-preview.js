(() => {
    const source = 'tinycms:customizer-preview';

    const post = (payload) => {
        if (window.parent === window) {
            return;
        }

        window.parent.postMessage({ source, ...payload }, '*');
    };

    const navigationUrl = (value) => {
        const url = new URL(value || '/', window.location.href);
        if (url.origin !== window.location.origin) {
            return { url: null, prevent: false };
        }

        const path = `/${url.pathname.replace(/^\/+/, '')}`;
        if (['/customizer', '/admin', '/auth'].includes(path) || path.startsWith('/customizer/') || path.startsWith('/admin/') || path.startsWith('/auth/')) {
            return { url: null, prevent: true };
        }

        return { url, prevent: true };
    };

    const navigate = (url) => post({ action: 'navigate', url });

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const widgetEdit = event.target.closest('[data-customizer-widget-edit]');
        if (widgetEdit) {
            event.preventDefault();
            const widget = widgetEdit.closest('[data-customizer-widget]');
            post({
                action: 'edit-widget',
                area: widget?.getAttribute('data-customizer-widget-area') || '',
                index: widget?.getAttribute('data-customizer-widget-index') || '0',
            });
            return;
        }

        const link = event.target.closest('a[href]');
        if (!(link instanceof HTMLAnchorElement) || link.hasAttribute('download')) {
            return;
        }

        const target = String(link.target || '').toLowerCase();
        if (target !== '' && target !== '_self') {
            return;
        }

        const targetUrl = navigationUrl(link.href);
        if (!targetUrl.url) {
            if (targetUrl.prevent) {
                event.preventDefault();
            }
            return;
        }

        event.preventDefault();
        navigate(targetUrl.url.toString());
    });

    document.addEventListener('submit', (event) => {
        const targetForm = event.target;
        if (!(targetForm instanceof HTMLFormElement) || String(targetForm.method || 'get').toLowerCase() !== 'get') {
            return;
        }

        const targetUrl = navigationUrl(targetForm.action || window.location.href);
        if (!targetUrl.url) {
            if (targetUrl.prevent) {
                event.preventDefault();
            }
            return;
        }

        new FormData(targetForm).forEach((value, key) => {
            targetUrl.url.searchParams.set(String(key), String(value));
        });

        event.preventDefault();
        navigate(targetUrl.url.toString());
    });
})();
