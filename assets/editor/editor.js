(function () {
    function createButton(icon, command, title) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'wysiwyg-btn';
        button.setAttribute('data-command', command);
        button.setAttribute('aria-label', title);
        button.title = title;
        button.innerHTML = '<svg aria-hidden="true"><use href="#' + icon + '"></use></svg>';
        return button;
    }

    function normalizeHtml(html) {
        return html === '<br>' ? '' : html;
    }

    function sync(textarea, editor) {
        textarea.value = normalizeHtml(editor.innerHTML.trim());
    }

    function toggleStates(toolbar) {
        var buttons = toolbar.querySelectorAll('[data-command]');
        buttons.forEach(function (button) {
            var command = button.getAttribute('data-command');
            if (!command || command === 'createLink' || command === 'removeFormat' || command === 'insertUnorderedList' || command === 'insertOrderedList') {
                button.classList.remove('is-active');
                return;
            }
            button.classList.toggle('is-active', document.queryCommandState(command));
        });
    }

    function init(textarea) {
        var wrapper = document.createElement('div');
        wrapper.className = 'wysiwyg';

        var toolbar = document.createElement('div');
        toolbar.className = 'wysiwyg-toolbar';

        var actions = [
            ['w-bold', 'bold', 'Tučně'],
            ['w-italic', 'italic', 'Kurzíva'],
            ['w-link', 'createLink', 'Odkaz'],
            ['w-ul', 'insertUnorderedList', 'Odrážky'],
            ['w-ol', 'insertOrderedList', 'Číslování'],
            ['w-clear', 'removeFormat', 'Vyčistit']
        ];

        actions.forEach(function (item) {
            toolbar.appendChild(createButton(item[0], item[1], item[2]));
        });

        var editor = document.createElement('div');
        editor.className = 'wysiwyg-editor';
        editor.contentEditable = 'true';
        editor.dataset.placeholder = 'Začněte psát obsah…';
        editor.innerHTML = textarea.value.trim();

        toolbar.addEventListener('click', function (event) {
            var button = event.target.closest('[data-command]');
            if (!button) {
                return;
            }
            var command = button.getAttribute('data-command');
            editor.focus();
            if (command === 'createLink') {
                var value = window.prompt('URL odkazu', 'https://');
                if (!value) {
                    return;
                }
                document.execCommand(command, false, value);
            } else {
                document.execCommand(command, false, null);
            }
            sync(textarea, editor);
            toggleStates(toolbar);
        });

        editor.addEventListener('input', function () {
            sync(textarea, editor);
            toggleStates(toolbar);
        });

        editor.addEventListener('keyup', function () {
            toggleStates(toolbar);
        });

        textarea.style.display = 'none';
        textarea.parentNode.insertBefore(wrapper, textarea);
        wrapper.appendChild(toolbar);
        wrapper.appendChild(editor);
        sync(textarea, editor);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var textareas = document.querySelectorAll('textarea[data-wysiwyg]');
        textareas.forEach(init);
    });
})();
