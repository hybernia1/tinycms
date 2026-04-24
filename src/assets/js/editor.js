(function () {
    var t = window.tinycms?.i18n?.t || function () { return ""; };
    var requestJson = window.tinycms?.api?.http?.requestJson;
    var postForm = window.tinycms?.api?.http?.postForm;
    var editorCounter = 0;
    var imageResizePositions = ['tl', 't', 'tr', 'r', 'br', 'b', 'bl', 'l'];
    var blockedCleanTags = ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'option'];
    var allowedCleanTags = ['p', 'br', 'strong', 'em', 'a', 'ul', 'ol', 'li', 'blockquote', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'img', 'iframe', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col'];

    function translate(key, fallback) {
        return t(key, fallback) || fallback;
    }

    function iconSvg(name) {
        return window.tinycms?.icons?.icon?.(name, '') || '';
    }

    function normalizeHtml(html) {
        return html === '<br>' ? '' : html;
    }

    function eventElement(event) {
        var target = event && event.target ? event.target : null;
        return target && target.nodeType === Node.TEXT_NODE ? target.parentElement : target;
    }

    function unwrapNode(node) {
        var parent = node.parentNode;
        if (!parent) {
            return;
        }
        while (node.firstChild) {
            parent.insertBefore(node.firstChild, node);
        }
        parent.removeChild(node);
    }

    function renameNode(node, tagName) {
        var replacement = document.createElement(tagName);
        while (node.firstChild) {
            replacement.appendChild(node.firstChild);
        }
        node.parentNode.replaceChild(replacement, node);
        return replacement;
    }

    function safeUrl(value, allowRelative) {
        var url = String(value || '').trim();
        if (!url) {
            return '';
        }
        if (/^[a-zA-Z][a-zA-Z\d+\-.]*:/.test(url) && !/^(https?:|mailto:|tel:)/i.test(url)) {
            return '';
        }
        if (/^(https?:\/\/|mailto:|tel:|#)/i.test(url)) {
            return url;
        }
        return allowRelative && /^\/(?!\/)/.test(url) ? url : '';
    }

    function safeHref(value) {
        var url = String(value || '').trim();
        if (!url || (/^[a-zA-Z][a-zA-Z\d+\-.]*:/.test(url) && !/^(https?:|mailto:|tel:)/i.test(url))) {
            return '';
        }
        return safeUrl(normalizeLinkUrl(url), true);
    }

    function safeYoutubeEmbedUrl(value) {
        var url = String(value || '').trim();
        if (!url) {
            return '';
        }
        try {
            var parsed = /^https?:\/\//i.test(url) ? new URL(url) : new URL(url, window.location.origin);
            var host = parsed.hostname.toLowerCase().replace(/^www\./, '').replace(/^m\./, '');
            var match = parsed.pathname.match(/^\/embed\/([a-zA-Z0-9_-]{11})$/);
            if ((host === 'youtube.com' || host === 'youtube-nocookie.com') && match) {
                return 'https://www.youtube.com/embed/' + match[1];
            }
        } catch (error) {
            return '';
        }
        return '';
    }

    function safeWidthStyle(value) {
        var match = String(value || '').match(/(?:^|;)\s*width\s*:\s*(\d+(?:\.\d+)?)%\s*(?:;|$)/i);
        if (!match) {
            return '';
        }
        var width = Math.max(10, Math.min(100, Number(match[1])));
        return 'width: ' + width.toFixed(2).replace(/\.00$/, '') + '%;';
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

    function normalizeTextNodes(root) {
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        var nodes = [];
        var node = walker.nextNode();
        while (node) {
            nodes.push(node);
            node = walker.nextNode();
        }
        nodes.forEach(function (textNode) {
            textNode.textContent = textNode.textContent.replace(/\u00a0/g, ' ');
        });
    }

    function cleanElement(node) {
        Array.prototype.slice.call(node.children).forEach(cleanElement);

        var tag = node.tagName.toLowerCase();
        if (tag === 'b' || tag === 'i') {
            node = renameNode(node, tag === 'b' ? 'strong' : 'em');
            tag = node.tagName.toLowerCase();
        }

        if (blockedCleanTags.indexOf(tag) >= 0) {
            node.remove();
            return;
        }

        if (allowedCleanTags.indexOf(tag) === -1) {
            unwrapNode(node);
            return;
        }

        var values = {};
        Array.prototype.slice.call(node.attributes).forEach(function (attribute) {
            values[attribute.name.toLowerCase()] = attribute.value;
            node.removeAttribute(attribute.name);
        });

        if (tag === 'a') {
            var href = safeHref(values.href || '');
            if (!href) {
                unwrapNode(node);
                return;
            }
            node.setAttribute('href', href);
            if (values.target === '_blank') {
                node.setAttribute('target', '_blank');
                node.setAttribute('rel', ['noopener', 'noreferrer'].concat(/\bnofollow\b/i.test(values.rel || '') ? ['nofollow'] : []).join(' '));
            } else if (/\bnofollow\b/i.test(values.rel || '')) {
                node.setAttribute('rel', 'nofollow');
            }
            return;
        }

        if (tag === 'img') {
            var src = safeUrl(values.src || '', true);
            var mediaId = String(values['data-media-id'] || '').trim();
            if (!src) {
                node.remove();
                return;
            }
            node.setAttribute('src', src);
            node.setAttribute('alt', String(values.alt || '').trim());
            if (/^\d+$/.test(mediaId)) {
                node.setAttribute('data-media-id', mediaId);
            }
            var imageStyle = safeWidthStyle(values.style || '');
            if (imageStyle) {
                node.setAttribute('style', imageStyle);
            }
            return;
        }

        if (tag === 'iframe') {
            var iframeSrc = safeYoutubeEmbedUrl(values.src || '');
            if (!iframeSrc) {
                node.remove();
                return;
            }
            node.setAttribute('src', iframeSrc);
            node.setAttribute('loading', 'lazy');
            node.setAttribute('allowfullscreen', '');
            node.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            node.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
            return;
        }

        if (tag === 'th' || tag === 'td') {
            var colspan = String(values.colspan || '').trim();
            var rowspan = String(values.rowspan || '').trim();
            if (/^\d+$/.test(colspan) && Number(colspan) > 1) {
                node.setAttribute('colspan', colspan);
            }
            if (/^\d+$/.test(rowspan) && Number(rowspan) > 1) {
                node.setAttribute('rowspan', rowspan);
            }
            return;
        }

        if (tag === 'div') {
            var className = ' ' + (values.class || '') + ' ';
            if (className.indexOf(' block-list ') >= 0) {
                unwrapNode(node);
                return;
            }
            if (className.indexOf(' block-image ') >= 0) {
                var align = className.indexOf(' align-left ') >= 0 ? 'left' : (className.indexOf(' align-right ') >= 0 ? 'right' : 'center');
                node.className = 'block block-image align-' + align;
                var blockStyle = safeWidthStyle(values.style || '');
                if (blockStyle) {
                    node.setAttribute('style', blockStyle);
                }
                return;
            }
            if (className.indexOf(' block-embed-youtube ') >= 0) {
                var embedAlign = className.indexOf(' align-left ') >= 0 ? 'left' : (className.indexOf(' align-right ') >= 0 ? 'right' : 'center');
                node.className = 'block block-embed block-embed-youtube align-' + embedAlign;
                var embedStyle = safeWidthStyle(values.style || '');
                if (embedStyle) {
                    node.setAttribute('style', embedStyle);
                }
                return;
            }
            if (className.indexOf(' embed-frame ') >= 0) {
                node.className = 'embed-frame';
                return;
            }
            unwrapNode(node);
        }
    }

    function cleanSerializedHtml(html) {
        var template = document.createElement('template');
        template.innerHTML = String(html || '');
        Array.prototype.slice.call(template.content.childNodes).forEach(function (node) {
            if (node.nodeType === Node.ELEMENT_NODE) {
                cleanElement(node);
            } else if (node.nodeType !== Node.TEXT_NODE || node.textContent.trim() === '') {
                node.remove();
            }
        });
        normalizeTextNodes(template.content);
        template.content.querySelectorAll('p.block-image-break').forEach(function (node) {
            node.removeAttribute('class');
        });
        template.content.querySelectorAll('.block.block-image').forEach(function (node) {
            if (!node.querySelector('img')) {
                node.remove();
            }
        });
        template.content.querySelectorAll('.block.block-embed').forEach(function (node) {
            if (!node.querySelector('iframe')) {
                node.remove();
            }
        });
        template.content.querySelectorAll('p').forEach(function (node) {
            if (isEmptyTextBlock(node)) {
                node.remove();
            }
        });
        return normalizeHtml(template.innerHTML.trim());
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
        block.innerHTML = '<div class="image-upload-loading" contenteditable="false">' + iconSvg('loader') + '</div>';
        return block;
    }

    function createBlockToolButton(icon, title, attributes) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'image-toolbar-btn';
        button.title = title;
        button.setAttribute('aria-label', title);
        Object.keys(attributes || {}).forEach(function (name) {
            button.setAttribute(name, attributes[name]);
        });
        button.innerHTML = iconSvg(icon);
        return button;
    }

    function createImageToolbar() {
        var toolbar = document.createElement('div');
        var items = [
            ['w-align-left', translate('editor.align_left', 'Left'), {'data-image-align': 'left'}],
            ['w-align-center', translate('editor.align_center', 'Center'), {'data-image-align': 'center'}],
            ['w-align-right', translate('editor.align_right', 'Right'), {'data-image-align': 'right'}],
            ['w-align-justify', translate('editor.image_full_width', 'Full width'), {'data-image-action': 'full'}],
            ['concept', translate('editor.image_replace', 'Replace image'), {'data-image-action': 'replace'}],
            ['delete', translate('editor.image_delete', 'Delete image'), {'data-image-action': 'delete'}],
        ];
        toolbar.className = 'image-toolbar';
        toolbar.setAttribute('contenteditable', 'false');
        items.forEach(function (item) {
            toolbar.appendChild(createBlockToolButton(item[0], item[1], item[2]));
        });
        return toolbar;
    }

    function createEmbedToolbar() {
        var toolbar = document.createElement('div');
        var items = [
            ['w-align-left', translate('editor.align_left', 'Left'), {'data-embed-align': 'left'}],
            ['w-align-center', translate('editor.align_center', 'Center'), {'data-embed-align': 'center'}],
            ['w-align-right', translate('editor.align_right', 'Right'), {'data-embed-align': 'right'}],
            ['w-align-justify', translate('editor.image_full_width', 'Full width'), {'data-embed-action': 'full'}],
            ['delete', translate('editor.image_delete', 'Delete'), {'data-embed-action': 'delete'}],
        ];
        toolbar.className = 'embed-toolbar';
        toolbar.setAttribute('contenteditable', 'false');
        items.forEach(function (item) {
            toolbar.appendChild(createBlockToolButton(item[0], item[1], item[2]));
        });
        return toolbar;
    }

    function createImageResizeHandle(position) {
        var handle = document.createElement('span');
        handle.className = 'image-resize-handle image-resize-handle-' + position;
        handle.setAttribute('contenteditable', 'false');
        handle.setAttribute('data-image-resize', position);
        return handle;
    }

    function createImageSelectionFrame() {
        var frame = document.createElement('span');
        frame.className = 'image-selection-frame';
        frame.setAttribute('contenteditable', 'false');
        return frame;
    }

    function createEmbedSelectionFrame() {
        var frame = document.createElement('span');
        frame.className = 'embed-selection-frame';
        frame.setAttribute('contenteditable', 'false');
        return frame;
    }

    function blockAlignment(block) {
        if (block.classList.contains('align-left')) {
            return 'left';
        }
        if (block.classList.contains('align-right')) {
            return 'right';
        }
        return 'center';
    }

    function syncToolbarState(block, alignAttribute, fullAttribute) {
        block.querySelectorAll('[' + alignAttribute + ']').forEach(function (button) {
            var active = button.getAttribute(alignAttribute) === blockAlignment(block);
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        block.querySelectorAll('[' + fullAttribute + '="full"]').forEach(function (button) {
            var active = String(block.style.width || '').trim() === '100%';
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function syncImageToolbarState(block) {
        syncToolbarState(block, 'data-image-align', 'data-image-action');
    }

    function syncEmbedToolbarState(block) {
        syncToolbarState(block, 'data-embed-align', 'data-embed-action');
    }

    function placeToolbar(block, editor, selector, belowClass) {
        if (!block || !editor) {
            return;
        }
        var toolbar = block.querySelector(selector);
        if (!toolbar) {
            return;
        }
        block.classList.remove(belowClass);
        var blockRect = block.getBoundingClientRect();
        var editorRect = editor.getBoundingClientRect();
        var toolbarHeight = toolbar.offsetHeight || 38;
        var gap = 8;
        var hasTopSpace = blockRect.top - toolbarHeight - gap >= editorRect.top;
        block.classList.toggle(belowClass, !hasTopSpace);
    }

    function placeImageToolbar(block, editor) {
        placeToolbar(block, editor, '.image-toolbar', 'image-toolbar-below');
    }

    function placeEmbedToolbar(block, editor) {
        placeToolbar(block, editor, '.embed-toolbar', 'embed-toolbar-below');
    }

    function applyBlockAlignment(block, align, syncState) {
        if (!block) {
            return;
        }
        var value = ['left', 'center', 'right'].indexOf(align) >= 0 ? align : 'center';
        block.classList.remove('align-left', 'align-center', 'align-right');
        block.classList.add('align-' + value);
        syncState(block);
    }

    function applyImageAlignment(block, align) {
        applyBlockAlignment(block, align, syncImageToolbarState);
    }

    function applyEmbedAlignment(block, align) {
        applyBlockAlignment(block, align, syncEmbedToolbarState);
    }

    function ensureImageBlock(block) {
        if (!block.classList.contains('block-image')) {
            block.classList.add('block-image');
        }
        if (!block.classList.contains('align-left') && !block.classList.contains('align-center') && !block.classList.contains('align-right')) {
            applyImageAlignment(block, 'center');
        }

        if (!block.querySelector('.image-toolbar')) {
            block.appendChild(createImageToolbar());
        }

        if (!block.querySelector('.image-selection-frame')) {
            block.appendChild(createImageSelectionFrame());
        }

        if (block.querySelectorAll('.image-resize-handle[data-image-resize]').length !== 8) {
            block.querySelectorAll('.image-resize-handle').forEach(function (node) {
                node.remove();
            });
            imageResizePositions.forEach(function (position) {
                block.appendChild(createImageResizeHandle(position));
            });
        }

        var image = block.querySelector('img[data-media-id]');
        if (image && block.style.width === '' && image.style.width !== '') {
            block.style.width = image.style.width;
            image.style.width = '100%';
        }
        syncImageToolbarState(block);
    }

    function ensureEmbedBlock(block) {
        if (!block.classList.contains('block-embed')) {
            block.classList.add('block-embed');
        }
        if (!block.classList.contains('align-left') && !block.classList.contains('align-center') && !block.classList.contains('align-right')) {
            applyEmbedAlignment(block, 'center');
        }

        if (!block.querySelector('.embed-toolbar')) {
            block.appendChild(createEmbedToolbar());
        }

        if (!block.querySelector('.embed-selection-frame')) {
            block.appendChild(createEmbedSelectionFrame());
        }

        syncEmbedToolbarState(block);
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

    function enhanceEmbedBlocks(editor) {
        editor.querySelectorAll('.block.block-embed').forEach(function (block) {
            ensureEmbedBlock(block);
        });
    }

    function serializeEditorHtml(editor) {
        var clone = editor.cloneNode(true);
        clone.querySelectorAll('.image-toolbar, .image-resize-handle, .image-selection-frame, .embed-toolbar, .embed-selection-frame').forEach(function (node) {
            node.remove();
        });
        clone.querySelectorAll('.block.block-image, .block.block-embed').forEach(function (block) {
            block.classList.remove('is-selected');
        });
        return cleanSerializedHtml(clone.innerHTML);
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
                    ensureEmbedBlock(node);
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
        button.innerHTML = iconSvg(icon);
        return button;
    }

    function createMenuItem(icon, command, label) {
        var item = document.createElement('button');
        item.type = 'button';
        item.className = 'wysiwyg-menu-item';
        item.setAttribute('data-command', command);
        item.innerHTML = iconSvg(icon) + '<span>' + label + '</span>';
        return item;
    }

    function createLinkToolButton(icon, role, title) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'wysiwyg-link-tool-btn';
        button.setAttribute('data-role', role);
        button.setAttribute('aria-label', title);
        button.title = title;
        button.innerHTML = iconSvg(icon);
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
        var mediaReplaceBlock = null;
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
            updateLinkApplyState();
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
            var linkTextInput = linkModal.querySelector('[data-role="link-text-input"]');
            var applyButton = linkModal.querySelector('[data-role="link-apply"]');
            if (!applyButton) {
                return;
            }
            var inputUrl = linkInput ? linkInput.value.trim() : '';
            var existingUrl = activeLink && editor.contains(activeLink) ? String(activeLink.getAttribute('href') || '').trim() : '';
            var textValue = linkTextInput ? linkTextInput.value.trim() : '';
            applyButton.disabled = inputUrl === '' && existingUrl === '' && textValue === '';
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
                if (!url && activeLink && editor.contains(activeLink)) {
                    url = normalizeLinkUrl(activeLink.getAttribute('href') || '');
                }
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
            if (event.target && event.target.matches('[data-role="link-input"], [data-role="link-text-input"]')) {
                updateLinkApplyState();
            }
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
            placeImageToolbar(editor.querySelector('.block.block-image.is-selected'), editor);
            placeEmbedToolbar(editor.querySelector('.block.block-embed.is-selected'), editor);
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

        editor.addEventListener('pointerdown', function (event) {
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
        });

        document.addEventListener('pointermove', function (event) {
            if (!resizingState || event.pointerId !== resizingState.pointerId) {
                return;
            }
            event.preventDefault();
            var position = resizingState.position;
            var dx = position.indexOf('l') >= 0 ? resizingState.startX - event.clientX : (position.indexOf('r') >= 0 ? event.clientX - resizingState.startX : 0);
            var dy = position.indexOf('t') >= 0 ? resizingState.startY - event.clientY : (position.indexOf('b') >= 0 ? event.clientY - resizingState.startY : 0);
            var ratio = resizingState.startWidth / Math.max(1, resizingState.startHeight);
            var verticalDelta = dy * ratio;
            var delta = Math.abs(verticalDelta) > Math.abs(dx) ? verticalDelta : dx;
            var width = Math.max(80, resizingState.startWidth + delta);
            var percent = Math.min(100, Math.max(10, (width / resizingState.editorWidth) * 100));
            setImageBlockWidth(resizingState.block, percent.toFixed(2).replace(/\.00$/, '') + '%');
            sync(textarea, editor);
        });

        ['pointerup', 'pointercancel'].forEach(function (type) {
            document.addEventListener(type, function (event) {
                if (!resizingState || event.pointerId !== resizingState.pointerId) {
                    return;
                }
                stopResize();
            });
        });

        editor.addEventListener('click', function (event) {
            if (event.target.closest('.wysiwyg-link-tools')) {
                return;
            }

            hideLinkTools();
            if (!linkModal.classList.contains('open')) {
                activeLink = null;
            }

            var embedAlignButton = event.target.closest('[data-embed-align]');
            if (embedAlignButton) {
                event.preventDefault();
                var embedBlock = embedAlignButton.closest('.block.block-embed');
                if (!embedBlock) {
                    return;
                }
                applyEmbedAlignment(embedBlock, embedAlignButton.getAttribute('data-embed-align') || 'center');
                sync(textarea, editor);
                return;
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

            var embedActionButton = event.target.closest('[data-embed-action]');
            if (embedActionButton) {
                event.preventDefault();
                var embedActionBlock = embedActionButton.closest('.block.block-embed');
                if (!embedActionBlock) {
                    return;
                }
                var embedAction = embedActionButton.getAttribute('data-embed-action') || '';
                if (embedAction === 'full') {
                    toggleBlockFullWidth(embedActionBlock, setEmbedBlockWidth, applyEmbedAlignment);
                    sync(textarea, editor);
                    return;
                }
                if (embedAction === 'delete') {
                    var embedCaretTarget = removeEmbedBlock(embedActionBlock);
                    placeCaret(embedCaretTarget);
                    persistEditorState(false);
                }
                return;
            }

            var imageActionButton = event.target.closest('[data-image-action]');
            if (imageActionButton) {
                event.preventDefault();
                var actionBlock = imageActionButton.closest('.block.block-image');
                if (!actionBlock) {
                    return;
                }
                var imageAction = imageActionButton.getAttribute('data-image-action') || '';
                if (imageAction === 'full') {
                    toggleBlockFullWidth(actionBlock, setImageBlockWidth, applyImageAlignment);
                    sync(textarea, editor);
                    return;
                }
                if (imageAction === 'replace') {
                    mediaRange = null;
                    mediaReplaceBlock = actionBlock;
                    openEditorMediaLibrary(imageMediaId(actionBlock));
                    return;
                }
                if (imageAction === 'delete') {
                    var caretTarget = removeImageBlock(actionBlock);
                    placeCaret(caretTarget);
                    persistEditorState(false);
                }
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
            document.execCommand('insertHTML', false, '<div class="block block-image align-center"><img src="' + escapeHtml(detail.url) + '" alt="' + escapeHtml(String(detail.name || '')) + '" data-media-id="' + mediaId + '"></div><p><br></p>');
            persistEditorState(true);
        });

        editor.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' || event.key === 'Delete') {
                var selectedImageForDelete = editor.querySelector('.block.block-image.is-selected');
                if (selectedImageForDelete) {
                    event.preventDefault();
                    var caretTarget = removeImageBlock(selectedImageForDelete);
                    placeCaret(caretTarget);
                    persistEditorState(false);
                    return;
                }
                var selectedEmbedForDelete = editor.querySelector('.block.block-embed.is-selected');
                if (selectedEmbedForDelete) {
                    event.preventDefault();
                    var embedCaretTarget = removeEmbedBlock(selectedEmbedForDelete);
                    placeCaret(embedCaretTarget);
                    persistEditorState(false);
                    return;
                }
            }

            function handleEnterOnSelectedBlock() {
                var selectedImageBlock = editor.querySelector('.block.block-image.is-selected');
                if (selectedImageBlock) {
                    var nextImage = selectedImageBlock.nextElementSibling;
                    var imageTarget = nextImage && nextImage.classList.contains('block-image-break') ? nextImage : createImageBreakParagraph();
                    if (imageTarget !== nextImage) {
                        selectedImageBlock.parentNode.insertBefore(imageTarget, selectedImageBlock.nextSibling);
                    }
                    placeCaret(imageTarget);
                    persistEditorState(false);
                    return true;
                }

                var selectedEmbedBlock = editor.querySelector('.block.block-embed.is-selected');
                if (!selectedEmbedBlock) {
                    return false;
                }
                var next = selectedEmbedBlock.nextElementSibling;
                var target = next && next.tagName === 'P' ? next : createImageBreakParagraph();
                if (target !== next) {
                    selectedEmbedBlock.parentNode.insertBefore(target, selectedEmbedBlock.nextSibling);
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

            function handleEnterKey() {
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
            var embedInsertId = 'embed-insert-' + editorId + '-' + Date.now();
            var embedHtml = '<div class="block block-embed block-embed-youtube align-center" data-embed-insert-id="' + embedInsertId + '"><div class="embed-frame"><iframe src="https://www.youtube.com/embed/' + videoId + '" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"></iframe></div></div><p><br></p>';
            document.execCommand('insertHTML', false, embedHtml);
            var insertedEmbed = editor.querySelector('[data-embed-insert-id="' + embedInsertId + '"]');
            if (insertedEmbed) {
                insertedEmbed.removeAttribute('data-embed-insert-id');
                placeCaretAfterBlock(insertedEmbed);
            }
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
