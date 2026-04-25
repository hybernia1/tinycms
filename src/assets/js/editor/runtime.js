(function () {
    var app = window.tinycms = window.tinycms || {};
    var t = app.i18n?.t || function () { return ""; };
    var modalUi = app.ui?.modal || {
        open: function (modal) { if (modal) { modal.classList.add('open'); } },
        close: function (modal) { if (modal) { modal.classList.remove('open'); } }
    };
    var requestJson = app.api?.http?.requestJson;
    var postForm = app.api?.http?.postForm;
    var editorModules = app.editor = app.editor || {};
    var sanitize = editorModules.sanitize || {};
    var blocks = editorModules.blocks || {};
    var selection = editorModules.selection || {};
    var toolbarModule = editorModules.toolbar || {};
    var editorCounter = 0;

    var cleanSerializedHtml = sanitize.cleanSerializedHtml;
    var escapeHtml = sanitize.escapeHtml;
    var isEmptyTextBlock = sanitize.isEmptyTextBlock;
    var normalizeLinkUrl = sanitize.normalizeLinkUrl;

    var applyEmbedAlignment = blocks.applyEmbedAlignment;
    var applyImageAlignment = blocks.applyImageAlignment;
    var createImageBreakParagraph = blocks.createImageBreakParagraph;
    var enhanceEmbedBlocks = blocks.enhanceEmbedBlocks;
    var enhanceImageBlocks = blocks.enhanceImageBlocks;
    var ensureImageBlock = blocks.ensureImageBlock;
    var normalizeBlocks = blocks.normalizeBlocks;
    var placeEmbedToolbar = blocks.placeEmbedToolbar;
    var placeImageToolbar = blocks.placeImageToolbar;
    var sync = blocks.sync;
    var syncEmbedToolbarState = blocks.syncEmbedToolbarState;
    var syncImageToolbarState = blocks.syncImageToolbarState;

    var eventElement = selection.eventElement;
    var focusEditorEnd = selection.focusEditorEnd;
    var getCurrentLink = selection.getCurrentLink;
    var getSelectionContainer = selection.getSelectionContainer;
    var isSelectionInside = selection.isSelectionInside;
    var isSelectionInsideHeading = selection.isSelectionInsideHeading;
    var isSelectionInsideTag = selection.isSelectionInsideTag;
    var placeCaret = selection.placeCaret;
    var rememberSelection = selection.rememberSelection;
    var restoreSelection = selection.restoreSelection;

    var createAlignGroup = toolbarModule.createAlignGroup;
    var createHeadingGroup = toolbarModule.createHeadingGroup;
    var createIconButton = toolbarModule.createIconButton;
    var createLinkToolButton = toolbarModule.createLinkToolButton;
    var createListGroup = toolbarModule.createListGroup;
    function init(textarea) {
        if (!textarea || textarea.dataset.wysiwygReady === '1') {
            return;
        }
        textarea.dataset.wysiwygReady = '1';
        editorCounter += 1;
        var editorId = 'wysiwyg-' + editorCounter;
        var wrapper = document.createElement('div');
        wrapper.className = 'wysiwyg';

        var toolbar = document.createElement('div');
        toolbar.className = 'wysiwyg-toolbar';

        var headingGroup = createHeadingGroup();
        var bold = createIconButton('w-bold', 'bold', ''+ t('editor.bold') + '');
        var italic = createIconButton('w-italic', 'italic', ''+ t('editor.italic') + '');
        var quote = createIconButton('w-quote', 'formatBlock:blockquote', t('editor.quote'));
        var link = createIconButton('w-link', 'toggleLinkPanel', t('editor.insert_link'));
        var listGroup = createListGroup();
        var html = createIconButton('w-html', 'toggleHtml', 'HTML');
        var media = createIconButton('w-image', 'openMediaLibrary', ''+ t('editor.insert_image') + '');
        var pagebreak = createIconButton('w-pagebreak', 'insertPagebreak', ''+ t('editor.page_break') + '');
        var alignGroup = createAlignGroup();
        var focus = createIconButton('w-focus', 'toggleFocusMode', ''+ t('editor.focus_mode') + '');
        focus.classList.add('wysiwyg-btn-focus');

        toolbar.appendChild(headingGroup);
        toolbar.appendChild(bold);
        toolbar.appendChild(italic);
        toolbar.appendChild(quote);
        toolbar.appendChild(link);
        toolbar.appendChild(listGroup);
        if ((textarea.dataset.mediaLibraryEndpoint || '').trim() !== '') {
            toolbar.appendChild(media);
        }
        toolbar.appendChild(pagebreak);
        toolbar.appendChild(html);
        toolbar.appendChild(alignGroup);
        toolbar.appendChild(focus);

        var editor = document.createElement('div');
        editor.className = 'wysiwyg-editor';
        editor.contentEditable = 'true';
        editor.innerHTML = textarea.value.trim();

        var htmlMode = false;
        var blockInsertSeq = 0;
        var toggleMenuByCommand = {
            toggleListMenu: 'is-list-open',
            toggleHeadingMenu: 'is-heading-open',
            toggleAlignMenu: 'is-align-open'
        };

        var linkController = editorModules.links.create({
            closeMenus: closeMenus,
            createLinkToolButton: createLinkToolButton,
            editor: editor,
            editorId: editorId,
            ensureSelectionInsideEditor: ensureSelectionInsideEditor,
            escapeHtml: escapeHtml,
            eventElement: eventElement,
            getCurrentLink: getCurrentLink,
            htmlMode: function () { return htmlMode; },
            isSelectionInside: isSelectionInside,
            modalUi: modalUi,
            normalizeLinkUrl: normalizeLinkUrl,
            persistEditorState: persistEditorState,
            rememberSelection: rememberSelection,
            requestJson: requestJson,
            restoreSelection: restoreSelection,
            sync: sync,
            textarea: textarea,
            wrapper: wrapper,
        });
        var linkModal = linkController.linkModal;
        var linkTools = linkController.linkTools;

        var mediaController = editorModules.media.create({
            blockInsertAttr: blockInsertAttr,
            blockInsertId: blockInsertId,
            cleanSerializedHtml: cleanSerializedHtml,
            createImageBreakParagraph: createImageBreakParagraph,
            createLoadingImageBlock: blocks.createLoadingImageBlock,
            csrfValue: csrfValue,
            editor: editor,
            editorForm: editorForm,
            editorId: editorId,
            ensureImageBlock: ensureImageBlock,
            ensureSelectionInsideEditor: ensureSelectionInsideEditor,
            escapeHtml: escapeHtml,
            extractPastedUrl: sanitize.extractPastedUrl,
            extractYoutubeVideoId: sanitize.extractYoutubeVideoId,
            findPastedImageFile: sanitize.findPastedImageFile,
            focusEditorEnd: focusEditorEnd,
            htmlMode: function () { return htmlMode; },
            insertPastedLink: linkController.insertPastedLink,
            insertStandaloneHtml: insertStandaloneHtml,
            isSelectionInside: isSelectionInside,
            persistEditorState: persistEditorState,
            placeCaretAfterBlock: placeCaretAfterBlock,
            postForm: postForm,
            rememberSelection: rememberSelection,
            requestJson: requestJson,
            restoreSelection: restoreSelection,
            selectImageBlock: selectImageBlock,
            sync: sync,
            textarea: textarea,
        });

        function editorForm() {
            return textarea.closest('form');
        }

        function csrfValue() {
            var input = editorForm()?.querySelector('input[name="_csrf"]');
            return input ? input.value || '' : '';
        }

        function blockInsertId(type) {
            blockInsertSeq += 1;
            return type + '-' + editorId + '-' + blockInsertSeq;
        }

        function blockInsertAttr(insertId) {
            return ' data-tinycms-insert-id="' + escapeHtml(insertId) + '"';
        }

        function setFocusMode(enabled) {
            document.body.classList.toggle('admin-focus-mode', enabled);
            focus.classList.toggle('is-active', enabled);
            focus.setAttribute('aria-pressed', enabled ? 'true' : 'false');
            var label = enabled ? '' + t('editor.focus_mode_exit') + '' : ''+ t('editor.focus_mode') + '';
            focus.title = label;
            focus.setAttribute('aria-label', label);
        }

        setFocusMode(document.body.classList.contains('admin-focus-mode'));

        function toggleMenu(menuClass) {
            ['is-heading-open', 'is-list-open', 'is-align-open'].forEach(function (className) {
                if (className === menuClass) {
                    wrapper.classList.toggle(className);
                    return;
                }
                wrapper.classList.remove(className);
            });
            linkController.closeAll();
        }

        function closeMenus() {
            wrapper.classList.remove('is-heading-open');
            wrapper.classList.remove('is-list-open');
            wrapper.classList.remove('is-align-open');
            linkController.closeAll();
        }

        function persistEditorState(withFormatState) {
            normalizeBlocks(editor);
            enhanceImageBlocks(editor);
            enhanceEmbedBlocks(editor);
            sync(textarea, editor);
            if (withFormatState) {
                updateFormatState();
            }
        }

        function removeBlock(block, removeImageBreak) {
            if (!block || !block.parentNode) {
                return null;
            }
            var nextNode = block.nextElementSibling;
            var prevNode = block.previousElementSibling;
            if (removeImageBreak) {
                if (nextNode && nextNode.classList.contains('block-image-break')) {
                    nextNode.remove();
                    nextNode = block.nextElementSibling;
                } else if (prevNode && prevNode.classList.contains('block-image-break')) {
                    prevNode.remove();
                    prevNode = block.previousElementSibling;
                }
            }
            block.remove();

            var caretTarget = null;
            if (nextNode && nextNode.parentNode === editor) {
                caretTarget = nextNode;
            } else if (prevNode && prevNode.parentNode === editor) {
                caretTarget = prevNode;
            } else {
                caretTarget = createImageBreakParagraph();
                editor.appendChild(caretTarget);
            }
            if (caretTarget.classList && caretTarget.classList.contains('block') && (caretTarget.classList.contains('block-image') || caretTarget.classList.contains('block-embed'))) {
                var spacer = createImageBreakParagraph();
                editor.insertBefore(spacer, caretTarget);
                caretTarget = spacer;
            }
            return caretTarget;
        }

        function removeImageBlock(block) {
            return removeBlock(block, true);
        }

        function removeEmbedBlock(block) {
            return removeBlock(block, false);
        }

        function clearSelectedBlocks() {
            editor.querySelectorAll('.block.block-image.is-selected').forEach(function (node) {
                node.classList.remove('is-selected');
                node.classList.remove('image-toolbar-below');
            });
            editor.querySelectorAll('.block.block-embed.is-selected').forEach(function (node) {
                node.classList.remove('is-selected');
                node.classList.remove('embed-toolbar-below');
            });
        }

        function selectImageBlock(block) {
            clearSelectedBlocks();
            if (block) {
                block.classList.add('is-selected');
                placeImageToolbar(block, editor);
            }
        }

        function selectEmbedBlock(block) {
            clearSelectedBlocks();
            if (block) {
                block.classList.add('is-selected');
                placeEmbedToolbar(block, editor);
            }
        }

        function selectedImageBlock() {
            return editor.querySelector('.block.block-image.is-selected');
        }

        function selectedEmbedBlock() {
            return editor.querySelector('.block.block-embed.is-selected');
        }

        function syncSelectedToolbars() {
            placeImageToolbar(selectedImageBlock(), editor);
            placeEmbedToolbar(selectedEmbedBlock(), editor);
        }

        function setImageBlockWidth(block, width) {
            block.style.width = width;
            var image = block.querySelector('img[data-media-id]');
            if (image) {
                image.style.width = width === '' ? '' : '100%';
            }
            syncImageToolbarState(block);
            placeImageToolbar(block, editor);
        }

        function setEmbedBlockWidth(block, width) {
            block.style.width = width;
            syncEmbedToolbarState(block);
            placeEmbedToolbar(block, editor);
        }

        function placeCaretAfterBlock(block) {
            if (!block || !block.parentNode) {
                return;
            }
            var next = block.nextElementSibling;
            var target = next && next.tagName === 'P' ? next : createImageBreakParagraph();
            if (target !== next) {
                block.parentNode.insertBefore(target, block.nextSibling);
            }
            if (target.classList.contains('block-image-break')) {
                target.classList.remove('block-image-break');
            }
            placeCaret(target);
        }

        function insertStandaloneHtml(html, insertId) {
            ensureSelectionInsideEditor();
            document.execCommand('defaultParagraphSeparator', false, 'p');
            document.execCommand('insertHTML', false, html);
            var block = editor.querySelector('[data-tinycms-insert-id="' + insertId + '"]');
            if (block) {
                block.removeAttribute('data-tinycms-insert-id');
                placeCaretAfterBlock(block);
            }
            persistEditorState(true);
        }

        function toggleBlockFullWidth(block, setWidth, applyAlign) {
            if (!block) {
                return;
            }
            if (String(block.style.width || '').trim() === '100%') {
                setWidth(block, '');
                return;
            }
            if (typeof applyAlign === 'function') {
                applyAlign(block, 'center');
            }
            setWidth(block, '100%');
        }

        function deleteBlock(block, remove) {
            var caretTarget = remove(block);
            placeCaret(caretTarget);
            persistEditorState(false);
        }

        function deleteSelectedBlock() {
            var imageBlock = selectedImageBlock();
            if (imageBlock) {
                deleteBlock(imageBlock, removeImageBlock);
                return true;
            }

            var embedBlock = selectedEmbedBlock();
            if (!embedBlock) {
                return false;
            }
            deleteBlock(embedBlock, removeEmbedBlock);
            return true;
        }

        function handleEmbedToolbarClick(event) {
            var alignButton = event.target.closest('[data-embed-align]');
            if (alignButton) {
                event.preventDefault();
                var alignBlock = alignButton.closest('.block.block-embed');
                if (!alignBlock) {
                    return true;
                }
                applyEmbedAlignment(alignBlock, alignButton.getAttribute('data-embed-align') || 'center');
                sync(textarea, editor);
                return true;
            }

            var actionButton = event.target.closest('[data-embed-action]');
            if (!actionButton) {
                return false;
            }
            event.preventDefault();
            var block = actionButton.closest('.block.block-embed');
            if (!block) {
                return true;
            }
            var action = actionButton.getAttribute('data-embed-action') || '';
            if (action === 'full') {
                toggleBlockFullWidth(block, setEmbedBlockWidth, applyEmbedAlignment);
                sync(textarea, editor);
                return true;
            }
            if (action === 'delete') {
                deleteBlock(block, removeEmbedBlock);
            }
            return true;
        }

        function handleImageToolbarClick(event) {
            var alignButton = event.target.closest('[data-image-align]');
            if (alignButton) {
                event.preventDefault();
                var alignBlock = alignButton.closest('.block.block-image');
                if (!alignBlock) {
                    return true;
                }
                applyImageAlignment(alignBlock, alignButton.getAttribute('data-image-align') || 'center');
                sync(textarea, editor);
                return true;
            }

            var actionButton = event.target.closest('[data-image-action]');
            if (!actionButton) {
                return false;
            }
            event.preventDefault();
            var block = actionButton.closest('.block.block-image');
            if (!block) {
                return true;
            }
            var action = actionButton.getAttribute('data-image-action') || '';
            if (action === 'full') {
                toggleBlockFullWidth(block, setImageBlockWidth, applyImageAlignment);
                sync(textarea, editor);
                return true;
            }
            if (action === 'replace') {
                mediaController.openReplaceLibrary(block);
                return true;
            }
            if (action === 'delete') {
                deleteBlock(block, removeImageBlock);
            }
            return true;
        }

        function ensureSelectionInsideEditor() {
            if (!isSelectionInside(editor)) {
                focusEditorEnd(editor);
            }
        }

        function updateFormatState() {
            if (htmlMode || !isSelectionInside(editor)) {
                bold.classList.remove('is-active');
                italic.classList.remove('is-active');
                quote.classList.remove('is-active');
                return;
            }
            var insideHeading = isSelectionInsideHeading(editor);
            bold.classList.toggle('is-active', !insideHeading && document.queryCommandState('bold'));
            italic.classList.toggle('is-active', document.queryCommandState('italic'));
            quote.classList.toggle('is-active', isSelectionInsideTag(editor, 'blockquote'));
        }

        function runCommand(command, value) {
            if (htmlMode) {
                return;
            }
            editor.focus();
            document.execCommand('defaultParagraphSeparator', false, 'p');
            document.execCommand(command, false, value || null);
            persistEditorState(true);
            closeMenus();
        }

        function resetTypingInlineFormats() {
            ['bold', 'italic'].forEach(function (command) {
                if (document.queryCommandState(command)) {
                    document.execCommand(command, false, null);
                }
            });
        }

        function insertParagraphAndResetFormats() {
            document.execCommand('defaultParagraphSeparator', false, 'p');
            document.execCommand('insertParagraph', false, null);
            resetTypingInlineFormats();
        }

        function blankParagraph() {
            var paragraph = document.createElement('p');
            paragraph.innerHTML = '<br>';
            return paragraph;
        }

        function previousQuoteNode(root, node) {
            var current = node.previousSibling;
            if (current) {
                while (current.lastChild) {
                    current = current.lastChild;
                }
                return current;
            }
            return node.parentNode && node.parentNode !== root ? node.parentNode : null;
        }

        function quoteEmptyLineBreak(quoteBlock) {
            var selected = window.getSelection();
            if (!selected || selected.rangeCount === 0 || !selected.isCollapsed) {
                return null;
            }

            var range = selected.getRangeAt(0).cloneRange();
            var marker = document.createElement('span');
            marker.setAttribute('data-tinycms-caret-marker', '1');
            range.insertNode(marker);

            var current = previousQuoteNode(quoteBlock, marker);
            var foundBreak = null;
            while (current && current !== quoteBlock) {
                if (current.nodeType === Node.TEXT_NODE && current.textContent.replace(/\u00a0/g, ' ').trim() !== '') {
                    break;
                }
                if (current.nodeType === Node.ELEMENT_NODE) {
                    if (current.tagName === 'BR') {
                        foundBreak = current;
                        break;
                    }
                    if (current.querySelector && (current.matches('img, iframe, hr, table') || current.querySelector('img, iframe, hr, table'))) {
                        break;
                    }
                }
                current = previousQuoteNode(quoteBlock, current);
            }

            var restoreRange = document.createRange();
            restoreRange.setStartBefore(marker);
            restoreRange.collapse(true);
            marker.remove();
            if (!foundBreak) {
                restoreSelection(restoreRange, editor);
            }
            return foundBreak;
        }

        function exitQuote(quoteBlock, removeNode) {
            var paragraph = blankParagraph();
            var quoteParent = quoteBlock.parentNode;
            var quoteNextSibling = quoteBlock.nextSibling;
            if (removeNode && removeNode.parentNode) {
                removeNode.remove();
            }
            if (quoteBlock.textContent.replace(/\u00a0/g, ' ').trim() === '' && !quoteBlock.querySelector('img, iframe, hr, video, audio, table')) {
                quoteBlock.remove();
            }
            if (quoteParent) {
                quoteParent.insertBefore(paragraph, quoteNextSibling);
            } else {
                editor.appendChild(paragraph);
            }
            placeCaret(paragraph);
            persistEditorState(true);
            return true;
        }

        function insertPagebreak() {
            var insertId = blockInsertId('pagebreak');
            insertStandaloneHtml('<hr' + blockInsertAttr(insertId) + '>', insertId);
        }

        function handleEnterOnSelectedBlock() {
            var imageBlock = selectedImageBlock();
            if (imageBlock) {
                var nextImage = imageBlock.nextElementSibling;
                var imageTarget = nextImage && nextImage.classList.contains('block-image-break') ? nextImage : createImageBreakParagraph();
                if (imageTarget !== nextImage) {
                    imageBlock.parentNode.insertBefore(imageTarget, imageBlock.nextSibling);
                }
                placeCaret(imageTarget);
                persistEditorState(false);
                return true;
            }

            var embedBlock = selectedEmbedBlock();
            if (!embedBlock) {
                return false;
            }
            var next = embedBlock.nextElementSibling;
            var target = next && next.tagName === 'P' ? next : createImageBreakParagraph();
            if (target !== next) {
                embedBlock.parentNode.insertBefore(target, embedBlock.nextSibling);
            }
            if (target.classList.contains('block-image-break')) {
                target.classList.remove('block-image-break');
            }
            placeCaret(target);
            persistEditorState(false);
            return true;
        }

        function handleEnterInQuote() {
            var quoteContainer = getSelectionContainer(editor);
            var quoteBlock = quoteContainer ? quoteContainer.closest('blockquote') : null;
            if (!quoteBlock) {
                return false;
            }

            var currentBlock = quoteContainer.closest('p, h1, h2, h3, h4, h5, h6, li, div');
            if (currentBlock && quoteBlock.contains(currentBlock) && isEmptyTextBlock(currentBlock)) {
                currentBlock.remove();
                return exitQuote(quoteBlock, null);
            }

            var emptyLineBreak = quoteEmptyLineBreak(quoteBlock);
            if (emptyLineBreak) {
                return exitQuote(quoteBlock, emptyLineBreak);
            }

            document.execCommand('insertLineBreak', false, null);
            persistEditorState(true);
            return true;
        }

        function handleEnterKey(event) {
            if (event.key !== 'Enter' || event.shiftKey) {
                return false;
            }
            event.preventDefault();
            if (handleEnterOnSelectedBlock()) {
                resetTypingInlineFormats();
                updateFormatState();
                return true;
            }
            if (handleEnterInQuote()) {
                resetTypingInlineFormats();
                updateFormatState();
                return true;
            }
            insertParagraphAndResetFormats();
            persistEditorState(true);
            return true;
        }

        function handleEditorKeydown(event) {
            if ((event.key === 'Backspace' || event.key === 'Delete') && deleteSelectedBlock()) {
                event.preventDefault();
                return;
            }

            handleEnterKey(event);
        }

        function handleEditorPaste(event) {
            mediaController.handlePaste(event);
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
            textarea.value = cleanSerializedHtml(textarea.value);
            editor.innerHTML = textarea.value.trim();
            normalizeBlocks(editor);
            enhanceImageBlocks(editor);
            enhanceEmbedBlocks(editor);
            textarea.style.display = 'none';
            sync(textarea, editor);
        }

        function syncEditorFromTextarea() {
            if (htmlMode) {
                return;
            }
            textarea.value = cleanSerializedHtml(textarea.value);
            editor.innerHTML = textarea.value.trim();
            normalizeBlocks(editor);
            enhanceImageBlocks(editor);
            enhanceEmbedBlocks(editor);
            updateFormatState();
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
                mediaController.openInsertLibrary();
                return;
            }

            if (command === 'insertPagebreak') {
                if (htmlMode) {
                    return;
                }
                insertPagebreak();
                return;
            }

            if (toggleMenuByCommand[command]) {
                if (htmlMode) {
                    return;
                }
                toggleMenu(toggleMenuByCommand[command]);
                return;
            }

            if (command === 'toggleLinkPanel') {
                if (htmlMode) {
                    return;
                }
                linkController.rememberCurrent();
                linkController.togglePanel();
                return;
            }

            if (command.indexOf('formatBlock:') === 0) {
                if (command === 'formatBlock:blockquote') {
                    if (isSelectionInsideTag(editor, 'blockquote')) {
                        runCommand('formatBlock', '<p>');
                        return;
                    }
                }
                runCommand('formatBlock', '<' + command.split(':')[1] + '>');
                return;
            }

            runCommand(command);
        });

        linkModal.addEventListener('click', linkController.handleModalClick);
        linkModal.addEventListener('input', linkController.handleModalInput);
        linkModal.addEventListener('keydown', linkController.handleModalKeydown);

        linkTools.addEventListener('mousedown', function (event) {
            event.preventDefault();
        });

        ['click', 'dblclick', 'auxclick'].forEach(function (type) {
            editor.addEventListener(type, linkController.navigate, true);
        });

        editor.addEventListener('mousedown', function (event) {
            if (event.detail > 1 || event.ctrlKey || event.metaKey) {
                linkController.navigate(event);
            }
        }, true);

        linkTools.addEventListener('click', linkController.handleToolsClick);

        document.addEventListener('click', function (event) {
            if (!wrapper.contains(event.target) && !linkModal.contains(event.target)) {
                closeMenus();
                updateFormatState();
            }
        });

        editor.addEventListener('scroll', function () {
            linkController.syncTools();
            syncSelectedToolbars();
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
        function resizeCursor(position) {
            if (position === 't' || position === 'b') {
                return 'ns-resize';
            }
            if (position === 'l' || position === 'r') {
                return 'ew-resize';
            }
            return position === 'tl' || position === 'br' ? 'nwse-resize' : 'nesw-resize';
        }

        function stopResize() {
            resizingState = null;
            document.body.classList.remove('is-image-resizing');
            document.body.style.cursor = '';
        }

        function startImageResize(event) {
            var handle = event.target.closest('.image-resize-handle');
            if (!handle) {
                return false;
            }
            var block = handle.closest('.block.block-image');
            var image = block ? block.querySelector('img[data-media-id]') : null;
            if (!block || !image) {
                return false;
            }
            event.preventDefault();
            var position = handle.getAttribute('data-image-resize') || 'br';
            var rect = image.getBoundingClientRect();
            resizingState = {
                pointerId: event.pointerId,
                block: block,
                image: image,
                editorWidth: Math.max(1, editor.clientWidth),
                startX: event.clientX,
                startY: event.clientY,
                startWidth: rect.width,
                startHeight: rect.height,
                position: position,
            };
            handle.setPointerCapture(event.pointerId);
            document.body.classList.add('is-image-resizing');
            document.body.style.cursor = resizeCursor(position);
            return true;
        }

        function resizeDelta(state, event) {
            var position = state.position;
            var dx = position.indexOf('l') >= 0 ? state.startX - event.clientX : (position.indexOf('r') >= 0 ? event.clientX - state.startX : 0);
            var dy = position.indexOf('t') >= 0 ? state.startY - event.clientY : (position.indexOf('b') >= 0 ? event.clientY - state.startY : 0);
            var ratio = state.startWidth / Math.max(1, state.startHeight);
            var verticalDelta = dy * ratio;
            return Math.abs(verticalDelta) > Math.abs(dx) ? verticalDelta : dx;
        }

        function updateImageResize(event) {
            if (!resizingState || event.pointerId !== resizingState.pointerId) {
                return false;
            }
            event.preventDefault();
            var width = Math.max(80, resizingState.startWidth + resizeDelta(resizingState, event));
            var percent = Math.min(100, Math.max(10, (width / resizingState.editorWidth) * 100));
            setImageBlockWidth(resizingState.block, percent.toFixed(2).replace(/\.00$/, '') + '%');
            sync(textarea, editor);
            return true;
        }

        function stopImageResize(event) {
            if (!resizingState || event.pointerId !== resizingState.pointerId) {
                return false;
            }
            stopResize();
            return true;
        }

        editor.addEventListener('pointerdown', startImageResize);

        document.addEventListener('pointermove', updateImageResize);

        ['pointerup', 'pointercancel'].forEach(function (type) {
            document.addEventListener(type, stopImageResize);
        });

        editor.addEventListener('click', function (event) {
            if (event.target.closest('.wysiwyg-link-tools')) {
                return;
            }

            if (linkController.isModalOpen()) {
                linkController.hideTools();
            } else {
                linkController.closeAll();
            }

            if (handleEmbedToolbarClick(event)) {
                return;
            }

            if (handleImageToolbarClick(event)) {
                return;
            }

            var clickedImage = event.target.closest('.block.block-image');
            if (clickedImage) {
                selectImageBlock(clickedImage);
                return;
            }
            selectEmbedBlock(event.target.closest('.block.block-embed'));
        });

        document.addEventListener('tinycms:media-library-selected', mediaController.handleLibrarySelected);

        editor.addEventListener('keydown', handleEditorKeydown);
        editor.addEventListener('paste', handleEditorPaste);

        editor.addEventListener('input', function () {
            sync(textarea, editor);
            updateFormatState();
        });

        editor.addEventListener('blur', function () {
            if (htmlMode) {
                return;
            }
            persistEditorState(true);
        });

        textarea.addEventListener('tinycms:editor-sync-from-textarea', function () {
            syncEditorFromTextarea();
        });

        textarea.style.display = 'none';
        textarea.parentNode.insertBefore(wrapper, textarea);
        wrapper.appendChild(toolbar);
        document.body.appendChild(linkModal);
        wrapper.appendChild(linkTools);
        wrapper.appendChild(editor);
        wrapper.appendChild(textarea);
        var form = editorForm();
        if (form) {
            form.addEventListener('submit', function () {
                if (htmlMode) {
                    textarea.value = cleanSerializedHtml(textarea.value);
                    return;
                }
                persistEditorState(false);
            });
        }

        document.execCommand('defaultParagraphSeparator', false, 'p');
        normalizeBlocks(editor);
        enhanceImageBlocks(editor);
        enhanceEmbedBlocks(editor);
        sync(textarea, editor);
        updateFormatState();
        linkController.updateApplyState();
    }

    function initAll(root) {
        var scope = root || document;
        var textareas = scope.matches && scope.matches('textarea[data-wysiwyg]')
            ? [scope]
            : scope.querySelectorAll('textarea[data-wysiwyg]');
        textareas.forEach(init);
    }

    editorModules.init = init;
    editorModules.initAll = initAll;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initAll(document);
        });
    }
})();
