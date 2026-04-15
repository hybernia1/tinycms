(function () {
    var t = window.tinycms?.i18n?.t || function () { return ''; };
    var requestJson = window.tinycms?.api?.http?.requestJson;
    var postForm = window.tinycms?.api?.http?.postForm;
    var ui = window.tinycmsEditorUi || {};
    var blocks = window.tinycmsEditorImageBlocks || {};
    var selectionUtils = window.tinycmsEditorSelection || {};
    var utils = window.tinycmsEditorUtils || {};
    var editorCounter = 0;

    var createHeadingGroup = ui.createHeadingGroup;
    var createIconButton = ui.createIconButton;
    var createListGroup = ui.createListGroup;
    var createAlignGroup = ui.createAlignGroup;
    var createLinkModal = ui.createLinkModal;
    var createLinkToolButton = ui.createLinkToolButton;

    var createLoadingImageBlock = blocks.createLoadingImageBlock;
    var ensureImageBlock = blocks.ensureImageBlock;
    var enhanceImageBlocks = blocks.enhanceImageBlocks;
    var createImageBreakParagraph = blocks.createImageBreakParagraph;
    var normalizeBlocks = blocks.normalizeBlocks;
    var sync = blocks.sync;
    var applyImageAlignment = blocks.applyImageAlignment;

    var rememberSelection = selectionUtils.rememberSelection;
    var restoreSelection = selectionUtils.restoreSelection;
    var focusEditorEnd = selectionUtils.focusEditorEnd;
    var isSelectionInside = selectionUtils.isSelectionInside;
    var getCurrentLink = selectionUtils.getCurrentLink;
    var getSelectionContainer = selectionUtils.getSelectionContainer;
    var isSelectionInsideTag = selectionUtils.isSelectionInsideTag;
    var isSelectionInsideHeading = selectionUtils.isSelectionInsideHeading;
    var placeCaret = selectionUtils.placeCaret;

    var normalizeLinkUrl = utils.normalizeLinkUrl;
    var escapeHtml = utils.escapeHtml;
    var extractYoutubeVideoId = utils.extractYoutubeVideoId;
    var extractPastedUrl = utils.extractPastedUrl;
    var findPastedImageFile = utils.findPastedImageFile;
    function init(textarea) {
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
        var link = createIconButton('w-link', 'toggleLinkPanel', 'Odkaz');
        var listGroup = createListGroup();
        var html = createIconButton('w-html', 'toggleHtml', 'HTML');
        var media = createIconButton('w-image', 'openMediaLibrary', ''+ t('editor.insert_image') + '');
        var pagebreak = createIconButton('w-pagebreak', 'insertPagebreak', ''+ t('editor.page_break') + '');
        var alignGroup = createAlignGroup();
        var focus = createIconButton('w-focus', 'toggleFocusMode', ''+ t('editor.focus_mode') + '');
        focus.classList.add('wysiwyg-btn-focus');
        var linkModal = createLinkModal();
        var linkTools = document.createElement('div');
        linkTools.className = 'wysiwyg-link-tools';
        linkTools.setAttribute('contenteditable', 'false');
        linkTools.appendChild(createLinkToolButton('w-link-edit', 'link-inline-edit', 'Upravit odkaz'));
        linkTools.appendChild(createLinkToolButton('w-link-unlink', 'link-inline-remove', t('editor.unlink')));

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

        var linkRange = null;
        var activeLink = null;
        var htmlMode = false;
        var mediaRange = null;
        var linkPasteSeq = 0;
        var draftInitPromise = null;
        var toggleMenuByCommand = {
            toggleListMenu: 'is-list-open',
            toggleHeadingMenu: 'is-heading-open',
            toggleAlignMenu: 'is-align-open'
        };

        function absoluteMediaUrl(path) {
            var value = String(path || '').trim();
            if (!value) {
                return '';
            }
            if (/^https?:\/\//i.test(value)) {
                return value;
            }
            var base = String(textarea.dataset.mediaBaseUrl || '').trim().replace(/\/$/, '');
            var normalized = value.charAt(0) === '/' ? value : ('/' + value);
            return base === '' ? normalized : (base + normalized);
        }

        function contentApiBase() {
            var draftEndpoint = String((textarea.closest('form') || {}).dataset ? textarea.closest('form').dataset.draftInitEndpoint || '' : '').trim();
            if (!draftEndpoint) {
                return '';
            }
            return draftEndpoint.replace(/\/admin\/api\/v1\/content\/draft\/init.*$/, '');
        }

        function mediaEndpointForContentId(id) {
            var value = Number(id || 0);
            if (value <= 0) {
                return '';
            }
            var current = String(textarea.dataset.mediaLibraryEndpoint || '').trim();
            if (current !== '') {
                var rewritten = current.replace(/\/admin\/api\/v1\/content\/\d+\/media(?:\/.*)?$/, '/admin/api/v1/content/' + value + '/media');
                if (rewritten !== current || /\/admin\/api\/v1\/content\/\d+\/media/.test(rewritten)) {
                    return rewritten;
                }
            }
            var base = contentApiBase();
            if (base === '') {
                return '';
            }
            return base + '/admin/api/v1/content/' + value + '/media';
        }

        function setContentIdEverywhere(id) {
            var value = Number(id || 0);
            if (value <= 0) {
                return;
            }
            var form = textarea.closest('form');
            if (form) {
                form.querySelectorAll('input[name="id"]').forEach(function (node) {
                    node.value = String(value);
                });
                form.querySelectorAll('input[name="content_id"]').forEach(function (node) {
                    node.value = String(value);
                });
            }
            textarea.dataset.contentId = String(value);
            var mediaEndpoint = mediaEndpointForContentId(value);
            if (mediaEndpoint !== '') {
                textarea.dataset.mediaLibraryEndpoint = mediaEndpoint;
            }
        }

        function ensureDraftId() {
            var currentId = Number(textarea.dataset.contentId || '0');
            if (currentId > 0) {
                return Promise.resolve(currentId);
            }
            if (draftInitPromise) {
                return draftInitPromise;
            }
            var form = textarea.closest('form');
            var endpoint = String(form && form.dataset ? form.dataset.draftInitEndpoint || '' : '').trim();
            var csrfInput = form ? form.querySelector('input[name="_csrf"]') : null;
            if (!endpoint || !csrfInput || typeof requestJson !== 'function') {
                return Promise.resolve(0);
            }

            draftInitPromise = requestJson(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: '_csrf=' + encodeURIComponent(csrfInput.value || '')
            }).then(function (result) {
                var response = result && result.response ? result.response : null;
                var normalized = result && result.data ? result.data : null;
                var newId = response && response.ok && normalized && normalized.success && normalized.data
                    ? Number(normalized.data.id || 0)
                    : 0;
                if (newId > 0) {
                    setContentIdEverywhere(newId);
                }
                return newId;
            }).catch(function () {
                return 0;
            }).finally(function () {
                draftInitPromise = null;
            });

            return draftInitPromise;
        }

        function waitForImageReady(url, retries) {
            var maxRetries = typeof retries === 'number' ? retries : 8;
            return new Promise(function (resolve) {
                var attempt = 0;
                function tryLoad() {
                    var image = new Image();
                    image.onload = function () {
                        resolve(true);
                    };
                    image.onerror = function () {
                        attempt += 1;
                        if (attempt >= maxRetries) {
                            resolve(false);
                            return;
                        }
                        window.setTimeout(tryLoad, 350);
                    };
                    image.src = url + (url.indexOf('?') === -1 ? '?' : '&') + 'v=' + Date.now();
                }
                tryLoad();
            });
        }

        function uploadPastedImage(file, loadingBlock) {
            if (!file) {
                return;
            }
            function removeLoadingBlockAndPersist() {
                if (loadingBlock && loadingBlock.parentNode) {
                    loadingBlock.remove();
                    persistEditorState(true);
                }
            }
            ensureDraftId().then(function (contentId) {
                if (contentId <= 0) {
                    return null;
                }
                var endpoint = mediaEndpointForContentId(contentId);
                if (!endpoint) {
                    return null;
                }
                var form = textarea.closest('form');
                var csrfInput = form ? form.querySelector('input[name="_csrf"]') : null;
                if (!csrfInput) {
                    return null;
                }
                var data = new FormData();
                data.append('_csrf', csrfInput.value || '');
                data.append('content_id', String(contentId));
                data.append('thumbnail', file, file.name || 'clipboard-image.png');
                if (typeof postForm !== 'function') {
                    return null;
                }
                return postForm(endpoint + '/upload', data, {
                    credentials: 'same-origin',
                });
            }).then(function (result) {
                var response = result && result.response ? result.response : null;
                var normalized = result && result.data ? result.data : null;
                var media = response && response.ok && normalized && normalized.success && normalized.data
                    ? normalized.data
                    : null;
                if (!media) {
                    removeLoadingBlockAndPersist();
                    return;
                }
                var mediaId = Number(media.id || 0);
                var imageUrl = absoluteMediaUrl(media.webp_path || media.path || media.preview_path || '');
                if (mediaId <= 0 || !imageUrl) {
                    removeLoadingBlockAndPersist();
                    return;
                }
                waitForImageReady(imageUrl).then(function (ready) {
                    if (!loadingBlock || !loadingBlock.parentNode) {
                        return;
                    }
                    if (!ready) {
                        removeLoadingBlockAndPersist();
                        return;
                    }
                    loadingBlock.classList.remove('is-loading');
                    loadingBlock.innerHTML = '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(String(media.name || '')) + '" data-media-id="' + mediaId + '">';
                    ensureImageBlock(loadingBlock);
                    if (!loadingBlock.nextElementSibling || loadingBlock.nextElementSibling.tagName !== 'P') {
                        loadingBlock.parentNode.insertBefore(createImageBreakParagraph(), loadingBlock.nextSibling);
                    }
                    persistEditorState(true);
                }).catch(function () {
                    removeLoadingBlockAndPersist();
                });
            }).catch(function () {
                removeLoadingBlockAndPersist();
            });
        }

        function hideLinkTools() {
            linkTools.classList.remove('is-visible');
            linkTools.style.top = '';
            linkTools.style.left = '';
        }

        function showLinkTools(linkNode) {
            if (!linkNode || !editor.contains(linkNode) || htmlMode) {
                hideLinkTools();
                return;
            }
            var linkRect = linkNode.getBoundingClientRect();
            var editorRect = editor.getBoundingClientRect();
            linkTools.style.left = (editor.offsetLeft + linkRect.left - editorRect.left + editor.scrollLeft) + 'px';
            linkTools.style.top = (editor.offsetTop + linkRect.bottom - editorRect.top + editor.scrollTop + 6) + 'px';
            linkTools.classList.add('is-visible');
        }

        function openLinkModal() {
            var linkInput = linkModal.querySelector('[data-role="link-input"]');
            var linkTextInput = linkModal.querySelector('[data-role="link-text-input"]');
            var linkTargetBlank = linkModal.querySelector('[data-role="link-target-blank"]');
            var linkNoFollow = linkModal.querySelector('[data-role="link-nofollow"]');
            var relValues = (activeLink ? (activeLink.getAttribute('rel') || '') : '').split(/\s+/).filter(Boolean);
            var selectedText = linkRange && !linkRange.collapsed ? linkRange.toString().replace(/\s+/g, ' ').trim() : '';

            linkModal.classList.add('open');
            wrapper.classList.remove('is-list-open');

            if (linkInput) {
                linkInput.value = activeLink ? (activeLink.getAttribute('href') || '') : '';
                linkInput.focus();
                linkInput.select();
            }
            if (linkTextInput) {
                linkTextInput.value = activeLink ? (activeLink.textContent || '').trim() : selectedText;
            }
            if (linkTargetBlank) {
                linkTargetBlank.checked = !!(activeLink && activeLink.getAttribute('target') === '_blank');
            }
            if (linkNoFollow) {
                linkNoFollow.checked = relValues.indexOf('nofollow') !== -1;
            }
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

        function updateLinkApplyState() {
            var linkInput = linkModal.querySelector('[data-role="link-input"]');
            var applyButton = linkModal.querySelector('[data-role="link-apply"]');
            if (!applyButton) {
                return;
            }
            applyButton.disabled = !linkInput || linkInput.value.trim() === '';
        }

        function resetLinkModalFields() {
            var linkInput = linkModal.querySelector('[data-role="link-input"]');
            var linkTextInput = linkModal.querySelector('[data-role="link-text-input"]');
            var linkTargetBlank = linkModal.querySelector('[data-role="link-target-blank"]');
            var linkNoFollow = linkModal.querySelector('[data-role="link-nofollow"]');
            if (linkInput) {
                linkInput.value = '';
            }
            if (linkTextInput) {
                linkTextInput.value = '';
            }
            if (linkTargetBlank) {
                linkTargetBlank.checked = false;
            }
            if (linkNoFollow) {
                linkNoFollow.checked = false;
            }
            updateLinkApplyState();
        }

        function toggleMenu(menuClass) {
            ['is-heading-open', 'is-list-open', 'is-align-open'].forEach(function (className) {
                if (className === menuClass) {
                    wrapper.classList.toggle(className);
                    return;
                }
                wrapper.classList.remove(className);
            });
            linkModal.classList.remove('open');
        }

        function closeMenus() {
            wrapper.classList.remove('is-heading-open');
            wrapper.classList.remove('is-list-open');
            wrapper.classList.remove('is-align-open');
            linkModal.classList.remove('open');
            hideLinkTools();
            activeLink = null;
        }

        function persistEditorState(withFormatState) {
            normalizeBlocks(editor);
            enhanceImageBlocks(editor);
            sync(textarea, editor);
            if (withFormatState) {
                updateFormatState();
            }
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

        function isEmptyTextBlock(node) {
            if (!node) {
                return false;
            }
            if (node.querySelector('img, iframe, hr, video, audio, table')) {
                return false;
            }
            var text = (node.textContent || '').replace(/\u00a0/g, ' ').trim();
            if (text !== '') {
                return false;
            }
            var html = String(node.innerHTML || '').replace(/\u00a0/g, ' ').replace(/\s+/g, '').toLowerCase();
            return html === '' || html === '<br>' || html === '<br/>';
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

        function syncEditorFromTextarea() {
            if (htmlMode) {
                return;
            }
            editor.innerHTML = textarea.value.trim();
            normalizeBlocks(editor);
            enhanceImageBlocks(editor);
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

            if (command === 'insertPagebreak') {
                if (htmlMode) {
                    return;
                }
                ensureSelectionInsideEditor();
                document.execCommand('insertHTML', false, '<hr />');
                persistEditorState(true);
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
                if (isSelectionInside(editor)) {
                    linkRange = rememberSelection();
                    activeLink = getCurrentLink(editor);
                }
                if (linkModal.classList.contains('open')) {
                    closeMenus();
                } else {
                    openLinkModal();
                }
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

        linkModal.addEventListener('click', function (event) {
            var linkInput = linkModal.querySelector('[data-role="link-input"]');
            var linkTextInput = linkModal.querySelector('[data-role="link-text-input"]');
            var linkTargetBlank = linkModal.querySelector('[data-role="link-target-blank"]');
            var linkNoFollow = linkModal.querySelector('[data-role="link-nofollow"]');

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
                persistEditorState(true);
                resetLinkModalFields();
                activeLink = null;
                closeMenus();
                return;
            }

            var apply = event.target.closest('[data-role="link-apply"]');
            if (apply) {
                var url = normalizeLinkUrl(linkInput ? linkInput.value : '');
                var textValue = linkTextInput ? linkTextInput.value.trim() : '';
                var withTargetBlank = !!(linkTargetBlank && linkTargetBlank.checked);
                var withNoFollow = !!(linkNoFollow && linkNoFollow.checked);
                if (url) {
                    var linkNode = null;
                    if (activeLink && editor.contains(activeLink) && (!linkRange || linkRange.collapsed)) {
                        activeLink.setAttribute('href', url);
                        linkNode = activeLink;
                    } else {
                        restoreSelection(linkRange, editor);
                        document.execCommand('defaultParagraphSeparator', false, 'p');
                        document.execCommand('createLink', false, url);
                        linkNode = getCurrentLink(editor);
                    }
                    if (linkNode && editor.contains(linkNode)) {
                        if (withTargetBlank) {
                            linkNode.setAttribute('target', '_blank');
                        } else {
                            linkNode.removeAttribute('target');
                        }
                        var relTokens = [];
                        if (withTargetBlank) {
                            relTokens.push('noopener');
                            relTokens.push('noreferrer');
                        }
                        if (withNoFollow) {
                            relTokens.push('nofollow');
                        }
                        if (relTokens.length) {
                            linkNode.setAttribute('rel', relTokens.join(' '));
                        } else {
                            linkNode.removeAttribute('rel');
                        }
                        if (textValue) {
                            linkNode.textContent = textValue;
                        }
                    }
                    persistEditorState(true);
                    resetLinkModalFields();
                    activeLink = null;
                }
                closeMenus();
                return;
            }

            if (event.target.closest('[data-role="link-cancel"]')) {
                resetLinkModalFields();
                activeLink = null;
                closeMenus();
            }
        });

        linkModal.addEventListener('input', function (event) {
            if (event.target && event.target.matches('[data-role="link-input"]')) {
                updateLinkApplyState();
            }
        });

        linkTools.addEventListener('mousedown', function (event) {
            event.preventDefault();
        });

        linkTools.addEventListener('click', function (event) {
            var editButton = event.target.closest('[data-role="link-inline-edit"]');
            if (editButton) {
                if (activeLink && editor.contains(activeLink)) {
                    openLinkModal();
                }
                return;
            }
            var removeButton = event.target.closest('[data-role="link-inline-remove"]');
            if (!removeButton || !activeLink || !editor.contains(activeLink)) {
                return;
            }
            var parent = activeLink.parentNode;
            while (activeLink.firstChild) {
                parent.insertBefore(activeLink.firstChild, activeLink);
            }
            parent.removeChild(activeLink);
            persistEditorState(true);
            hideLinkTools();
            activeLink = null;
        });

        document.addEventListener('click', function (event) {
            if (!wrapper.contains(event.target) && !linkModal.contains(event.target)) {
                closeMenus();
                updateFormatState();
            }
        });

        editor.addEventListener('scroll', function () {
            if (activeLink && linkTools.classList.contains('is-visible')) {
                showLinkTools(activeLink);
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
            if (event.target.closest('.wysiwyg-link-tools')) {
                return;
            }

            var clickedLink = event.target.closest('a');
            if (clickedLink && editor.contains(clickedLink)) {
                event.preventDefault();
                activeLink = clickedLink;
                linkRange = null;
                showLinkTools(clickedLink);
                return;
            }

            hideLinkTools();
            if (!linkModal.classList.contains('open')) {
                activeLink = null;
            }

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
            persistEditorState(true);
        });

        editor.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' || event.key === 'Delete') {
                var selectedImageForDelete = editor.querySelector('.block.block-image.is-selected');
                if (selectedImageForDelete) {
                    event.preventDefault();
                    var nextNode = selectedImageForDelete.nextElementSibling;
                    var prevNode = selectedImageForDelete.previousElementSibling;
                    if (nextNode && nextNode.classList.contains('block-image-break')) {
                        nextNode.remove();
                        nextNode = selectedImageForDelete.nextElementSibling;
                    } else if (prevNode && prevNode.classList.contains('block-image-break')) {
                        prevNode.remove();
                        prevNode = selectedImageForDelete.previousElementSibling;
                    }
                    selectedImageForDelete.remove();
                    var caretTarget = null;
                    if (nextNode && nextNode.parentNode === editor) {
                        caretTarget = nextNode;
                    } else if (prevNode && prevNode.parentNode === editor) {
                        caretTarget = prevNode;
                    } else {
                        caretTarget = createImageBreakParagraph();
                        editor.appendChild(caretTarget);
                    }
                    if (caretTarget.classList && caretTarget.classList.contains('block') && caretTarget.classList.contains('block-image')) {
                        var spacer = createImageBreakParagraph();
                        editor.insertBefore(spacer, caretTarget);
                        caretTarget = spacer;
                    }
                    placeCaret(caretTarget);
                    persistEditorState(false);
                    return;
                }
            }

            function handleEnterKey() {
                if (event.key !== 'Enter' || event.shiftKey) {
                    return false;
                }
                var selectedImageBlock = editor.querySelector('.block.block-image.is-selected');
                if (selectedImageBlock) {
                    event.preventDefault();
                    var next = selectedImageBlock.nextElementSibling;
                    var target = next && next.classList.contains('block-image-break') ? next : createImageBreakParagraph();
                    if (target !== next) {
                        selectedImageBlock.parentNode.insertBefore(target, selectedImageBlock.nextSibling);
                    }
                    placeCaret(target);
                    persistEditorState(false);
                    return true;
                }
                var quoteContainer = getSelectionContainer(editor);
                var quoteBlock = quoteContainer ? quoteContainer.closest('blockquote') : null;
                if (quoteBlock) {
                    var currentBlock = quoteContainer.closest('p, h1, h2, h3, h4, h5, h6, li, div');
                    if (currentBlock && quoteBlock.contains(currentBlock) && isEmptyTextBlock(currentBlock)) {
                        event.preventDefault();
                        currentBlock.remove();
                        var paragraph = document.createElement('p');
                        paragraph.innerHTML = '<br>';
                        var quoteParent = quoteBlock.parentNode;
                        var quoteNextSibling = quoteBlock.nextSibling;
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
                    event.preventDefault();
                    var nextParagraph = document.createElement('p');
                    nextParagraph.innerHTML = '<br>';
                    if (currentBlock && quoteBlock.contains(currentBlock) && currentBlock !== quoteBlock) {
                        currentBlock.parentNode.insertBefore(nextParagraph, currentBlock.nextSibling);
                    } else {
                        quoteBlock.appendChild(nextParagraph);
                    }
                    placeCaret(nextParagraph);
                    persistEditorState(true);
                    return true;
                }
                document.execCommand('defaultParagraphSeparator', false, 'p');
                event.preventDefault();
                document.execCommand('insertParagraph', false, null);
                persistEditorState(true);
                return true;
            }
            if (handleEnterKey()) {
                return;
            }

        });

        editor.addEventListener('paste', function (event) {
            if (htmlMode) {
                return;
            }
            var pastedImage = findPastedImageFile(event);
            if (pastedImage) {
                event.preventDefault();
                var imageRange = isSelectionInside(editor) ? rememberSelection() : null;
                if (!imageRange) {
                    focusEditorEnd(editor);
                    imageRange = rememberSelection();
                }
                var loadingBlock = createLoadingImageBlock();
                if (imageRange) {
                    restoreSelection(imageRange, editor);
                    imageRange.deleteContents();
                    imageRange.insertNode(loadingBlock);
                    imageRange.setStartAfter(loadingBlock);
                    imageRange.collapse(true);
                    restoreSelection(imageRange, editor);
                } else {
                    editor.appendChild(loadingBlock);
                }
                sync(textarea, editor);
                uploadPastedImage(pastedImage, loadingBlock);
                return;
            }
            var clipboard = event.clipboardData;
            var text = clipboard ? clipboard.getData('text/plain') : '';
            var videoId = extractYoutubeVideoId(text);
            if (!videoId) {
                var pastedUrl = extractPastedUrl(text);
                if (!pastedUrl) {
                    return;
                }
                event.preventDefault();
                ensureSelectionInsideEditor();
                linkPasteSeq += 1;
                var linkId = 'paste-link-' + editorId + '-' + linkPasteSeq;
                document.execCommand('insertHTML', false, '<a href="' + escapeHtml(pastedUrl) + '" data-paste-link-id="' + linkId + '">' + escapeHtml(pastedUrl) + '</a>');
                persistEditorState(true);
                var endpoint = (textarea.dataset.linkTitleEndpoint || '').trim();
                if (!endpoint || typeof requestJson !== 'function') {
                    return;
                }
                requestJson(endpoint + '?url=' + encodeURIComponent(pastedUrl), {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                }).then(function (result) {
                    if (!result || !result.response || !result.response.ok) {
                        return null;
                    }
                    return result.data;
                }).then(function (payload) {
                    var title = payload && payload.success && payload.data ? String(payload.data.title || '').trim() : '';
                    if (!title) {
                        return;
                    }
                    var linkNode = editor.querySelector('a[data-paste-link-id="' + linkId + '"]');
                    if (!linkNode) {
                        return;
                    }
                    linkNode.textContent = title;
                    linkNode.removeAttribute('data-paste-link-id');
                    sync(textarea, editor);
                }).catch(function () {});
                return;
            }
            event.preventDefault();
            ensureSelectionInsideEditor();
            var embedHtml = '<div class="block block-embed block-embed-youtube"><div class="embed-frame"><iframe src="https://www.youtube.com/embed/' + videoId + '" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"></iframe></div></div><p><br></p>';
            document.execCommand('insertHTML', false, embedHtml);
            persistEditorState(true);
        });

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
        var form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                if (htmlMode) {
                    return;
                }
                persistEditorState(false);
            });
        }

        document.execCommand('defaultParagraphSeparator', false, 'p');
        normalizeBlocks(editor);
        enhanceImageBlocks(editor);
        sync(textarea, editor);
        updateFormatState();
        updateLinkApplyState();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var textareas = document.querySelectorAll('textarea[data-wysiwyg]');
        textareas.forEach(init);
    });
})();
