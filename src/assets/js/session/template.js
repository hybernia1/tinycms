(() => {
const t = window.tinycms?.i18n?.t || (() => '');
const icon = window.tinycms?.icons?.icon || (() => '');

const esc = (value) => String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

const currentCsrf = () => document.querySelector('input[name="_csrf"]')?.value || '';

const ensureSessionModal = (loginEndpoint) => {
    let modal = document.querySelector('[data-session-login-modal]');
    if (modal) {
        return modal;
    }

    modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.id = 'session-login-modal';
    modal.setAttribute('data-session-login-modal', '');
    modal.innerHTML = `
        <div class="modal session-login-modal">
            <h3 class="m-0 mb-3">${esc(t('auth.login'))}</h3>
            <p class="m-0 mb-3">${esc(t('auth.session_expired'))}</p>
            <p class="m-0 mb-3 text-danger" data-session-login-message hidden></p>
            <form method="post" action="${esc(loginEndpoint)}" data-session-login-form>
                <input type="hidden" name="_csrf" value="${esc(currentCsrf())}">
                <div class="mb-3">
                    <label>${esc(t('common.email'))}</label>
                    <div class="field-with-icon">
                        <span class="field-overlay field-overlay-start field-icon" aria-hidden="true">${icon('email')}</span>
                        <input class="field-control-with-start-icon" type="email" name="email" data-session-login-email required>
                    </div>
                    <small class="text-danger" data-session-login-error="email" hidden></small>
                </div>
                <div class="mb-3">
                    <label>${esc(t('common.password'))}</label>
                    <div class="field-with-icon">
                        <input class="field-control-with-end-icon" type="password" name="password" data-password-input required>
                        <button class="field-overlay field-overlay-end field-icon-button" type="button" data-password-toggle aria-label="${esc(t('auth.show_password'))}" title="${esc(t('auth.show_password'))}">
                            ${icon('show')}
                        </button>
                    </div>
                    <small class="text-danger" data-session-login-error="password" hidden></small>
                </div>
                <div class="mb-4">
                    <label><input type="checkbox" name="remember" value="1"> ${esc(t('auth.remember'))}</label>
                </div>
                <button class="btn btn-primary" type="submit" data-session-login-submit>${esc(t('auth.login'))}</button>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
    return modal;
};

const ensureConnectionModal = () => {
    let modal = document.querySelector('[data-connection-lost-modal]');
    if (modal) {
        return modal;
    }

    modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.id = 'connection-lost-modal';
    modal.setAttribute('data-connection-lost-modal', '');
    modal.innerHTML = `
        <div class="modal session-login-modal">
            <h3 class="m-0 mb-3">${esc(t('common.connection_lost'))}</h3>
            <p class="m-0 mb-4">${esc(t('auth.connection_lost'))}</p>
            <button class="btn btn-primary" type="button" data-connection-lost-retry>${esc(t('common.retry'))}</button>
        </div>
    `;
    document.body.appendChild(modal);
    return modal;
};

window.tinycms = window.tinycms || {};
window.tinycms.session = window.tinycms.session || {};
window.tinycms.session.template = {
    ensureConnectionModal,
    ensureSessionModal,
};
})();
