(() => {
    const roots = Array.from(document.querySelectorAll('[data-color-field]'));
    if (roots.length === 0) {
        return;
    }

    const app = window.tinycms || {};
    const icon = app.icons?.icon || (() => '');
    const t = app.i18n?.t || ((key, fallback = '') => fallback || key);
    const presets = ['#0f172a', '#1e293b', '#334155', '#64748b', '#94a3b8', '#cbd5e1', '#e2e8f0', '#f8fafc', '#ef4444', '#f97316', '#eab308', '#22c55e', '#14b8a6', '#06b6d4', '#3b82f6', '#8b5cf6'];
    const validHex = (value) => /^#[0-9a-f]{6}$/i.test(String(value || '').trim());

    const sync = (control) => {
        const value = control.querySelector('[data-color-value]');
        const picker = control.querySelector('[data-color-picker]');
        const transparent = control.querySelector('[data-color-transparent]');
        const swatch = control.querySelector('[data-custom-color-swatch]');
        const label = control.querySelector('[data-custom-color-label]');
        const hex = control.querySelector('[data-custom-color-hex]');
        if (!(value instanceof HTMLInputElement) || !(picker instanceof HTMLInputElement) || !(transparent instanceof HTMLInputElement)) {
            return;
        }

        picker.disabled = transparent.checked;
        value.value = transparent.checked ? 'transparent' : picker.value;
        control.classList.toggle('is-transparent', transparent.checked);

        if (swatch instanceof HTMLElement) {
            swatch.style.backgroundColor = transparent.checked ? 'transparent' : picker.value;
            swatch.classList.toggle('is-transparent', transparent.checked);
        }
        if (label instanceof HTMLElement) {
            label.textContent = transparent.checked ? 'transparent' : picker.value.toLowerCase();
        }
        if (hex instanceof HTMLInputElement) {
            hex.value = picker.value.toLowerCase();
            hex.disabled = transparent.checked;
        }
    };

    const buildUi = (control) => {
        const picker = control.querySelector('[data-color-picker]');
        const transparent = control.querySelector('[data-color-transparent]');
        if (!(picker instanceof HTMLInputElement) || !(transparent instanceof HTMLInputElement)) {
            return;
        }

        picker.classList.add('custom-color-native');

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'custom-color-trigger';
        trigger.setAttribute('aria-expanded', 'false');

        const swatch = document.createElement('span');
        swatch.className = 'custom-color-swatch';
        swatch.setAttribute('data-custom-color-swatch', '1');

        const label = document.createElement('span');
        label.className = 'custom-color-label';
        label.setAttribute('data-custom-color-label', '1');

        trigger.append(swatch, label);

        const panel = document.createElement('div');
        panel.className = 'custom-color-panel';

        const hex = document.createElement('input');
        hex.type = 'text';
        hex.className = 'custom-color-hex';
        hex.setAttribute('data-custom-color-hex', '1');
        hex.setAttribute('spellcheck', 'false');

        const palette = document.createElement('div');
        palette.className = 'custom-color-palette';
        presets.forEach((color) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'custom-color-preset';
            btn.value = color;
            btn.style.backgroundColor = color;
            btn.setAttribute('aria-label', color);
            palette.appendChild(btn);
        });

        const actions = document.createElement('div');
        actions.className = 'custom-color-actions';

        const nativeBtn = document.createElement('button');
        nativeBtn.type = 'button';
        nativeBtn.className = 'btn btn-light btn-icon';
        nativeBtn.innerHTML = icon('brush');
        nativeBtn.setAttribute('title', 'Native picker');

        const transparentBtn = document.createElement('button');
        transparentBtn.type = 'button';
        transparentBtn.className = 'btn btn-light';
        transparentBtn.textContent = t('themes.color_transparent', 'Transparent');

        const resetBtn = document.createElement('button');
        resetBtn.type = 'button';
        resetBtn.className = 'btn btn-light btn-icon';
        resetBtn.innerHTML = icon('restore');
        resetBtn.setAttribute('title', t('themes.color_reset', 'Reset color'));

        actions.append(nativeBtn, transparentBtn, resetBtn);
        panel.append(hex, palette, actions);

        const host = document.createElement('div');
        host.className = 'custom-color';
        host.append(trigger, panel);
        picker.insertAdjacentElement('afterend', host);

        trigger.addEventListener('click', () => {
            const open = host.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        nativeBtn.addEventListener('click', () => {
            transparent.checked = false;
            sync(control);
            if (typeof picker.showPicker === 'function') {
                picker.showPicker();
                return;
            }
            picker.click();
        });

        transparentBtn.addEventListener('click', () => {
            transparent.checked = !transparent.checked;
            sync(control);
            picker.dispatchEvent(new Event('change', { bubbles: true }));
        });

        resetBtn.addEventListener('click', () => {
            const defaultValue = String(picker.getAttribute('data-color-default') || '').trim().toLowerCase();
            picker.value = validHex(defaultValue) ? defaultValue : '#000000';
            transparent.checked = false;
            sync(control);
            picker.dispatchEvent(new Event('change', { bubbles: true }));
        });

        hex.addEventListener('change', () => {
            const next = hex.value.trim().toLowerCase();
            if (!validHex(next)) {
                hex.value = picker.value.toLowerCase();
                return;
            }

            picker.value = next;
            transparent.checked = false;
            sync(control);
            picker.dispatchEvent(new Event('change', { bubbles: true }));
        });

        palette.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLButtonElement) || !target.classList.contains('custom-color-preset')) {
                return;
            }

            picker.value = target.value;
            transparent.checked = false;
            sync(control);
            picker.dispatchEvent(new Event('change', { bubbles: true }));
        });
    };

    roots.forEach((control) => {
        buildUi(control);
        sync(control);
    });

    document.addEventListener('click', (event) => {
        roots.forEach((control) => {
            const target = event.target;
            if (!(target instanceof Element) || control.contains(target)) {
                return;
            }

            const host = control.querySelector('.custom-color');
            const trigger = control.querySelector('.custom-color-trigger');
            if (host instanceof HTMLElement) {
                host.classList.remove('is-open');
            }
            if (trigger instanceof HTMLButtonElement) {
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    });

    document.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof Element) || !target.matches('[data-color-picker], [data-color-transparent]')) {
            return;
        }

        const control = target.closest('[data-color-field]');
        if (control instanceof HTMLElement) {
            sync(control);
        }
    });
})();
