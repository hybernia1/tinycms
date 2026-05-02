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

    const attachFrameHandlers = () => {
        let doc = null;
        try {
            doc = frame.contentDocument;
        } catch (error) {
            return;
        }
        if (!doc || doc.documentElement?.dataset.customizerBound === '1') {
            return;
        }

        doc.documentElement.dataset.customizerBound = '1';
        doc.addEventListener('click', (event) => {
            const targetNode = event.target?.nodeType === 1 ? event.target : event.target?.parentElement;
            const widgetEdit = targetNode?.closest('[data-customizer-widget-edit]');
            if (widgetEdit) {
                event.preventDefault();
                openWidgetRow(widgetEdit);
                return;
            }

            const link = targetNode?.closest('a[href]');
            if (!link || link.tagName !== 'A' || link.hasAttribute('download')) {
                return;
            }

            const target = String(link.target || '').toLowerCase();
            if (target !== '' && target !== '_self') {
                return;
            }

            if (setPreviewBase(link.href)) {
                event.preventDefault();
            }
        });

        doc.addEventListener('submit', (event) => {
            const targetForm = event.target;
            if (!targetForm || targetForm.tagName !== 'FORM' || String(targetForm.method || 'get').toLowerCase() !== 'get') {
                return;
            }

            const url = new URL(targetForm.action || previewBase, window.location.href);
            if (url.origin !== window.location.origin) {
                return;
            }

            removePreviewParams(url);
            new FormData(targetForm).forEach((value, key) => {
                url.searchParams.set(String(key), String(value));
            });

            event.preventDefault();
            setPreviewBase(url.toString());
        });
    };

    frame.addEventListener('load', () => {
        attachFrameHandlers();
        try {
            const loaded = new URL(frame.contentWindow.location.href);
            const hadPreview = loaded.searchParams.has('theme_preview');
            if (setPreviewBase(loaded.toString(), false) && !hadPreview) {
                refresh();
            }
        } catch (error) {
            return;
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
