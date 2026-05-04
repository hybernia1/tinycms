(() => {
    const roots = Array.from(document.querySelectorAll('[data-color-field]'));
    if (roots.length === 0) {
        return;
    }

    const sync = (control) => {
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

    roots.forEach(sync);
    document.addEventListener('input', (event) => {
        const target = event.target;
        if (!(target instanceof Element) || !target.matches('[data-color-picker]')) {
            return;
        }

        const control = target.closest('[data-color-field]');
        if (control instanceof HTMLElement) {
            sync(control);
        }
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

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const colorReset = target.closest('[data-color-reset]');
        if (!(colorReset instanceof HTMLButtonElement)) {
            return;
        }

        const control = colorReset.closest('[data-color-field]');
        if (!(control instanceof HTMLElement)) {
            return;
        }

        const picker = control.querySelector('[data-color-picker]');
        const transparent = control.querySelector('[data-color-transparent]');
        const value = control.querySelector('[data-color-value]');
        if (!(picker instanceof HTMLInputElement) || !(transparent instanceof HTMLInputElement) || !(value instanceof HTMLInputElement)) {
            return;
        }

        const defaultValue = String(picker.getAttribute('data-color-default') || '').trim().toLowerCase();
        picker.value = /^#[0-9a-f]{6}$/i.test(defaultValue) ? defaultValue : '#000000';
        transparent.checked = false;
        sync(control);
        value.dispatchEvent(new Event('change', { bubbles: true }));
    });
})();
