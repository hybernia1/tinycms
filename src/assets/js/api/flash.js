(() => {
    const app = window.tinycms = window.tinycms || {};
    const api = app.api = app.api || {};
    const t = app.i18n?.t || (() => '');
    const esc = app.support?.esc || ((value) => String(value || ''));
    const icon = app.icons?.icon || (() => '');
    const sessionStore = app.support?.sessionStore || { get: () => '', set: () => {}, remove: () => {} };

    const resolveFlashContainer = () => {
        const existing = document.querySelector('.admin-flash-stack');
        if (existing) {
            return existing;
        }

        const main = document.querySelector('.admin-main');
        if (main) {
            const stack = document.createElement('div');
            stack.className = 'admin-flash-stack';
            stack.setAttribute('aria-live', 'polite');
            main.prepend(stack);
            return stack;
        }

        return document.querySelector('.admin-content') || document.body;
    };

    const pushFlash = (type, message) => {
        const text = String(message || '').trim();
        if (text === '') {
            return;
        }

        const container = resolveFlashContainer();
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

        sessionStore.set('tinycms:flash', JSON.stringify({
            type,
            message: text,
        }));
    };

    const consumeStoredFlash = () => {
        try {
            const raw = sessionStore.get('tinycms:flash');
            if (!raw) {
                return;
            }
            sessionStore.remove('tinycms:flash');
            const payload = JSON.parse(raw);
            pushFlash((payload && payload.type) || 'success', (payload && payload.message) || '');
        } catch (_) {
        }
    };

    Object.assign(api, {
        pushFlash,
        storeFlash,
        flash: {
            push: pushFlash,
            store: storeFlash,
            consume: consumeStoredFlash,
        },
    });

    consumeStoredFlash();
})();
