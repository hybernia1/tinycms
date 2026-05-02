(() => {
    const form = document.querySelector('[data-theme-customizer]');
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const frame = document.querySelector(form.getAttribute('data-preview-frame') || '');
    if (!(frame instanceof HTMLIFrameElement)) {
        return;
    }

    const openLink = document.querySelector('[data-preview-open]');
    const customizerBase = form.getAttribute('data-customizer-url') || window.location.pathname;
    const customizerRoot = form.closest('[data-customizer-root]') || document;
    let previewBase = form.getAttribute('data-preview-base') || '/';
    let timer = 0;

    const removePreviewParams = (url) => {
        Array.from(url.searchParams.keys()).forEach((key) => {
            if (key === 'theme_preview' || key.startsWith('theme[')) {
                url.searchParams.delete(key);
            }
        });
    };

    const localUrl = (value) => {
        const url = new URL(value || '/', window.location.href);
        if (url.origin !== window.location.origin) {
            return null;
        }

        removePreviewParams(url);
        const path = `/${url.pathname.replace(/^\/+/, '')}`;
        if (['/customizer', '/admin', '/auth'].includes(path) || path.startsWith('/customizer/') || path.startsWith('/admin/') || path.startsWith('/auth/')) {
            return null;
        }

        return url;
    };

    const syncCustomizerUrl = () => {
        const url = new URL(customizerBase, window.location.href);
        const target = localUrl(previewBase);
        if (target) {
            url.searchParams.set('url', target.toString());
        }
        if (!target) {
            url.searchParams.delete('url');
        }

        window.history.replaceState(null, '', url.toString());
        if (openLink instanceof HTMLAnchorElement) {
            openLink.href = target ? target.toString() : '/';
        }
    };

    const previewUrl = () => {
        const url = localUrl(previewBase) || new URL('/', window.location.origin);
        const data = new FormData(form);

        data.forEach((value, key) => {
            if (String(key).startsWith('theme[')) {
                url.searchParams.set(key, String(value));
            }
        });
        url.searchParams.set('theme_preview', '1');

        return url.toString();
    };

    const refresh = () => {
        syncCustomizerUrl();
        frame.src = previewUrl();
    };

    const scheduleRefresh = () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(refresh, 250);
    };

    const activeForm = () => {
        const active = customizerRoot.querySelector('[data-customizer-screen].is-active');
        return active?.closest('form') || active?.querySelector('form') || form;
    };

    const setPreviewBase = (value, reload = true) => {
        const target = localUrl(value);
        if (!target) {
            return false;
        }

        previewBase = target.toString();
        syncCustomizerUrl();
        if (reload) {
            refresh();
        }
        return true;
    };

    const openScreen = (name) => {
        const targetName = String(name || '').trim() || 'main';
        let found = false;
        customizerRoot.querySelectorAll('[data-customizer-screen]').forEach((screen) => {
            const active = screen.getAttribute('data-customizer-screen') === targetName;
            screen.classList.toggle('is-active', active);
            if (active) {
                found = true;
                screen.scrollTop = 0;
                screen.closest('.customizer-controls')?.scrollTo({ top: 0, behavior: 'auto' });
            }
        });
        if (!found && targetName !== 'main') {
            openScreen('main');
        }
    };

    const openWidgetRow = (button) => {
        const area = button.getAttribute('data-customizer-widget-area') || button.closest('[data-customizer-widget]')?.getAttribute('data-customizer-widget-area') || '';
        const index = Number(button.getAttribute('data-customizer-widget-index') || button.closest('[data-customizer-widget]')?.getAttribute('data-customizer-widget-index') || 0);
        if (area === '' || !Number.isFinite(index)) {
            return;
        }

        const widgetForm = customizerRoot.querySelector('[data-customizer-widgets]');
        if (!widgetForm) {
            return;
        }

        openScreen(`widget-area-${area}`);
        const rows = Array.from(widgetForm.querySelectorAll('[data-widget-item]')).filter((row) => {
            const rowArea = row.querySelector('[data-widget-item-area]')?.value || row.closest('[data-widget-area]')?.getAttribute('data-widget-area') || '';
            return rowArea === area;
        });
        const row = rows[index] || null;
        if (!row) {
            openScreen('widgets');
            return;
        }

        const details = row.querySelector('[data-widget-item-details]');
        const toggle = row.querySelector('[data-widget-item-toggle]');
        if (details) {
            details.hidden = false;
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
        }
        row.classList.add('is-open');
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        details?.querySelector('input:not([type="hidden"]), textarea, select')?.focus({ preventScroll: true });
    };

    window.addEventListener('message', (event) => {
        if (event.source !== frame.contentWindow || !event.data || event.data.source !== 'tinycms:customizer-preview') {
            return;
        }

        if (event.data.action === 'edit-widget') {
            openWidgetRow({
                getAttribute: (name) => {
                    if (name === 'data-customizer-widget-area') {
                        return String(event.data.area || '');
                    }
                    if (name === 'data-customizer-widget-index') {
                        return String(event.data.index || 0);
                    }
                    return '';
                },
                closest: () => null,
            });
            return;
        }

        if (event.data.action === 'navigate') {
            setPreviewBase(String(event.data.url || ''));
        }
    });

    form.addEventListener('input', scheduleRefresh);
    form.addEventListener('change', scheduleRefresh);
    customizerRoot.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        if (event.target.closest('[data-customizer-save]')) {
            activeForm().requestSubmit();
            return;
        }

        const openButton = event.target.closest('[data-customizer-open]');
        if (openButton) {
            openScreen(openButton.getAttribute('data-customizer-open'));
            return;
        }

        const backButton = event.target.closest('[data-customizer-back]');
        if (backButton) {
            openScreen(backButton.getAttribute('data-customizer-back'));
        }
    });
    document.addEventListener('tinycms:api-form-success', (event) => {
        if (event.target instanceof HTMLFormElement && event.target.hasAttribute('data-preview-refresh-on-success')) {
            refresh();
        }
    });
    document.addEventListener('tinycms:media-setting-selected', scheduleRefresh);

    refresh();
})();
