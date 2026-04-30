(() => {
    const app = window.tinycms = window.tinycms || {};

    const esc = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const currentCsrf = () => document.querySelector('input[name="_csrf"]')?.value || '';

    const escapeSelector = (value) => {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(String(value || ''));
        }
        return String(value || '').replace(/["\\]/g, '\\$&');
    };

    const sessionStore = {
        get(key) {
            try {
                return window.sessionStorage.getItem(key) || '';
            } catch (_) {
                return '';
            }
        },
        set(key, value) {
            try {
                window.sessionStorage.setItem(key, value);
            } catch (_) {
            }
        },
        remove(key) {
            try {
                window.sessionStorage.removeItem(key);
            } catch (_) {
            }
        },
    };

    const scriptBaseUrl = (script) => String(script?.src || '').replace(/[^/]+(?:\?.*)?$/, '');

    const scriptLoaded = (attr, src) => Array.prototype.some.call(
        document.scripts,
        (script) => script.getAttribute(attr) === src,
    );

    const loadScript = (src, attr) => new Promise((resolve, reject) => {
        if (scriptLoaded(attr, src)) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.defer = true;
        script.setAttribute(attr, src);
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });

    const loadScripts = (currentScript, modules, attr) => {
        const root = scriptBaseUrl(currentScript);
        return modules.reduce((chain, module) => (
            chain.then(() => loadScript(root + module, attr))
        ), Promise.resolve());
    };

    app.support = { esc, escapeSelector, currentCsrf, loadScripts, sessionStore };
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
