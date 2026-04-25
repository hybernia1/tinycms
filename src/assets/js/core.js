(() => {
    const app = window.tinycms = window.tinycms || {};

    const esc = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const currentCsrf = () => document.querySelector('input[name="_csrf"]')?.value || '';

    app.support = { esc, currentCsrf };
})();

(() => {
    const app = window.tinycms = window.tinycms || {};
    const esc = app.support?.esc || ((value) => String(value || ''));
    const sprite = () => String(window.tinycmsIconSprite || '').trim();
    const cleanName = (name) => /^[a-z0-9_-]+$/i.test(String(name || '')) ? String(name).trim() : '';

    const href = (name) => {
        const icon = cleanName(name);
        const base = sprite();
        return icon !== '' && base !== '' ? `${base}#icon-${icon}` : '';
    };

    const icon = (name, classes = 'icon') => {
        const iconHref = href(name);
        if (iconHref === '') {
            return '';
        }

        const className = String(classes || '').trim() || 'icon';
        return `<svg class="${esc(className)}" aria-hidden="true" focusable="false"><use href="${esc(iconHref)}"></use></svg>`;
    };

    app.icons = { href, icon, sprite };
})();

(() => {
    const app = window.tinycms = window.tinycms || {};
    const i18n = window.tinycmsI18n || {};

    const value = (path) => String(path || '').split('.').reduce((acc, key) => (
        acc && Object.prototype.hasOwnProperty.call(acc, key) ? acc[key] : undefined
    ), i18n);

    const api = {
        value,
        t: (path, fallback = '') => {
            const result = value(path);
            return typeof result === 'string' && result !== '' ? result : fallback;
        },
        ta: (path, fallback = []) => {
            const result = value(path);
            return Array.isArray(result) && result.length ? result : fallback;
        },
    };

    app.i18n = api;
})();
