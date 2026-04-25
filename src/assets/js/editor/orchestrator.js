(function () {
    var app = window.tinycms = window.tinycms || {};
    var editor = app.editor = app.editor || {};
    var currentScript = document.currentScript;
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

    function baseUrl() {
        var source = currentScript ? String(currentScript.src || '') : '';
        return source.replace(/orchestrator\.js(?:\?.*)?$/, '');
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var existing = document.querySelector('script[data-editor-module="' + src + '"]');
            if (existing) {
                resolve();
                return;
            }
            var script = document.createElement('script');
            script.src = src;
            script.defer = true;
            script.setAttribute('data-editor-module', src);
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    function boot() {
        if (typeof editor.initAll === 'function') {
            editor.initAll(document);
        }
    }

    function loadModules() {
        var root = baseUrl();
        return modules.reduce(function (chain, module) {
            return chain.then(function () {
                return loadScript(root + module);
            });
        }, Promise.resolve());
    }

    if (editor.loading) {
        return;
    }
    editor.loading = loadModules().then(boot).catch(function (error) {
        window.setTimeout(function () {
            throw error;
        }, 0);
    });
})();
