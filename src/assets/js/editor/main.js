(function () {
    var app = window.tinycms = window.tinycms || {};
    var t = app.i18n?.t || function () { return ""; };
    var modalUi = app.ui?.modal || {
        open: function (modal) { if (modal) { modal.classList.add('open'); } },
        close: function (modal) { if (modal) { modal.classList.remove('open'); } }
    };
    var requestJson = app.api?.http?.requestJson;
    var postForm = app.api?.http?.postForm;
    var editorModules = app.editor || {};
    var sanitize = editorModules.sanitize || {};
    var blocks = editorModules.blocks || {};
    var selection = editorModules.selection || {};
    var toolbarModule = editorModules.toolbar || {};
    var editorCounter = 0;

    var cleanSerializedHtml = sanitize.cleanSerializedHtml;
    var escapeHtml = sanitize.escapeHtml;
    var extractPastedUrl = sanitize.extractPastedUrl;
    var extractYoutubeVideoId = sanitize.extractYoutubeVideoId;
    var findPastedImageFile = sanitize.findPastedImageFile;
    var isEmptyTextBlock = sanitize.isEmptyTextBlock;
    var normalizeLinkUrl = sanitize.normalizeLinkUrl;

    var applyEmbedAlignment = blocks.applyEmbedAlignment;
    var applyImageAlignment = blocks.applyImageAlignment;
    var createImageBreakParagraph = blocks.createImageBreakParagraph;
    var createLoadingImageBlock = blocks.createLoadingImageBlock;
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
        var linkModal = editorModules.linkModal.create();
        var linkTools = document.createElement('div');
        linkTools.className = 'wysiwyg-link-tools';
        linkTools.setAttribute('contenteditable', 'false');
        linkTools.appendChild(createLinkToolButton('w-link-edit', 'link-inline-edit', t('editor.edit_link')));
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
        var mediaReplaceBlock = null;
        var linkPasteSeq = 0;
        var draftInitPromise = null;
        var toggleMenuByCommand = {
            toggleListMenu: 'is-list-open',
            toggleHeadingMenu: 'is-heading-open',
            toggleAlignMenu: 'is-align-open'
        };

        function editorForm() {
            return textarea.closest('form');
        }

        function csrfValue() {
            var input = editorForm()?.querySelector('input[name="_csrf"]');
            return input ? input.value || '' : '';
        }

        function linkModalNodes() {
            return {
                input: linkModal.querySelector('[data-role="link-input"]'),
                textInput: linkModal.querySelector('[data-role="link-text-input"]'),
                targetBlank: linkModal.querySelector('[data-role="link-target-blank"]'),
                noFollow: linkModal.querySelector('[data-role="link-nofollow"]'),
                apply: linkModal.querySelector('[data-role="link-apply"]')
            };
        }

        function unwrapLink(linkNode) {
            if (!linkNode || !linkNode.parentNode) {
                return;
            }
            var parent = linkNode.parentNode;
            while (linkNode.firstChild) {
                parent.insertBefore(linkNode.firstChild, linkNode);
            }
            parent.removeChild(linkNode);
        }

        function imageHtml(url, name, mediaId) {
            return '<img src="' + escapeHtml(url) + '" alt="' + escapeHtml(String(name || '')) + '" data-media-id="' + Number(mediaId || 0) + '">';
        }

        function imageBlockHtml(url, name, mediaId) {
            return '<div class="block block-image align-center">' + imageHtml(url, name, mediaId) + '</div><p><br></p>';
        }

        function applyLinkOptions(linkNode, targetBlank, noFollow) {
            if (targetBlank) {
                linkNode.setAttribute('target', '_blank');
            } else {
                linkNode.removeAttribute('target');
            }

            var relTokens = [];
            if (targetBlank) {
                relTokens.push('noopener');
                relTokens.push('noreferrer');
            }
            if (noFollow) {
                relTokens.push('nofollow');
            }
            if (relTokens.length) {
                linkNode.setAttribute('rel', relTokens.join(' '));
                return;
            }
            linkNode.removeAttribute('rel');
        }

        function pasteLinkHtml(url, id) {
            return '<a href="' + escapeHtml(url) + '" data-paste-link-id="' + escapeHtml(id) + '">' + escapeHtml(url) + '</a>';
        }

        function refreshPastedLinkTitle(linkId, url) {
            var endpoint = (textarea.dataset.linkTitleEndpoint || '').trim();
            if (!endpoint || typeof requestJson !== 'function') {
                return;
            }
            requestJson(endpoint + '?url=' + encodeURIComponent(url), {
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
        }

        function insertPastedLink(url) {
            ensureSelectionInsideEditor();
            linkPasteSeq += 1;
            var linkId = 'paste-link-' + editorId + '-' + linkPasteSeq;
            document.execCommand('insertHTML', false, pasteLinkHtml(url, linkId));
            persistEditorState(true);
            refreshPastedLinkTitle(linkId, url);
        }

        function youtubeEmbedHtml(videoId, insertId) {
            return '<div class="block block-embed block-embed-youtube align-center" data-embed-insert-id="' + escapeHtml(insertId) + '"><div class="embed-frame"><iframe src="https://www.youtube.com/embed/' + escapeHtml(videoId) + '" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"></iframe></div></div><p><br></p>';
        }

        function insertYoutubeEmbed(videoId) {
            ensureSelectionInsideEditor();
            var embedInsertId = 'embed-insert-' + editorId + '-' + Date.now();
            document.execCommand('insertHTML', false, youtubeEmbedHtml(videoId, embedInsertId));
            var insertedEmbed = editor.querySelector('[data-embed-insert-id="' + embedInsertId + '"]');
            if (insertedEmbed) {
                insertedEmbed.removeAttribute('data-embed-insert-id');
                placeCaretAfterBlock(insertedEmbed);
            }
            persistEditorState(true);
        }

        function insertLoadingImageBlock() {
            var imageRange = isSelectionInside(editor) ? rememberSelection() : null;
            if (!imageRange) {
                focusEditorEnd(editor);
                imageRange = rememberSelection();
            }
            var loadingBlock = createLoadingImageBlock();
            if (!imageRange) {
                editor.appendChild(loadingBlock);
                return loadingBlock;
            }
            restoreSelection(imageRange, editor);
            imageRange.deleteContents();
            imageRange.insertNode(loadingBlock);
            imageRange.setStartAfter(loadingBlock);
            imageRange.collapse(true);
            restoreSelection(imageRange, editor);
            return loadingBlock;
        }

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
            var form = editorForm();
            var draftEndpoint = String(form && form.dataset ? form.dataset.draftInitEndpoint || '' : '').trim();
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
            var form = editorForm();
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
            var form = editorForm();
            var endpoint = String(form && form.dataset ? form.dataset.draftInitEndpoint || '' : '').trim();
            var csrf = csrfValue();
            if (!endpoint || !csrf || typeof requestJson !== 'function') {
                return Promise.resolve(0);
            }

            draftInitPromise = requestJson(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: '_csrf=' + encodeURIComponent(csrf)
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
                var csrf = csrfValue();
                if (!csrf) {
                    return null;
                }
                var data = new FormData();
                data.append('_csrf', csrf);
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
                var imageUrl = absoluteMediaUrl(media.webp_path || media.preview_path || '');
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
                    loadingBlock.innerHTML = imageHtml(imageUrl, media.name, mediaId);
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

        function linkFromEditorEvent(event) {
            var target = eventElement(event);
            if (!target || target.closest('.wysiwyg-link-tools')) {
                return null;
            }
            var linkNode = target.closest('a');
            return linkNode && editor.contains(linkNode) ? linkNode : null;
        }

        function blockEditorLinkNavigation(event) {
            var linkNode = linkFromEditorEvent(event);
            if (!linkNode) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            activeLink = linkNode;
            linkRange = null;
            showLinkTools(linkNode);
        }

        function openLinkModal() {
            var fields = linkModalNodes();
            var relValues = (activeLink ? (activeLink.getAttribute('rel') || '') : '').split(/\s+/).filter(Boolean);
            var selectedText = linkRange && !linkRange.collapsed ? linkRange.toString().replace(/\s+/g, ' ').trim() : '';

            modalUi.open(linkModal);
            wrapper.classList.remove('is-list-open');

            if (fields.input) {
                fields.input.value = activeLink ? (activeLink.getAttribute('href') || '') : '';
                fields.input.focus();
                fields.input.select();
            }
            if (fields.textInput) {
                fields.textInput.value = activeLink ? (activeLink.textContent || '').trim() : selectedText;
            }
            if (fields.targetBlank) {
                fields.targetBlank.checked = !!(activeLink && activeLink.getAttribute('target') === '_blank');
            }
            if (fields.noFollow) {
                fields.noFollow.checked = relValues.indexOf('nofollow') !== -1;
            }
            updateLinkApplyState();
        }

        function closeLinkModal() {
            modalUi.close(linkModal);
        }

        function isLinkModalOpen() {
            return linkModal.classList.contains('open');
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
            var fields = linkModalNodes();
            if (!fields.apply) {
                return;
            }
            var inputUrl = fields.input ? fields.input.value.trim() : '';
            var existingUrl = activeLink && editor.contains(activeLink) ? String(activeLink.getAttribute('href') || '').trim() : '';
            var textValue = fields.textInput ? fields.textInput.value.trim() : '';
            fields.apply.disabled = inputUrl === '' && existingUrl === '' && textValue === '';
        }

        function resetLinkModalFields() {
            var fields = linkModalNodes();
            if (fields.input) {
                fields.input.value = '';
            }
            if (fields.textInput) {
                fields.textInput.value = '';
            }
            if (fields.targetBlank) {
                fields.targetBlank.checked = false;
            }
            if (fields.noFollow) {
                fields.noFollow.checked = false;
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
            closeLinkModal();
        }

        function closeMenus() {
            wrapper.classList.remove('is-heading-open');
            wrapper.classList.remove('is-list-open');
            wrapper.classList.remove('is-align-open');
            closeLinkModal();
            hideLinkTools();
            activeLink = null;
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

        function imageMediaId(block) {
            var image = block ? block.querySelector('img[data-media-id]') : null;
            return Number(image ? image.getAttribute('data-media-id') || '0' : '0');
        }

        function openEditorMediaLibrary(currentMediaId) {
            document.dispatchEvent(new CustomEvent('tinycms:media-library-open', {
                detail: {
                    mode: 'editor',
                    editorId: editorId,
                    contentId: Number(textarea.dataset.contentId || '0'),
                    endpoint: textarea.dataset.mediaLibraryEndpoint || '',
                    baseUrl: textarea.dataset.mediaBaseUrl || '',
                    currentMediaId: Number(currentMediaId || 0),
                },
            }));
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
                mediaRange = null;
                mediaReplaceBlock = block;
                openEditorMediaLibrary(imageMediaId(block));
                return true;
            }
            if (action === 'delete') {
                deleteBlock(block, removeImageBlock);
            }
            return true;
        }

        function replaceImageBlock(block, detail, mediaId) {
            var image = block ? block.querySelector('img[data-media-id]') : null;
            if (!image) {
                return false;
            }
            image.src = detail.url;
            image.alt = String(detail.name || '');
            image.setAttribute('data-media-id', String(mediaId));
            image.style.width = block.style.width === '' ? '' : '100%';
            ensureImageBlock(block);
            selectImageBlock(block);
            persistEditorState(true);
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
                var paragraph = blankParagraph();
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

            var nextParagraph = blankParagraph();
            if (currentBlock && quoteBlock.contains(currentBlock) && currentBlock !== quoteBlock) {
                currentBlock.parentNode.insertBefore(nextParagraph, currentBlock.nextSibling);
            } else {
                quoteBlock.appendChild(nextParagraph);
            }
            placeCaret(nextParagraph);
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
            if (htmlMode) {
                return;
            }
            var pastedImage = findPastedImageFile(event);
            if (pastedImage) {
                event.preventDefault();
                var loadingBlock = insertLoadingImageBlock();
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
                insertPastedLink(pastedUrl);
                return;
            }
            event.preventDefault();
            insertYoutubeEmbed(videoId);
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
                mediaRange = isSelectionInside(editor) ? rememberSelection() : null;
                mediaReplaceBlock = null;
                openEditorMediaLibrary(0);
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
                if (isLinkModalOpen()) {
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
            var fields = linkModalNodes();

            if (event.target.closest('[data-role="link-remove"]')) {
                if (activeLink && editor.contains(activeLink)) {
                    unwrapLink(activeLink);
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
                var url = normalizeLinkUrl(fields.input ? fields.input.value : '');
                if (!url && activeLink && editor.contains(activeLink)) {
                    url = normalizeLinkUrl(activeLink.getAttribute('href') || '');
                }
                var textValue = fields.textInput ? fields.textInput.value.trim() : '';
                var withTargetBlank = !!(fields.targetBlank && fields.targetBlank.checked);
                var withNoFollow = !!(fields.noFollow && fields.noFollow.checked);
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
                        applyLinkOptions(linkNode, withTargetBlank, withNoFollow);
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
            if (event.target && event.target.matches('[data-role="link-input"], [data-role="link-text-input"]')) {
                updateLinkApplyState();
            }
        });

        linkModal.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                resetLinkModalFields();
                activeLink = null;
                closeMenus();
                return;
            }
            if (event.key !== 'Enter' || event.shiftKey || event.ctrlKey || event.altKey || event.metaKey) {
                return;
            }
            event.preventDefault();
            linkModalNodes().apply?.click();
        });

        linkTools.addEventListener('mousedown', function (event) {
            event.preventDefault();
        });

        ['click', 'dblclick', 'auxclick'].forEach(function (type) {
            editor.addEventListener(type, blockEditorLinkNavigation, true);
        });

        editor.addEventListener('mousedown', function (event) {
            if (event.detail > 1 || event.ctrlKey || event.metaKey) {
                blockEditorLinkNavigation(event);
            }
        }, true);

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
            unwrapLink(activeLink);
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

            hideLinkTools();
            if (!isLinkModalOpen()) {
                activeLink = null;
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

        document.addEventListener('tinycms:media-library-selected', function (event) {
            var detail = event.detail || {};
            if (detail.editorId !== editorId || !detail.url) {
                return;
            }
            var mediaId = Number(detail.id || 0);
            if (mediaId <= 0) {
                return;
            }
            if (mediaReplaceBlock && editor.contains(mediaReplaceBlock)) {
                if (replaceImageBlock(mediaReplaceBlock, detail, mediaId)) {
                    mediaReplaceBlock = null;
                    return;
                }
            }
            mediaReplaceBlock = null;
            if (mediaRange) {
                restoreSelection(mediaRange, editor);
            } else {
                focusEditorEnd(editor);
            }
            document.execCommand('insertHTML', false, imageBlockHtml(detail.url, detail.name, mediaId));
            persistEditorState(true);
        });

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
        updateLinkApplyState();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var textareas = document.querySelectorAll('textarea[data-wysiwyg]');
        textareas.forEach(init);
    });
})();
