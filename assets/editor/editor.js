(function () {
    var editorCounter = 0;

    function normalizeHtml(html) {
        return html === '<br>' ? '' : html;
    }

    function createImageControls() {
        var controls = document.createElement('div');
        controls.className = 'image-controls';
        controls.setAttribute('contenteditable', 'false');
        controls.innerHTML = '<button type="button" class="btn btn-light btn-xs" data-image-align="left">Vlevo</button>'
            + '<button type="button" class="btn btn-light btn-xs" data-image-align="center">Střed</button>'
            + '<button type="button" class="btn btn-light btn-xs" data-image-align="right">Vpravo</button>';
        return controls;
    }

    function createImageSizeControls() {
        var controls = document.createElement('div');
        controls.className = 'image-size-controls';
        controls.setAttribute('contenteditable', 'false');
        controls.innerHTML = '<button type="button" class="btn btn-light btn-xs" data-image-size="100">100%</button>'
            + '<button type="button" class="btn btn-light btn-xs" data-image-size="75">75%</button>'
            + '<button type="button" class="btn btn-light btn-xs" data-image-size="50">50%</button>'
            + '<button type="button" class="btn btn-light btn-xs" data-image-size="25">25%</button>';
        return controls;
    }

    function createImageResizeHandle() {
        var handle = document.createElement('span');
        handle.className = 'image-resize-handle';
        handle.setAttribute('contenteditable', 'false');
        return handle;
    }

    function createImageSelectionFrame() {
        var frame = document.createElement('span');
        frame.className = 'image-selection-frame';
        frame.setAttribute('contenteditable', 'false');
        ['tl', 'tr', 'bl', 'br'].forEach(function (corner) {
            var node = document.createElement('span');
            node.className = 'image-selection-corner image-selection-corner-' + corner;
            node.setAttribute('contenteditable', 'false');
            frame.appendChild(node);
        });
        return frame;
    }

    function applyImageAlignment(block, align) {
        var value = ['left', 'center', 'right'].indexOf(align) >= 0 ? align : 'center';
        block.classList.remove('align-left', 'align-center', 'align-right');
        block.classList.add('align-' + value);
    }

    function ensureImageBlock(block) {
        if (!block.classList.contains('block-image')) {
            block.classList.add('block-image');
        }
        if (!block.classList.contains('align-left') && !block.classList.contains('align-center') && !block.classList.contains('align-right')) {
            applyImageAlignment(block, 'center');
        }

        if (!block.querySelector('.image-controls')) {
            block.appendChild(createImageControls());
        }

        if (!block.querySelector('.image-resize-handle')) {
            block.appendChild(createImageResizeHandle());
        }

        if (!block.querySelector('.image-size-controls')) {
            block.appendChild(createImageSizeControls());
        }

        if (!block.querySelector('.image-selection-frame')) {
            block.appendChild(createImageSelectionFrame());
        }

        var image = block.querySelector('img[data-media-id]');
        if (image && block.style.width === '' && image.style.width !== '') {
            block.style.width = image.style.width;
            image.style.width = '100%';
        }
    }

    function enhanceImageBlocks(editor) {
        var images = Array.prototype.slice.call(editor.querySelectorAll('img[data-media-id]'));
        images.forEach(function (image) {
            var block = image.closest('.block.block-image');
            if (!block) {
                block = document.createElement('div');
                block.className = 'block block-image align-center';
                var parent = image.parentNode;
                if (parent) {
                    parent.insertBefore(block, image);
                    block.appendChild(image);
                    if ((parent.tagName === 'P' || parent.tagName === 'DIV') && parent.textContent.trim() === '' && parent.querySelectorAll('img').length === 0) {
                        parent.remove();
                    }
                }
            }

            if (block) {
                ensureImageBlock(block);
            }
        });
    }

    function serializeEditorHtml(editor) {
        var clone = editor.cloneNode(true);
        clone.querySelectorAll('.image-controls, .image-size-controls, .image-resize-handle, .image-selection-frame').forEach(function (node) {
            node.remove();
        });
        clone.querySelectorAll('.block.block-image').forEach(function (block) {
            block.classList.remove('is-selected');
        });
        return normalizeHtml(clone.innerHTML.trim());
    }

    function sync(textarea, editor) {
        textarea.value = serializeEditorHtml(editor);
    }

    function createImageBreakParagraph() {
        var paragraph = document.createElement('p');
        paragraph.className = 'block-image-break';
        paragraph.innerHTML = '<br>';
        return paragraph;
    }

    function placeCaret(paragraph) {
        if (!paragraph) {
            return;
        }
        var selection = window.getSelection();
        if (!selection) {
            return;
        }
        var range = document.createRange();
        range.selectNodeContents(paragraph);
        range.collapse(true);
        selection.removeAllRanges();
        selection.addRange(range);
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

            if (node.tagName === 'UL' || node.tagName === 'OL') {
                var listWrapper = document.createElement('div');
                listWrapper.className = 'block block-list';
                editor.replaceChild(listWrapper, node);
                listWrapper.appendChild(node);
                return;
            }

            if (node.tagName === 'DIV') {
                var childList = node.firstElementChild;
                if (node.classList.contains('block') && node.classList.contains('block-list') && childList && (childList.tagName === 'UL' || childList.tagName === 'OL')) {
                    return;
                }

                if (node.classList.contains('block') && node.classList.contains('block-image')) {
                    ensureImageBlock(node);
                    return;
                }

                if (childList && (childList.tagName === 'UL' || childList.tagName === 'OL')) {
                    node.className = 'block block-list';
                    return;
                }

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

    function focusEditorEnd(editor) {
        editor.focus();
        var selection = window.getSelection();
        if (!selection) {
            return;
        }
        var range = document.createRange();
        range.selectNodeContents(editor);
        range.collapse(false);
        selection.removeAllRanges();
        selection.addRange(range);
    }

    function isSelectionInside(editor) {
        var selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return false;
        }
        return editor.contains(selection.anchorNode);
    }

    function getCurrentLink(editor) {
        var selection = window.getSelection();
        if (!selection || selection.rangeCount === 0 || !editor.contains(selection.anchorNode)) {
            return null;
        }
        var source = selection.anchorNode;
        if (source.nodeType === Node.TEXT_NODE) {
            source = source.parentElement;
        }
        return source ? source.closest('a') : null;
    }

    function createIconButton(icon, command, title) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'wysiwyg-btn';
        button.setAttribute('data-command', command);
        button.setAttribute('aria-label', title);
        button.title = title;
        button.innerHTML = '<svg aria-hidden="true"><use href="/assets/icons.svg#icon-' + icon + '"></use></svg>';
        return button;
    }

    function createMenuItem(icon, command, label) {
        var item = document.createElement('button');
        item.type = 'button';
        item.className = 'wysiwyg-menu-item';
        item.setAttribute('data-command', command);
        item.innerHTML = '<svg aria-hidden="true"><use href="/assets/icons.svg#icon-' + icon + '"></use></svg><span>' + label + '</span>';
        return item;
    }

    function createColorSwatchButton(command, value) {
        var item = document.createElement('button');
        item.type = 'button';
        item.className = 'wysiwyg-color-swatch';
        item.setAttribute('data-command', command);
        item.setAttribute('data-value', value);
        item.style.backgroundColor = value;
        item.title = value;
        item.setAttribute('aria-label', value);
        return item;
    }

    function createColorGroup(icon, toggleCommand, swatchCommand, title, type) {
        var group = document.createElement('div');
        group.className = 'wysiwyg-group wysiwyg-group-color';

        var toggle = createIconButton(icon, toggleCommand, title);

        var menu = document.createElement('div');
        menu.className = 'wysiwyg-menu wysiwyg-menu-color';
        menu.setAttribute('data-color-type', type);

        var colors = [
            '#000000', '#434343', '#666666', '#999999',
            '#b7b7b7', '#cccccc', '#d9d9d9', '#efefef',
            '#f3f3f3', '#ffffff', '#980000', '#ff0000',
            '#ff9900', '#ffff00', '#00ff00', '#00ffff',
        ];

        colors.forEach(function (color) {
            menu.appendChild(createColorSwatchButton(swatchCommand, color));
        });

        group.appendChild(toggle);
        group.appendChild(menu);
        return group;
    }

    function createHeadingGroup() {
        var group = document.createElement('div');
        group.className = 'wysiwyg-group wysiwyg-group-heading';

        var toggle = createIconButton('w-heading', 'toggleHeadingMenu', 'Nadpisy');

        var menu = document.createElement('div');
        menu.className = 'wysiwyg-menu wysiwyg-menu-heading';

        menu.appendChild(createMenuItem('w-heading', 'formatBlock:p', 'Odstavec'));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h1', 'Nadpis 1'));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h2', 'Nadpis 2'));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h3', 'Nadpis 3'));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h4', 'Nadpis 4'));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h5', 'Nadpis 5'));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h6', 'Nadpis 6'));
        group.appendChild(toggle);
        group.appendChild(menu);
        return group;
    }

    function createListGroup() {
        var group = document.createElement('div');
        group.className = 'wysiwyg-group wysiwyg-group-list';

        var toggle = createIconButton('w-ul', 'toggleListMenu', 'Seznamy');

        var menu = document.createElement('div');
        menu.className = 'wysiwyg-menu wysiwyg-menu-list';

        menu.appendChild(createMenuItem('w-ul', 'insertUnorderedList', 'Odrážky'));
        menu.appendChild(createMenuItem('w-ol', 'insertOrderedList', 'Číslování'));
        group.appendChild(toggle);
        group.appendChild(menu);
        return group;
    }

    function createAlignGroup() {
        var group = document.createElement('div');
        group.className = 'wysiwyg-group wysiwyg-group-align';

        var toggle = createIconButton('w-align-left', 'toggleAlignMenu', 'Zarovnání');

        var menu = document.createElement('div');
        menu.className = 'wysiwyg-menu wysiwyg-menu-align';

        menu.appendChild(createMenuItem('w-align-left', 'justifyLeft', 'Vlevo'));
        menu.appendChild(createMenuItem('w-align-center', 'justifyCenter', 'Na střed'));
        menu.appendChild(createMenuItem('w-align-right', 'justifyRight', 'Vpravo'));
        menu.appendChild(createMenuItem('w-align-justify', 'justifyFull', 'Do bloku'));
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

        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-light';
        remove.setAttribute('data-role', 'link-remove');
        remove.textContent = 'Odebrat odkaz';

        var confirm = document.createElement('button');
        confirm.type = 'button';
        confirm.className = 'btn btn-primary';
        confirm.setAttribute('data-role', 'link-apply');
        confirm.textContent = 'Vložit';

        actions.appendChild(cancel);
        actions.appendChild(remove);
        actions.appendChild(confirm);
        panel.appendChild(input);
        panel.appendChild(actions);
        return panel;
    }

    function init(textarea) {
        editorCounter += 1;
        var editorId = 'wysiwyg-' + editorCounter;
        var wrapper = document.createElement('div');
        wrapper.className = 'wysiwyg';

        var toolbar = document.createElement('div');
        toolbar.className = 'wysiwyg-toolbar';

        var headingGroup = createHeadingGroup();
        var bold = createIconButton('w-bold', 'bold', 'Tučně');
        var italic = createIconButton('w-italic', 'italic', 'Kurzíva');
        var quote = createIconButton('w-quote', 'formatBlock:blockquote', 'Citace');
        var link = createIconButton('w-link', 'toggleLinkPanel', 'Odkaz');
        var clear = createIconButton('w-clear', 'removeFormat', 'Vyčistit');
        var listGroup = createListGroup();
        var html = createIconButton('w-html', 'toggleHtml', 'HTML');
        var media = createIconButton('w-image', 'openMediaLibrary', 'Vložit obrázek');
        var alignGroup = createAlignGroup();
        var textColorGroup = createColorGroup('w-text-color', 'toggleTextColorMenu', 'foreColor', 'Barva textu', 'text');
        var backgroundColorGroup = createColorGroup('w-bg-color', 'toggleBackgroundColorMenu', 'hiliteColor', 'Barva pozadí', 'background');
        var focus = createIconButton('w-focus', 'toggleFocusMode', 'Nerušené psaní');
        focus.classList.add('wysiwyg-btn-focus');
        var linkPanel = createLinkPanel();

        toolbar.appendChild(headingGroup);
        toolbar.appendChild(bold);
        toolbar.appendChild(italic);
        toolbar.appendChild(quote);
        toolbar.appendChild(link);
        toolbar.appendChild(listGroup);
        toolbar.appendChild(clear);
        if ((textarea.dataset.mediaLibraryEndpoint || '').trim() !== '') {
            toolbar.appendChild(media);
        }
        toolbar.appendChild(html);
        toolbar.appendChild(alignGroup);
        toolbar.appendChild(textColorGroup);
        toolbar.appendChild(backgroundColorGroup);
        toolbar.appendChild(focus);

        var editor = document.createElement('div');
        editor.className = 'wysiwyg-editor';
        editor.contentEditable = 'true';
        editor.dataset.placeholder = 'Začněte psát obsah…';
        editor.innerHTML = textarea.value.trim();

        var linkRange = null;
        var activeLink = null;
        var htmlMode = false;
        var mediaRange = null;

        function setFocusMode(enabled) {
            document.body.classList.toggle('admin-focus-mode', enabled);
            focus.classList.toggle('is-active', enabled);
            focus.setAttribute('aria-pressed', enabled ? 'true' : 'false');
            var label = enabled ? 'Ukončit nerušené psaní' : 'Nerušené psaní';
            focus.title = label;
            focus.setAttribute('aria-label', label);
        }

        setFocusMode(document.body.classList.contains('admin-focus-mode'));

        function closeMenus() {
            wrapper.classList.remove('is-heading-open');
            wrapper.classList.remove('is-list-open');
            wrapper.classList.remove('is-align-open');
            wrapper.classList.remove('is-text-color-open');
            wrapper.classList.remove('is-bg-color-open');
            wrapper.classList.remove('is-link-open');
            activeLink = null;
        }

        function updateFormatState() {
            if (htmlMode || !isSelectionInside(editor)) {
                bold.classList.remove('is-active');
                italic.classList.remove('is-active');
                return;
            }
            bold.classList.toggle('is-active', document.queryCommandState('bold'));
            italic.classList.toggle('is-active', document.queryCommandState('italic'));
        }

        function runCommand(command, value) {
            if (htmlMode) {
                return;
            }
            editor.focus();
            document.execCommand('defaultParagraphSeparator', false, 'p');
            document.execCommand(command, false, value || null);
            normalizeBlocks(editor);
            enhanceImageBlocks(editor);
            sync(textarea, editor);
            closeMenus();
            updateFormatState();
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
            enhanceImageBlocks(editor);
            textarea.style.display = 'none';
            sync(textarea, editor);
        }

        toolbar.addEventListener('mousedown', function (event) {
            if (event.target.closest('[data-command]')) {
                event.preventDefault();
            }
        });

        toolbar.addEventListener('click', function (event) {
            var button = event.target.closest('[data-command]');
            if (!button) {
                return;
            }

            var command = button.getAttribute('data-command');
            if (command === 'toggleFocusMode') {
                setFocusMode(!document.body.classList.contains('admin-focus-mode'));
                return;
            }

            if (command === 'toggleHtml') {
                setHtmlMode(!htmlMode);
                return;
            }

            if (command === 'openMediaLibrary') {
                if (htmlMode) {
                    return;
                }
                mediaRange = isSelectionInside(editor) ? rememberSelection() : null;
                document.dispatchEvent(new CustomEvent('tinycms:media-library-open', {
                    detail: {
                        mode: 'editor',
                        editorId: editorId,
                        contentId: Number(textarea.dataset.contentId || '0'),
                        endpoint: textarea.dataset.mediaLibraryEndpoint || '',
                        baseUrl: textarea.dataset.mediaBaseUrl || '',
                    },
                }));
                return;
            }

            if (command === 'toggleListMenu') {
                if (htmlMode) {
                    return;
                }
                wrapper.classList.remove('is-heading-open');
                wrapper.classList.remove('is-align-open');
                wrapper.classList.remove('is-text-color-open');
                wrapper.classList.remove('is-bg-color-open');
                wrapper.classList.toggle('is-list-open');
                wrapper.classList.remove('is-link-open');
                return;
            }

            if (command === 'toggleHeadingMenu') {
                if (htmlMode) {
                    return;
                }
                wrapper.classList.toggle('is-heading-open');
                wrapper.classList.remove('is-list-open');
                wrapper.classList.remove('is-align-open');
                wrapper.classList.remove('is-text-color-open');
                wrapper.classList.remove('is-bg-color-open');
                wrapper.classList.remove('is-link-open');
                return;
            }

            if (command === 'toggleAlignMenu') {
                if (htmlMode) {
                    return;
                }
                wrapper.classList.remove('is-heading-open');
                wrapper.classList.remove('is-list-open');
                wrapper.classList.toggle('is-align-open');
                wrapper.classList.remove('is-text-color-open');
                wrapper.classList.remove('is-bg-color-open');
                wrapper.classList.remove('is-link-open');
                return;
            }

            if (command === 'toggleTextColorMenu') {
                if (htmlMode) {
                    return;
                }
                wrapper.classList.remove('is-heading-open');
                wrapper.classList.remove('is-list-open');
                wrapper.classList.remove('is-align-open');
                wrapper.classList.toggle('is-text-color-open');
                wrapper.classList.remove('is-bg-color-open');
                wrapper.classList.remove('is-link-open');
                return;
            }

            if (command === 'toggleBackgroundColorMenu') {
                if (htmlMode) {
                    return;
                }
                wrapper.classList.remove('is-heading-open');
                wrapper.classList.remove('is-list-open');
                wrapper.classList.remove('is-align-open');
                wrapper.classList.remove('is-text-color-open');
                wrapper.classList.toggle('is-bg-color-open');
                wrapper.classList.remove('is-link-open');
                return;
            }

            if (command === 'toggleLinkPanel') {
                if (htmlMode) {
                    return;
                }
                if (isSelectionInside(editor)) {
                    linkRange = rememberSelection();
                    activeLink = getCurrentLink(editor);
                }
                wrapper.classList.toggle('is-link-open');
                wrapper.classList.remove('is-list-open');
                if (wrapper.classList.contains('is-link-open')) {
                    var linkInput = linkPanel.querySelector('[data-role="link-input"]');
                    if (linkInput) {
                        linkInput.value = activeLink ? (activeLink.getAttribute('href') || '') : '';
                        linkInput.focus();
                        linkInput.select();
                    }
                }
                return;
            }

            if (command.indexOf('formatBlock:') === 0) {
                runCommand('formatBlock', '<' + command.split(':')[1] + '>');
                return;
            }

            if (command === 'foreColor' || command === 'hiliteColor') {
                document.execCommand('styleWithCSS', false, true);
                runCommand(command, button.getAttribute('data-value') || '#000000');
                return;
            }

            runCommand(command);
        });

        linkPanel.addEventListener('click', function (event) {
            var linkInput = linkPanel.querySelector('[data-role="link-input"]');

            if (event.target.closest('[data-role="link-remove"]')) {
                if (activeLink && editor.contains(activeLink)) {
                    var parent = activeLink.parentNode;
                    while (activeLink.firstChild) {
                        parent.insertBefore(activeLink.firstChild, activeLink);
                    }
                    parent.removeChild(activeLink);
                } else if (linkRange) {
                    restoreSelection(linkRange, editor);
                    document.execCommand('unlink', false, null);
                }
                normalizeBlocks(editor);
                sync(textarea, editor);
                updateFormatState();
                if (linkInput) {
                    linkInput.value = '';
                }
                activeLink = null;
                closeMenus();
                return;
            }

            var apply = event.target.closest('[data-role="link-apply"]');
            if (apply) {
                var url = linkInput ? linkInput.value.trim() : '';
                if (url) {
                    if (activeLink && editor.contains(activeLink) && (!linkRange || linkRange.collapsed)) {
                        activeLink.setAttribute('href', url);
                    } else {
                        restoreSelection(linkRange, editor);
                        document.execCommand('defaultParagraphSeparator', false, 'p');
                        document.execCommand('createLink', false, url);
                    }
                    normalizeBlocks(editor);
                    sync(textarea, editor);
                    updateFormatState();
                    if (linkInput) {
                        linkInput.value = '';
                    }
                    activeLink = null;
                }
                closeMenus();
                return;
            }

            if (event.target.closest('[data-role="link-cancel"]')) {
                if (linkInput) {
                    linkInput.value = '';
                }
                activeLink = null;
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
                updateFormatState();
            }
        });

        document.addEventListener('selectionchange', function () {
            if (isSelectionInside(editor)) {
                updateFormatState();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && document.body.classList.contains('admin-focus-mode')) {
                setFocusMode(false);
            }
        });

        var resizingState = null;
        function stopResize() {
            resizingState = null;
            document.body.classList.remove('is-image-resizing');
        }

        editor.addEventListener('mousedown', function (event) {
            var handle = event.target.closest('.image-resize-handle');
            if (!handle) {
                return;
            }
            var block = handle.closest('.block.block-image');
            var image = block ? block.querySelector('img[data-media-id]') : null;
            if (!block || !image) {
                return;
            }
            event.preventDefault();
            resizingState = {
                block: block,
                image: image,
                editorWidth: Math.max(1, editor.clientWidth),
                startX: event.clientX,
                startWidth: image.getBoundingClientRect().width,
            };
            document.body.classList.add('is-image-resizing');
        });

        document.addEventListener('mousemove', function (event) {
            if (!resizingState) {
                return;
            }
            var width = Math.max(120, resizingState.startWidth + (event.clientX - resizingState.startX));
            var percent = Math.min(100, Math.max(15, (width / resizingState.editorWidth) * 100));
            resizingState.block.style.width = percent.toFixed(2).replace(/\.00$/, '') + '%';
            resizingState.image.style.width = '100%';
            sync(textarea, editor);
        });

        document.addEventListener('mouseup', function () {
            stopResize();
        });

        editor.addEventListener('click', function (event) {
            var alignButton = event.target.closest('[data-image-align]');
            if (alignButton) {
                event.preventDefault();
                var block = alignButton.closest('.block.block-image');
                if (!block) {
                    return;
                }
                applyImageAlignment(block, alignButton.getAttribute('data-image-align') || 'center');
                sync(textarea, editor);
                return;
            }

            var sizeButton = event.target.closest('[data-image-size]');
            if (sizeButton) {
                event.preventDefault();
                var sizeBlock = sizeButton.closest('.block.block-image');
                if (!sizeBlock) {
                    return;
                }
                var size = Number(sizeButton.getAttribute('data-image-size') || '100');
                var value = Math.max(25, Math.min(100, size));
                sizeBlock.style.width = value + '%';
                var sizeImage = sizeBlock.querySelector('img[data-media-id]');
                if (sizeImage) {
                    sizeImage.style.width = '100%';
                }
                sync(textarea, editor);
                return;
            }

            var blockImage = event.target.closest('.block.block-image');
            editor.querySelectorAll('.block.block-image.is-selected').forEach(function (node) {
                node.classList.remove('is-selected');
            });
            if (blockImage) {
                blockImage.classList.add('is-selected');
            }
        });

        document.addEventListener('tinycms:media-library-selected', function (event) {
            var detail = event.detail || {};
            if (detail.editorId !== editorId || !detail.url) {
                return;
            }
            var mediaId = Number(detail.id || 0);
            if (mediaId <= 0) {
                return;
            }
            if (mediaRange) {
                restoreSelection(mediaRange, editor);
            } else {
                focusEditorEnd(editor);
            }
            document.execCommand('insertHTML', false, '<div class="block block-image align-center"><img src="' + String(detail.url).replace(/"/g, '&quot;') + '" alt="' + String(detail.name || '').replace(/"/g, '&quot;') + '" data-media-id="' + mediaId + '"></div><p><br></p>');
            normalizeBlocks(editor);
            enhanceImageBlocks(editor);
            sync(textarea, editor);
            updateFormatState();
        });

        editor.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                var selectedImageBlock = editor.querySelector('.block.block-image.is-selected');
                if (selectedImageBlock) {
                    event.preventDefault();
                    var next = selectedImageBlock.nextElementSibling;
                    var target = next && next.classList.contains('block-image-break') ? next : createImageBreakParagraph();
                    if (target !== next) {
                        selectedImageBlock.parentNode.insertBefore(target, selectedImageBlock.nextSibling);
                    }
                    placeCaret(target);
                    sync(textarea, editor);
                    return;
                }
            }

            if (event.key === 'Enter' && !event.shiftKey) {
                var wasBold = document.queryCommandState('bold');
                var wasItalic = document.queryCommandState('italic');
                document.execCommand('defaultParagraphSeparator', false, 'p');
                event.preventDefault();
                document.execCommand('insertParagraph', false, null);
                if (wasBold) {
                    document.execCommand('bold', false, null);
                }
                if (wasItalic) {
                    document.execCommand('italic', false, null);
                }
                updateFormatState();
            }
        });

        editor.addEventListener('input', function () {
            sync(textarea, editor);
            updateFormatState();
        });

        editor.addEventListener('blur', function () {
            if (htmlMode) {
                return;
            }
            normalizeBlocks(editor);
            enhanceImageBlocks(editor);
            sync(textarea, editor);
            updateFormatState();
        });

        textarea.style.display = 'none';
        textarea.parentNode.insertBefore(wrapper, textarea);
        wrapper.appendChild(toolbar);
        wrapper.appendChild(linkPanel);
        wrapper.appendChild(editor);
        wrapper.appendChild(textarea);
        var form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                if (htmlMode) {
                    return;
                }
                normalizeBlocks(editor);
                enhanceImageBlocks(editor);
                sync(textarea, editor);
            });
        }

        document.execCommand('defaultParagraphSeparator', false, 'p');
        normalizeBlocks(editor);
        enhanceImageBlocks(editor);
        sync(textarea, editor);
        updateFormatState();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var textareas = document.querySelectorAll('textarea[data-wysiwyg]');
        textareas.forEach(init);
    });
})();
