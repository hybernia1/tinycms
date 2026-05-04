(() => {
    const form = document.querySelector('[data-theme-customizer]');
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const app = window.tinycms || {};
    const icon = app.icons?.icon || (() => '');
    const t = app.i18n?.t || ((key, fallback = '') => fallback || key);
    const frame = document.querySelector(form.getAttribute('data-preview-frame') || '');
    if (!(frame instanceof HTMLIFrameElement)) {
        return;
    }

    const openLink = document.querySelector('[data-preview-open]');
    const customizerBase = form.getAttribute('data-customizer-url') || window.location.pathname;
    const customizerRoot = form.closest('[data-customizer-root]') || document;
    const previewRoot = document.querySelector('[data-customizer-preview]');
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
        const widgetAreas = customizerRoot.querySelector('[data-widget-area-visibility-value]');
        if (widgetAreas instanceof HTMLInputElement) {
            url.searchParams.set('theme[enabled_widget_areas]', widgetAreas.value);
        }
        url.searchParams.set('theme_preview', '1');

        return url.toString();
    };

    const refresh = () => {
        syncCustomizerUrl();
        frame.src = previewUrl();
    };

    const syncColorControl = (control) => {
        const value = control.querySelector('[data-color-value]');
        const picker = control.querySelector('[data-color-picker]');
        const transparent = control.querySelector('[data-color-transparent]');
        if (!(value instanceof HTMLInputElement) || !(picker instanceof HTMLInputElement) || !(transparent instanceof HTMLInputElement)) {
            return;
        }

        picker.disabled = transparent.checked;
        value.value = transparent.checked ? 'transparent' : picker.value;
        control.classList.toggle('is-transparent', transparent.checked);
    };

    const syncWidgetAreaVisibility = () => {
        const values = Array.from(customizerRoot.querySelectorAll('[data-widget-area-visibility-value]'))
            .filter((item) => item instanceof HTMLInputElement);
        if (values.length === 0) {
            return;
        }

        const toggles = Array.from(customizerRoot.querySelectorAll('[data-widget-area-visibility-toggle]'));
        const enabled = toggles.filter((item) => item.getAttribute('aria-pressed') === 'true');
        const nextValue = enabled.length === toggles.length
            ? '*'
            : enabled.map((item) => item instanceof HTMLButtonElement ? item.value : '').filter(Boolean).join(',');
        values.forEach((value) => {
            value.value = nextValue;
        });
    };

    const setWidgetAreaVisibility = (button, enabled) => {
        const label = enabled ? t('widgets.hide_area', 'Hide area') : t('widgets.show_area', 'Show area');
        button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        button.closest('[data-widget-area-visibility-row]')?.classList.toggle('is-hidden', !enabled);

        const target = button.querySelector('[data-widget-area-visibility-icon]');
        if (target) {
            target.innerHTML = icon(enabled ? 'hide' : 'show');
        }

        syncWidgetAreaVisibility();
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

    const setPreviewDevice = (button) => {
        const device = String(button.getAttribute('data-preview-device') || '').trim();
        if (!['desktop', 'tablet', 'mobile'].includes(device)) {
            return;
        }

        if (previewRoot instanceof HTMLElement) {
            previewRoot.setAttribute('data-preview-device', device);
        }

        customizerRoot.querySelectorAll('.customizer-device-button[data-preview-device]').forEach((item) => {
            const active = item === button;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
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
    customizerRoot.querySelectorAll('[data-color-field]').forEach(syncColorControl);
    syncWidgetAreaVisibility();
    customizerRoot.addEventListener('input', (event) => {
        const target = event.target;
        if (!(target instanceof Element) || !target.matches('[data-color-picker]')) {
            return;
        }

        const control = target.closest('[data-color-field]');
        if (control instanceof HTMLElement) {
            syncColorControl(control);
        }
    });
    customizerRoot.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof Element) || !target.matches('[data-color-picker], [data-color-transparent]')) {
            return;
        }

        const colorControl = target.closest('[data-color-field]');
        if (colorControl instanceof HTMLElement) {
            syncColorControl(colorControl);
        }
    });
    customizerRoot.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        if (event.target.closest('[data-customizer-save]')) {
            activeForm().requestSubmit();
            return;
        }

        const areaToggle = event.target.closest('[data-widget-area-visibility-toggle]');
        if (areaToggle instanceof HTMLButtonElement) {
            setWidgetAreaVisibility(areaToggle, areaToggle.getAttribute('aria-pressed') !== 'true');
            scheduleRefresh();
            return;
        }

        const deviceButton = event.target.closest('.customizer-device-button[data-preview-device]');
        if (deviceButton instanceof HTMLElement) {
            setPreviewDevice(deviceButton);
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
            return;
        }

        const colorReset = event.target.closest('[data-color-reset]');
        if (colorReset) {
            const control = colorReset.closest('[data-color-field]');
            if (!(control instanceof HTMLElement)) {
                return;
            }

            const picker = control.querySelector('[data-color-picker]');
            const transparent = control.querySelector('[data-color-transparent]');
            if (!(picker instanceof HTMLInputElement) || !(transparent instanceof HTMLInputElement)) {
                return;
            }

            const fallback = '#000000';
            const defaultValue = String(picker.getAttribute('data-color-default') || '').trim().toLowerCase();
            picker.value = /^#[0-9a-f]{6}$/i.test(defaultValue) ? defaultValue : fallback;
            transparent.checked = false;
            syncColorControl(control);
            scheduleRefresh();
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
