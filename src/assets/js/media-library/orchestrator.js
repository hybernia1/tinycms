(() => {
    const app = window.tinycms = window.tinycms || {};
    const mediaLibrary = app.mediaLibrary = app.mediaLibrary || {};
    const currentScript = document.currentScript;
    const loadScripts = app.support?.loadScripts;
    const modules = [
        'template.js',
        'transport.js',
        'renderer.js',
        'helpers.js',
        'modal.js',
    ];

    if (mediaLibrary.loading || typeof loadScripts !== 'function') {
        return;
    }

    mediaLibrary.loading = loadScripts(currentScript, modules, 'data-media-library-module').catch((error) => {
        window.setTimeout(() => {
            throw error;
        }, 0);
    });
})();
