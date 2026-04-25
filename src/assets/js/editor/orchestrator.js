(function () {
    var app = window.tinycms = window.tinycms || {};
    var editor = app.editor = app.editor || {};
    var currentScript = document.currentScript;
    var loadScripts = app.support && app.support.loadScripts;
    var modules = [
        'sanitize.js',
        'selection.js',
        'toolbar.js',
        'blocks.js',
        'link-modal.js',
        'links.js',
        'media.js',
        'runtime.js',
    ];

    function boot() {
        if (typeof editor.initAll === 'function') {
            editor.initAll(document);
        }
    }

    if (editor.loading || typeof loadScripts !== 'function') {
        return;
    }
    editor.loading = loadScripts(currentScript, modules, 'data-editor-module').then(boot).catch(function (error) {
        window.setTimeout(function () {
            throw error;
        }, 0);
    });
})();
