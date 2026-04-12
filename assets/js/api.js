(() => {
    const t = window.tinycms?.i18n?.t || (() => '');

    const esc = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const iconSprite = (() => {
        const iconUse = document.querySelector('svg use[href*="#icon-"]');
        return iconUse ? String(iconUse.getAttribute('href') || '').split('#')[0] : '';
    })();

    const icon = (name) => iconSprite !== ''
        ? `<svg class="icon" aria-hidden="true" focusable="false"><use href="${esc(iconSprite)}#icon-${esc(name)}"></use></svg>`
        : '';

    const pushFlash = (type, message) => {
        const text = String(message || '').trim();
        if (text === '') {
            return;
        }

        const container = document.querySelector('.admin-content');
        if (!container) {
            return;
        }

        container.querySelectorAll('.flash').forEach((node) => node.remove());

        const flashType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'warning';
        const flash = document.createElement('div');
        const uiType = flashType === 'info' ? 'warning' : flashType;
        const flashIcon = uiType === 'success' ? 'success' : (uiType === 'error' ? 'error' : 'warning');
        flash.className = `flash flash-${uiType}`;
        flash.innerHTML = `
            <span class="d-flex align-center gap-2">${icon(flashIcon)}<span>${esc(text)}</span></span>
            <button type="button" data-flash-close aria-label="${esc(t('common.close_notice'))}" title="${esc(t('common.close_notice'))}">
                ${icon('cancel')}
            </button>
        `;
        container.prepend(flash);
    };

    const storeFlash = (type, message) => {
        const text = String(message || '').trim();
        if (text === '') {
            return;
        }

        try {
            window.sessionStorage.setItem('tinycms:flash', JSON.stringify({
                type,
                message: text,
            }));
        } catch (_) {
        }
    };

    const consumeStoredFlash = () => {
        try {
            const raw = window.sessionStorage.getItem('tinycms:flash');
            if (!raw) {
                return;
            }
            window.sessionStorage.removeItem('tinycms:flash');
            const payload = JSON.parse(raw);
            pushFlash((payload && payload.type) || 'success', (payload && payload.message) || '');
        } catch (_) {
        }
    };

    consumeStoredFlash();

    window.tinycms = window.tinycms || {};
    window.tinycms.api = {
        esc,
        icon,
        pushFlash,
        storeFlash,
    };
})();
