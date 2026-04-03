(function () {
    function normalizeHtml(html) {
        return html === '<br>' ? '' : html;
    }

    function sync(textarea, editor) {
        textarea.value = normalizeHtml(editor.innerHTML.trim());
    }

    function normalizeBlocks(editor) {
        var nodes = Array.prototype.slice.call(editor.childNodes);
        nodes.forEach(function (node) {
            if (node.nodeType === Node.TEXT_NODE && node.textContent && node.textContent.trim() !== '') {
                var paragraph = document.createElement('p');
                paragraph.textContent = node.textContent;
                editor.replaceChild(paragraph, node);
                return;
            }

            if (node.nodeType !== Node.ELEMENT_NODE) {
                return;
            }

            if (node.tagName === 'DIV') {
                var p = document.createElement('p');
                p.innerHTML = node.innerHTML;
                editor.replaceChild(p, node);
            }
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

    function createMenuItem(icon, command, label) {
        var item = document.createElement('button');
        item.type = 'button';
        item.className = 'wysiwyg-menu-item';
        item.setAttribute('data-command', command);
        item.innerHTML = '<svg aria-hidden="true"><use href="#' + icon + '"></use></svg><span>' + label + '</span>';
        return item;
    }

    function createListGroup() {
        var group = document.createElement('div');
        group.className = 'wysiwyg-group';

        var toggle = createIconButton('w-ul', 'toggleListMenu', 'Seznamy');

        var menu = document.createElement('div');
        menu.className = 'wysiwyg-menu';

        menu.appendChild(createMenuItem('w-ul', 'insertUnorderedList', 'Odrážky'));
        menu.appendChild(createMenuItem('w-ol', 'insertOrderedList', 'Číslování'));
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
        var html = createIconButton('w-html', 'toggleHtml', 'HTML');
        var linkPanel = createLinkPanel();

        toolbar.appendChild(bold);
        toolbar.appendChild(italic);
        toolbar.appendChild(link);
        toolbar.appendChild(listGroup);
        toolbar.appendChild(clear);
        toolbar.appendChild(html);

        var editor = document.createElement('div');
        editor.className = 'wysiwyg-editor';
        editor.contentEditable = 'true';
        editor.dataset.placeholder = 'Začněte psát obsah…';
        editor.innerHTML = textarea.value.trim();

        var linkRange = null;
        var htmlMode = false;

        function closeMenus() {
            wrapper.classList.remove('is-list-open');
            wrapper.classList.remove('is-link-open');
        }

        function runCommand(command) {
            if (htmlMode) {
                return;
            }
            editor.focus();
            document.execCommand('defaultParagraphSeparator', false, 'p');
            document.execCommand(command, false, null);
            normalizeBlocks(editor);
            sync(textarea, editor);
            closeMenus();
        }

        function setHtmlMode(enabled) {
            htmlMode = enabled;
            wrapper.classList.toggle('is-html-mode', enabled);
            html.classList.toggle('is-active', enabled);
            closeMenus();
            if (enabled) {
                sync(textarea, editor);
                textarea.style.display = 'block';
                return;
            }
            editor.innerHTML = textarea.value.trim();
            textarea.style.display = 'none';
            sync(textarea, editor);
        }

        toolbar.addEventListener('click', function (event) {
            var button = event.target.closest('[data-command]');
            if (!button) {
                return;
            }

            var command = button.getAttribute('data-command');
            if (command === 'toggleHtml') {
                setHtmlMode(!htmlMode);
                return;
            }

            if (command === 'toggleListMenu') {
                if (htmlMode) {
                    return;
                }
                wrapper.classList.toggle('is-list-open');
                wrapper.classList.remove('is-link-open');
                return;
            }

            if (command === 'toggleLinkPanel') {
                if (htmlMode) {
                    return;
                }
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
                    document.execCommand('defaultParagraphSeparator', false, 'p');
                    document.execCommand('createLink', false, url);
                    normalizeBlocks(editor);
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

        editor.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                document.execCommand('defaultParagraphSeparator', false, 'p');
                event.preventDefault();
                document.execCommand('insertParagraph', false, null);
            }
        });

        editor.addEventListener('input', function () {
            normalizeBlocks(editor);
            sync(textarea, editor);
        });

        textarea.style.display = 'none';
        textarea.parentNode.insertBefore(wrapper, textarea);
        wrapper.appendChild(toolbar);
        wrapper.appendChild(linkPanel);
        wrapper.appendChild(editor);
        wrapper.appendChild(textarea);
        document.execCommand('defaultParagraphSeparator', false, 'p');
        normalizeBlocks(editor);
        sync(textarea, editor);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var textareas = document.querySelectorAll('textarea[data-wysiwyg]');
        textareas.forEach(init);
    });
})();
