(() => {
    const app = window.tinycms = window.tinycms || {};
    const adminUi = app.adminUi = app.adminUi || {};
    const loadScripts = app.support?.loadScripts;
    const currentScript = document.currentScript;
    const modules = [
        'admin-menu.js',
        'custom-select.js',
        'custom-datetime.js',
        'password-toggle.js',
        'custom-upload.js',
    ];

    if (adminUi.loading || typeof loadScripts !== 'function') {
        return;
    }

    adminUi.loading = loadScripts(currentScript, modules, 'data-admin-ui-module').catch((error) => {
        window.setTimeout(() => {
            throw error;
        }, 0);
    });
})();
