(() => {
    const esc = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

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

    window.tinycms = window.tinycms || {};
    window.tinycms.icons = { href, icon, sprite };
})();
