(function () {
    var t = window.tinycms?.i18n?.t || function () { return ''; };

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

    function createImageBreakParagraph() {
        var paragraph = document.createElement('p');
        paragraph.className = 'block-image-break';
        paragraph.innerHTML = '<br>';
        return paragraph;
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

    function serializeEditorHtml(editor) {
        var normalizeHtml = window.tinycmsEditorUtils?.normalizeHtml || function (html) { return html; };
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

    window.tinycmsEditorImageBlocks = {
        createLoadingImageBlock: createLoadingImageBlock,
        applyImageAlignment: applyImageAlignment,
        ensureImageBlock: ensureImageBlock,
        enhanceImageBlocks: enhanceImageBlocks,
        createImageBreakParagraph: createImageBreakParagraph,
        normalizeBlocks: normalizeBlocks,
        serializeEditorHtml: serializeEditorHtml,
        sync: sync,
    };
})();
