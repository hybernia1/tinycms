(function () {
    var linkState = {
        editor: null,
        textarea: null,
        toolbar: null,
        range: null
    };

    function createButton(icon, command, title, isModalTrigger) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'wysiwyg-btn';
        button.setAttribute('data-command', command);
        button.setAttribute('aria-label', title);
        button.title = title;
        if (isModalTrigger) {
            button.setAttribute('data-modal-open', '');
            button.setAttribute('data-modal-target', '#wysiwyg-link-modal');
        }
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

    function rememberSelection() {
        var selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return null;
        }
        return selection.getRangeAt(0).cloneRange();
    }

    function restoreSelection(range, editor) {
        if (!range) {
            return;
        }
        editor.focus();
        var selection = window.getSelection();
        if (!selection) {
            return;
        }
        selection.removeAllRanges();
        selection.addRange(range);
    }

    function init(textarea) {
        var wrapper = document.createElement('div');
        wrapper.className = 'wysiwyg';

        var toolbar = document.createElement('div');
        toolbar.className = 'wysiwyg-toolbar';

        var actions = [
            ['w-bold', 'bold', 'Tučně', false],
            ['w-italic', 'italic', 'Kurzíva', false],
            ['w-link', 'createLink', 'Odkaz', true],
            ['w-ul', 'insertUnorderedList', 'Odrážky', false],
            ['w-ol', 'insertOrderedList', 'Číslování', false],
            ['w-clear', 'removeFormat', 'Vyčistit', false]
        ];

        actions.forEach(function (item) {
            toolbar.appendChild(createButton(item[0], item[1], item[2], item[3]));
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
                linkState.editor = editor;
                linkState.textarea = textarea;
                linkState.toolbar = toolbar;
                linkState.range = rememberSelection();
                window.setTimeout(function () {
                    var linkInput = document.querySelector('[data-wysiwyg-link-input]');
                    if (linkInput) {
                        linkInput.focus();
                        linkInput.select();
                    }
                }, 0);
                return;
            }

            document.execCommand(command, false, null);
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

    document.addEventListener('click', function (event) {
        var confirm = event.target.closest('[data-wysiwyg-link-confirm]');
        if (!confirm || !linkState.editor || !linkState.textarea || !linkState.toolbar) {
            return;
        }

        var modal = confirm.closest('[data-modal]');
        var input = modal ? modal.querySelector('[data-wysiwyg-link-input]') : null;
        var url = input ? input.value.trim() : '';
        if (!url) {
            return;
        }

        restoreSelection(linkState.range, linkState.editor);
        document.execCommand('createLink', false, url);
        sync(linkState.textarea, linkState.editor);
        toggleStates(linkState.toolbar);

        if (modal) {
            modal.classList.remove('open');
        }
        if (input) {
            input.value = '';
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' || !event.target.matches('[data-wysiwyg-link-input]')) {
            return;
        }
        event.preventDefault();
        var confirm = document.querySelector('[data-wysiwyg-link-confirm]');
        if (confirm) {
            confirm.click();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var textareas = document.querySelectorAll('textarea[data-wysiwyg]');
        textareas.forEach(init);
    });
})();
