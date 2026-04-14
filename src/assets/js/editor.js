(function () {
    var t = window.tinycms?.i18n?.t || function () { return ""; };
    var requestJson = window.tinycms?.api?.http?.requestJson;
    var postForm = window.tinycms?.api?.http?.postForm;
    var editorCounter = 0;

    function normalizeHtml(html) {
        return html === '<br>' ? '' : html;
    }

    function extractYoutubeVideoId(value) {
        var raw = String(value || '').trim();
        if (!raw) {
            return null;
        }
        if (/^[a-zA-Z0-9_-]{11}$/.test(raw)) {
            return raw;
        }

        var normalized = raw;
        if (!/^[a-zA-Z][a-zA-Z\d+\-.]*:\/\//.test(normalized)) {
            normalized = 'https://' + normalized.replace(/^\/+/, '');
        }

        var parsed = null;
        try {
            parsed = new URL(normalized);
        } catch (error) {
            return null;
        }

        var host = parsed.hostname.toLowerCase().replace(/^www\./, '').replace(/^m\./, '');
        var id = '';
        if (host === 'youtu.be') {
            id = (parsed.pathname.split('/')[1] || '').trim();
        } else if (host === 'youtube.com' || host === 'youtube-nocookie.com') {
            if (parsed.pathname === '/watch') {
                id = (parsed.searchParams.get('v') || '').trim();
            } else {
                var pathMatch = parsed.pathname.match(/^\/(embed|shorts|live)\/([^/?#]+)/);
                id = pathMatch ? pathMatch[2].trim() : '';
            }
        }

        return /^[a-zA-Z0-9_-]{11}$/.test(id) ? id : null;
    }

    function normalizeLinkUrl(url) {
        var value = String(url || '').trim();
        if (!value) {
            return '';
        }
        if (/^(mailto:|tel:|https?:\/\/|\/|#)/i.test(value)) {
            return value;
        }
        return 'https://' + value.replace(/^\/+/, '');
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function extractPastedUrl(value) {
        var raw = String(value || '').trim();
        if (!raw || /\s/.test(raw)) {
            return '';
        }
        var normalized = normalizeLinkUrl(raw);
        if (!/^https?:\/\//i.test(normalized)) {
            return '';
        }
        try {
            new URL(normalized);
        } catch (error) {
            return '';
        }
        return normalized;
    }

    function findPastedImageFile(event) {
        var clipboard = event.clipboardData;
        if (!clipboard || !clipboard.items) {
            return null;
        }
        for (var i = 0; i < clipboard.items.length; i += 1) {
            var item = clipboard.items[i];
            if (item && item.kind === 'file' && /^image\//i.test(item.type || '')) {
                return item.getAsFile();
            }
        }
        return null;
    }

    function createLoadingImageBlock() {
        var block = document.createElement('div');
        block.className = 'block block-image align-center is-loading';
        block.innerHTML = '<div class="image-upload-loading" contenteditable="false"><svg class="icon" aria-hidden="true"><use href="/src/assets/svg/icons.svg#icon-loader"></use></svg></div>';
        return block;
    }

    function createImageControls() {
        var controls = document.createElement('div');
        controls.className = 'image-controls';
        controls.setAttribute('contenteditable', 'false');
        controls.innerHTML = '<button type="button" class="btn btn-light btn-xs" data-image-align="left">' + t('editor.align_left') + '</button>'
            + '<button type="button" class="btn btn-light btn-xs" data-image-align="center">' + t('editor.align_center') + '</button>'
            + '<button type="button" class="btn btn-light btn-xs" data-image-align="right">' + t('editor.align_right') + '</button>';
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

                if (node.classList.contains('block') && node.classList.contains('block-embed')) {
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

    function getSelectionContainer(editor) {
        var selection = window.getSelection();
        if (!selection || selection.rangeCount === 0 || !editor.contains(selection.anchorNode)) {
            return null;
        }
        var node = selection.anchorNode;
        return node && node.nodeType === Node.TEXT_NODE ? node.parentElement : node;
    }

    function isSelectionInsideTag(editor, tagName) {
        var container = getSelectionContainer(editor);
        return !!(container && container.closest(tagName));
    }

    function isSelectionInsideHeading(editor) {
        var container = getSelectionContainer(editor);
        return !!(container && container.closest('h1, h2, h3, h4, h5, h6'));
    }

    function createIconButton(icon, command, title) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'wysiwyg-btn';
        button.setAttribute('data-command', command);
        button.setAttribute('aria-label', title);
        button.title = title;
        button.innerHTML = '<svg aria-hidden="true"><use href="/src/assets/svg/icons.svg#icon-' + icon + '"></use></svg>';
        return button;
    }

    function createMenuItem(icon, command, label) {
        var item = document.createElement('button');
        item.type = 'button';
        item.className = 'wysiwyg-menu-item';
        item.setAttribute('data-command', command);
        item.innerHTML = '<svg aria-hidden="true"><use href="/src/assets/svg/icons.svg#icon-' + icon + '"></use></svg><span>' + label + '</span>';
        return item;
    }

    function createLinkToolButton(icon, role, title) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'wysiwyg-link-tool-btn';
        button.setAttribute('data-role', role);
        button.setAttribute('aria-label', title);
        button.title = title;
        button.innerHTML = '<svg aria-hidden="true"><use href="/src/assets/svg/icons.svg#icon-' + icon + '"></use></svg>';
        return button;
    }

    function createHeadingGroup() {
        var group = document.createElement('div');
        group.className = 'wysiwyg-group wysiwyg-group-heading';

        var toggle = createIconButton('w-heading', 'toggleHeadingMenu', t('editor.headings'));

        var menu = document.createElement('div');
        menu.className = 'wysiwyg-menu wysiwyg-menu-heading';

        menu.appendChild(createMenuItem('w-heading', 'formatBlock:p', t('editor.paragraph')));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h1', t('editor.heading_1')));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h2', t('editor.heading_2')));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h3', t('editor.heading_3')));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h4', t('editor.heading_4')));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h5', t('editor.heading_5')));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h6', t('editor.heading_6')));
        group.appendChild(toggle);
        group.appendChild(menu);
        return group;
    }

    function createListGroup() {
        var group = document.createElement('div');
        group.className = 'wysiwyg-group wysiwyg-group-list';

        var toggle = createIconButton('w-ul', 'toggleListMenu', t('editor.lists'));

        var menu = document.createElement('div');
        menu.className = 'wysiwyg-menu wysiwyg-menu-list';

        menu.appendChild(createMenuItem('w-ul', 'insertUnorderedList', ''+ t('editor.list_bulleted') + ''));
        menu.appendChild(createMenuItem('w-ol', 'insertOrderedList', ''+ t('editor.list_numbered') + ''));
        group.appendChild(toggle);
        group.appendChild(menu);
        return group;
    }

    function createAlignGroup() {
        var group = document.createElement('div');
        group.className = 'wysiwyg-group wysiwyg-group-align';

        var toggle = createIconButton('w-align-left', 'toggleAlignMenu', ''+ t('editor.alignment') + '');

        var menu = document.createElement('div');
        menu.className = 'wysiwyg-menu wysiwyg-menu-align';

        menu.appendChild(createMenuItem('w-align-left', 'justifyLeft', '' + t('editor.align_left') + ''));
        menu.appendChild(createMenuItem('w-align-center', 'justifyCenter', ''+ t('editor.align_center') + ''));
        menu.appendChild(createMenuItem('w-align-right', 'justifyRight', '' + t('editor.align_right') + ''));
        menu.appendChild(createMenuItem('w-align-justify', 'justifyFull', t('editor.align_justify')));
        group.appendChild(toggle);
        group.appendChild(menu);
        return group;
    }

    function createLinkModal() {
        var modal = document.createElement('div');
        modal.className = 'modal-overlay wysiwyg-link-modal';
        modal.setAttribute('data-modal', '');

        var dialog = document.createElement('div');
        dialog.className = 'modal wysiwyg-link-dialog';

        var title = document.createElement('h3');
        title.className = 'wysiwyg-link-title';
        title.textContent = '' + t('editor.insert_link') + '';

        var input = document.createElement('input');
        input.type = 'url';
        input.placeholder = 'https://';
        input.className = 'wysiwyg-link-input';
        input.setAttribute('data-role', 'link-input');

        var textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.placeholder = t('editor.link_text');
        textInput.className = 'wysiwyg-link-input';
        textInput.setAttribute('data-role', 'link-text-input');

        var options = document.createElement('div');
        options.className = 'wysiwyg-link-options';

        var targetOption = document.createElement('label');
        targetOption.className = 'wysiwyg-link-option';
        var targetInput = document.createElement('input');
        targetInput.type = 'checkbox';
        targetInput.setAttribute('data-role', 'link-target-blank');
        targetOption.appendChild(targetInput);
        targetOption.appendChild(document.createTextNode(' ' + t('editor.open_new_window')));

        var nofollowOption = document.createElement('label');
        nofollowOption.className = 'wysiwyg-link-option';
        var nofollowInput = document.createElement('input');
        nofollowInput.type = 'checkbox';
        nofollowInput.setAttribute('data-role', 'link-nofollow');
        nofollowOption.appendChild(nofollowInput);
        nofollowOption.appendChild(document.createTextNode(' ' + t('editor.add_nofollow')));

        var actions = document.createElement('div');
        actions.className = 'modal-actions wysiwyg-link-actions';

        var cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'btn btn-light';
        cancel.setAttribute('data-role', 'link-cancel');
        cancel.setAttribute('data-modal-close', '');
        cancel.textContent = '' + t('editor.cancel') + '';

        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-light';
        remove.setAttribute('data-role', 'link-remove');
        remove.textContent = t('editor.remove_link');

        var confirm = document.createElement('button');
        confirm.type = 'button';
        confirm.className = 'btn btn-primary';
        confirm.setAttribute('data-role', 'link-apply');
        confirm.setAttribute('data-modal-confirm', '');
        confirm.setAttribute('data-modal-confirm-manual', '');
        confirm.textContent = '' + t('editor.save') + '';

        actions.appendChild(cancel);
        actions.appendChild(remove);
        actions.appendChild(confirm);
        options.appendChild(targetOption);
        options.appendChild(nofollowOption);
        dialog.appendChild(title);
        dialog.appendChild(input);
        dialog.appendChild(textInput);
        dialog.appendChild(options);
        dialog.appendChild(actions);
        modal.appendChild(dialog);
        return modal;
    }

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
                    if (loadingBlock && loadingBlock.parentNode) {
                        loadingBlock.remove();
                        persistEditorState(true);
                    }
                    return;
                }
                var mediaId = Number(media.id || 0);
                var imageUrl = absoluteMediaUrl(media.webp_path || media.path || media.preview_path || '');
                if (mediaId <= 0 || !imageUrl) {
                    if (loadingBlock && loadingBlock.parentNode) {
                        loadingBlock.remove();
                        persistEditorState(true);
                    }
                    return;
                }
                waitForImageReady(imageUrl).then(function (ready) {
                    if (!loadingBlock || !loadingBlock.parentNode) {
                        return;
                    }
                    if (!ready) {
                        loadingBlock.remove();
                        persistEditorState(true);
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
                    if (loadingBlock && loadingBlock.parentNode) {
                        loadingBlock.remove();
                        persistEditorState(true);
                    }
                });
            }).catch(function () {
                if (loadingBlock && loadingBlock.parentNode) {
                    loadingBlock.remove();
                    persistEditorState(true);
                }
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
                if (!isSelectionInside(editor)) {
                    focusEditorEnd(editor);
                }
                document.execCommand('insertHTML', false, '<hr />');
                persistEditorState(true);
                return;
            }

            if (command === 'toggleListMenu') {
                if (htmlMode) {
                    return;
                }
                toggleMenu('is-list-open');
                return;
            }

            if (command === 'toggleHeadingMenu') {
                if (htmlMode) {
                    return;
                }
                toggleMenu('is-heading-open');
                return;
            }

            if (command === 'toggleAlignMenu') {
                if (htmlMode) {
                    return;
                }
                toggleMenu('is-align-open');
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
                    persistEditorState(false);
                    return;
                }
            }

            if (event.key === 'Enter' && !event.shiftKey) {
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
                        return;
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
                    return;
                }
            }

            if (event.key === 'Enter' && !event.shiftKey) {
                document.execCommand('defaultParagraphSeparator', false, 'p');
                event.preventDefault();
                document.execCommand('insertParagraph', false, null);
                persistEditorState(true);
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
                if (!isSelectionInside(editor)) {
                    focusEditorEnd(editor);
                }
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
            if (!isSelectionInside(editor)) {
                focusEditorEnd(editor);
            }
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
