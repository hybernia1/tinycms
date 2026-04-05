(function () {
    function toText(value) {
        return String(value || '').trim();
    }

    function getModal() {
        return document.querySelector('[data-auth-check-modal]');
    }

    function closeModal() {
        var modal = getModal();
        if (modal) {
            modal.classList.remove('open');
        }
    }

    function openModal(message, action) {
        var modal = getModal();
        if (!modal) {
            return;
        }

        var text = modal.querySelector('[data-auth-check-modal-text]');
        var actionButton = modal.querySelector('[data-auth-check-modal-action]');

        if (text) {
            text.textContent = toText(message);
        }

        if (actionButton) {
            if (action && action.href) {
                actionButton.textContent = toText(action.label) || 'Pokračovat';
                actionButton.setAttribute('href', action.href);
                actionButton.hidden = false;
            } else {
                actionButton.hidden = true;
            }
        }

        modal.classList.add('open');
    }

    function initAdminAuthCheck() {
        var body = document.body;
        if (!body || !body.hasAttribute('data-auth-check-endpoint')) {
            return null;
        }

        var endpoint = body.getAttribute('data-auth-check-endpoint') || '';
        var loginUrl = body.getAttribute('data-login-url') || '/login';
        var homeUrl = body.getAttribute('data-home-url') || '/';
        var ttl = 10000;
        var checkedAt = 0;
        var pending = null;

        async function ensureAccess(force) {
            if (!navigator.onLine) {
                openModal('Jste bez připojení k síti. Zkuste to znovu po obnovení připojení.');
                return false;
            }

            if (!force && Date.now() - checkedAt < ttl) {
                return true;
            }

            if (pending) {
                return pending;
            }

            pending = fetch(endpoint, { headers: { Accept: 'application/json' } })
                .then(async function (response) {
                    checkedAt = Date.now();
                    if (response.status === 401) {
                        openModal('Byli jste odhlášeni. Přihlaste se znovu.', { label: 'Přihlásit se', href: loginUrl });
                        return false;
                    }

                    if (response.status === 403) {
                        window.location.href = homeUrl;
                        return false;
                    }

                    if (!response.ok) {
                        return false;
                    }

                    return true;
                })
                .catch(function () {
                    openModal('Jste bez připojení k síti. Zkuste to znovu po obnovení připojení.');
                    return false;
                })
                .finally(function () {
                    pending = null;
                });

            return pending;
        }

        function reportOffline() {
            openModal('Jste bez připojení k síti. Zkuste to znovu po obnovení připojení.');
        }

        window.addEventListener('online', closeModal);
        window.addEventListener('offline', reportOffline);

        document.addEventListener('click', function (event) {
            var closeTrigger = event.target.closest('[data-auth-check-modal-close]');
            if (closeTrigger) {
                closeModal();
            }
        });

        return {
            ensureAccess: ensureAccess,
            reportOffline: reportOffline,
        };
    }

    window.tinycmsAdminAuth = initAdminAuthCheck();
})();
