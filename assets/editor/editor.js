(function () {
    function normalizeHtml(html) {
        return html === '<br>' ? '' : html;
    }

    function sync(textarea, editor) {
        textarea.value = normalizeHtml(editor.innerHTML.trim());
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

    function createIconButton(icon, command, title) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'wysiwyg-btn';
        button.setAttribute('data-command', command);
        button.setAttribute('aria-label', title);
        button.title = title;
        button.innerHTML = '<svg aria-hidden="true"><use href="#' + icon + '"></use></svg>';
        return button;
    }

    function createListGroup() {
        var group = document.createElement('div');
        group.className = 'wysiwyg-group';

        var toggle = createIconButton('w-ul', 'toggleListMenu', 'Seznamy');
        toggle.setAttribute('data-role', 'list-toggle');

        var menu = document.createElement('div');
        menu.className = 'wysiwyg-menu';
        menu.setAttribute('data-role', 'list-menu');

        var bullets = document.createElement('button');
        bullets.type = 'button';
        bullets.className = 'wysiwyg-menu-item';
        bullets.setAttribute('data-command', 'insertUnorderedList');
        bullets.textContent = 'Odrážky';

        var numbers = document.createElement('button');
        numbers.type = 'button';
        numbers.className = 'wysiwyg-menu-item';
        numbers.setAttribute('data-command', 'insertOrderedList');
        numbers.textContent = 'Číslování';

        menu.appendChild(bullets);
        menu.appendChild(numbers);
        group.appendChild(toggle);
        group.appendChild(menu);
        return group;
    }

    function createLinkPanel() {
        var panel = document.createElement('div');
        panel.className = 'wysiwyg-link-panel';

        var input = document.createElement('input');
        input.type = 'url';
        input.placeholder = 'https://';
        input.className = 'wysiwyg-link-input';
        input.setAttribute('data-role', 'link-input');

        var actions = document.createElement('div');
        actions.className = 'wysiwyg-link-actions';

        var cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'btn btn-light';
        cancel.setAttribute('data-role', 'link-cancel');
        cancel.textContent = 'Zrušit';

        var confirm = document.createElement('button');
        confirm.type = 'button';
        confirm.className = 'btn btn-primary';
        confirm.setAttribute('data-role', 'link-apply');
        confirm.textContent = 'Vložit';

        actions.appendChild(cancel);
        actions.appendChild(confirm);
        panel.appendChild(input);
        panel.appendChild(actions);
        return panel;
    }

    function init(textarea) {
        var wrapper = document.createElement('div');
        wrapper.className = 'wysiwyg';

        var toolbar = document.createElement('div');
        toolbar.className = 'wysiwyg-toolbar';

        var bold = createIconButton('w-bold', 'bold', 'Tučně');
        var italic = createIconButton('w-italic', 'italic', 'Kurzíva');
        var link = createIconButton('w-link', 'toggleLinkPanel', 'Odkaz');
        var clear = createIconButton('w-clear', 'removeFormat', 'Vyčistit');
        var listGroup = createListGroup();
        var linkPanel = createLinkPanel();

        toolbar.appendChild(bold);
        toolbar.appendChild(italic);
        toolbar.appendChild(link);
        toolbar.appendChild(listGroup);
        toolbar.appendChild(clear);

        var editor = document.createElement('div');
        editor.className = 'wysiwyg-editor';
        editor.contentEditable = 'true';
        editor.dataset.placeholder = 'Začněte psát obsah…';
        editor.innerHTML = textarea.value.trim();

        var linkRange = null;

        function closeMenus() {
            wrapper.classList.remove('is-list-open');
            wrapper.classList.remove('is-link-open');
        }

        function runCommand(command) {
            editor.focus();
            document.execCommand(command, false, null);
            sync(textarea, editor);
            closeMenus();
        }

        toolbar.addEventListener('click', function (event) {
            var button = event.target.closest('[data-command]');
            if (!button) {
                return;
            }

            var command = button.getAttribute('data-command');
            if (command === 'toggleListMenu') {
                wrapper.classList.toggle('is-list-open');
                wrapper.classList.remove('is-link-open');
                return;
            }

            if (command === 'toggleLinkPanel') {
                linkRange = rememberSelection();
                wrapper.classList.toggle('is-link-open');
                wrapper.classList.remove('is-list-open');
                if (wrapper.classList.contains('is-link-open')) {
                    var linkInput = linkPanel.querySelector('[data-role="link-input"]');
                    if (linkInput) {
                        linkInput.focus();
                        linkInput.select();
                    }
                }
                return;
            }

            runCommand(command);
        });

        linkPanel.addEventListener('click', function (event) {
            var apply = event.target.closest('[data-role="link-apply"]');
            if (apply) {
                var linkInput = linkPanel.querySelector('[data-role="link-input"]');
                var url = linkInput ? linkInput.value.trim() : '';
                if (url) {
                    restoreSelection(linkRange, editor);
                    document.execCommand('createLink', false, url);
                    sync(textarea, editor);
                    if (linkInput) {
                        linkInput.value = '';
                    }
                }
                closeMenus();
                return;
            }

            if (event.target.closest('[data-role="link-cancel"]')) {
                closeMenus();
            }
        });

        linkPanel.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                var apply = linkPanel.querySelector('[data-role="link-apply"]');
                if (apply) {
                    apply.click();
                }
            }
        });

        document.addEventListener('click', function (event) {
            if (!wrapper.contains(event.target)) {
                closeMenus();
            }
        });

        editor.addEventListener('input', function () {
            sync(textarea, editor);
        });

        textarea.style.display = 'none';
        textarea.parentNode.insertBefore(wrapper, textarea);
        wrapper.appendChild(toolbar);
        wrapper.appendChild(linkPanel);
        wrapper.appendChild(editor);
        sync(textarea, editor);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var textareas = document.querySelectorAll('textarea[data-wysiwyg]');
        textareas.forEach(init);
    });
})();
