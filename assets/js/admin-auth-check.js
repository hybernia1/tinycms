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
        var loginWrap = modal.querySelector('[data-auth-check-login]');

        if (text) {
            text.textContent = toText(message);
        }

        if (loginWrap) {
            loginWrap.hidden = action !== 'login';
        }

        modal.classList.add('open');
    }

    function initAdminAuthCheck() {
        var body = document.body;
        if (!body || !body.hasAttribute('data-auth-check-endpoint')) {
            return null;
        }

        var endpoint = body.getAttribute('data-auth-check-endpoint') || '';
        var loginEndpoint = body.getAttribute('data-auth-login-endpoint') || '';
        var pending = null;
        var loginForm = document.querySelector('[data-auth-check-login]');
        var loginCsrfInput = document.querySelector('[data-auth-check-login] input[name="_csrf"]');

        function syncLoginCsrf(payload) {
            if (!loginCsrfInput) {
                return;
            }

            var token = String(payload?.data?.csrf || '').trim();
            if (token !== '') {
                loginCsrfInput.value = token;
            }
        }

        function syncLoginMessage(payload, fallback) {
            var message = String(payload?.error?.message || '').trim();
            openModal(message !== '' ? message : fallback, 'login');
        }

        async function ensureAccess(force) {
            if (!navigator.onLine) {
                openModal('Jste bez připojení k síti. Zkuste to znovu po obnovení připojení.');
                return false;
            }

            if (pending) {
                return pending;
            }

            pending = fetch(endpoint, { headers: { Accept: 'application/json' } })
                .then(async function (response) {
                    if (response.status === 401) {
                        var payload401 = await response.json().catch(function () { return {}; });
                        syncLoginCsrf(payload401);
                        syncLoginMessage(payload401, 'Byli jste odhlášeni. Přihlaste se znovu.');
                        return false;
                    }

                    if (response.status === 403) {
                        var payload403 = await response.json().catch(function () { return {}; });
                        syncLoginCsrf(payload403);
                        syncLoginMessage(payload403, 'Nemáte dostatečná oprávnění.');
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

        window.addEventListener('online', function () {
            closeModal();
            ensureAccess(true).catch(function () { return null; });
        });
        window.addEventListener('offline', reportOffline);

        document.addEventListener('click', function (event) {
            var closeTrigger = event.target.closest('[data-auth-check-modal-close]');
            if (closeTrigger) {
                closeModal();
            }
        });

        if (loginForm && loginEndpoint !== '') {
            loginForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                if (!navigator.onLine) {
                    reportOffline();
                    return;
                }

                var response = await fetch(loginEndpoint, {
                    method: 'POST',
                    body: new FormData(loginForm),
                    headers: { Accept: 'application/json' },
                }).catch(function () {
                    reportOffline();
                    return null;
                });
                if (!response) {
                    return;
                }

                var payload = await response.json().catch(function () { return {}; });
                syncLoginCsrf(payload);

                if (response.ok && payload?.ok === true) {
                    closeModal();
                    ensureAccess(true).catch(function () { return null; });
                    return;
                }

                syncLoginMessage(payload, 'Přihlášení selhalo.');
            });
        }

        ensureAccess(true).catch(function () { return null; });
        window.setInterval(function () {
            ensureAccess(true).catch(function () { return null; });
        }, 5000);

        return {
            ensureAccess: ensureAccess,
            reportOffline: reportOffline,
        };
    }

    window.tinycmsAdminAuth = initAdminAuthCheck();
})();
