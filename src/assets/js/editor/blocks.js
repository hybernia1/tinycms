(() => {
const app = window.tinycms = window.tinycms || {};
const editor = app.editor = app.editor || {};
const t = app.i18n?.t || (() => '');
const iconSvg = (name) => app.icons?.icon?.(name, '') || '';
const cleanSerializedHtml = editor.sanitize?.cleanSerializedHtml || ((html) => String(html || '').trim());
const imageResizePositions = ['tl', 't', 'tr', 'r', 'br', 'b', 'bl', 'l'];

const translate = (key, fallback) => t(key, fallback) || fallback;

const createLoadingImageBlock = () => {
    const block = document.createElement('div');
    block.className = 'block block-image align-center is-loading';
    block.innerHTML = '<div class="image-upload-loading" contenteditable="false">' + iconSvg('loader') + '</div>';
    return block;
};

const createBlockToolButton = (icon, title, attributes) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'image-toolbar-btn';
    button.title = title;
    button.setAttribute('aria-label', title);
    Object.keys(attributes || {}).forEach((name) => {
        button.setAttribute(name, attributes[name]);
    });
    button.innerHTML = iconSvg(icon);
    return button;
};

const createImageToolbar = () => {
    const toolbar = document.createElement('div');
    const items = [
        ['w-align-left', translate('editor.align_left', 'Left'), {'data-image-align': 'left'}],
        ['w-align-center', translate('editor.align_center', 'Center'), {'data-image-align': 'center'}],
        ['w-align-right', translate('editor.align_right', 'Right'), {'data-image-align': 'right'}],
        ['w-align-justify', translate('editor.image_full_width', 'Full width'), {'data-image-action': 'full'}],
        ['concept', translate('editor.image_replace', 'Replace image'), {'data-image-action': 'replace'}],
        ['delete', translate('editor.image_delete', 'Delete image'), {'data-image-action': 'delete'}],
    ];
    toolbar.className = 'image-toolbar';
    toolbar.setAttribute('contenteditable', 'false');
    items.forEach((item) => {
        toolbar.appendChild(createBlockToolButton(item[0], item[1], item[2]));
    });
    return toolbar;
};

const createEmbedToolbar = () => {
    const toolbar = document.createElement('div');
    const items = [
        ['w-align-left', translate('editor.align_left', 'Left'), {'data-embed-align': 'left'}],
        ['w-align-center', translate('editor.align_center', 'Center'), {'data-embed-align': 'center'}],
        ['w-align-right', translate('editor.align_right', 'Right'), {'data-embed-align': 'right'}],
        ['w-align-justify', translate('editor.image_full_width', 'Full width'), {'data-embed-action': 'full'}],
        ['delete', translate('editor.image_delete', 'Delete'), {'data-embed-action': 'delete'}],
    ];
    toolbar.className = 'embed-toolbar';
    toolbar.setAttribute('contenteditable', 'false');
    items.forEach((item) => {
        toolbar.appendChild(createBlockToolButton(item[0], item[1], item[2]));
    });
    return toolbar;
};

const createImageResizeHandle = (position) => {
    const handle = document.createElement('span');
    handle.className = 'image-resize-handle image-resize-handle-' + position;
    handle.setAttribute('contenteditable', 'false');
    handle.setAttribute('data-image-resize', position);
    return handle;
};

const createImageSelectionFrame = () => {
    const frame = document.createElement('span');
    frame.className = 'image-selection-frame';
    frame.setAttribute('contenteditable', 'false');
    return frame;
};

const createEmbedSelectionFrame = () => {
    const frame = document.createElement('span');
    frame.className = 'embed-selection-frame';
    frame.setAttribute('contenteditable', 'false');
    return frame;
};

const blockAlignment = (block) => {
    if (block.classList.contains('align-left')) {
        return 'left';
    }
    if (block.classList.contains('align-right')) {
        return 'right';
    }
    return 'center';
};

const syncToolbarState = (block, alignAttribute, fullAttribute) => {
    block.querySelectorAll('[' + alignAttribute + ']').forEach((button) => {
        const active = button.getAttribute(alignAttribute) === blockAlignment(block);
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    block.querySelectorAll('[' + fullAttribute + '="full"]').forEach((button) => {
        const active = String(block.style.width || '').trim() === '100%';
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
};

const syncImageToolbarState = (block) => {
    syncToolbarState(block, 'data-image-align', 'data-image-action');
};

const syncEmbedToolbarState = (block) => {
    syncToolbarState(block, 'data-embed-align', 'data-embed-action');
};

const placeToolbar = (block, editor, selector, belowClass) => {
    if (!block || !editor) {
        return;
    }
    const toolbar = block.querySelector(selector);
    if (!toolbar) {
        return;
    }
    block.classList.remove(belowClass);
    const blockRect = block.getBoundingClientRect();
    const editorRect = editor.getBoundingClientRect();
    const toolbarHeight = toolbar.offsetHeight || 38;
    const gap = 8;
    const hasTopSpace = blockRect.top - toolbarHeight - gap >= editorRect.top;
    block.classList.toggle(belowClass, !hasTopSpace);
};

const placeImageToolbar = (block, editor) => {
    placeToolbar(block, editor, '.image-toolbar', 'image-toolbar-below');
};

const placeEmbedToolbar = (block, editor) => {
    placeToolbar(block, editor, '.embed-toolbar', 'embed-toolbar-below');
};

const applyBlockAlignment = (block, align, syncState) => {
    if (!block) {
        return;
    }
    const value = ['left', 'center', 'right'].indexOf(align) >= 0 ? align : 'center';
    block.classList.remove('align-left', 'align-center', 'align-right');
    block.classList.add('align-' + value);
    syncState(block);
};

const applyImageAlignment = (block, align) => {
    applyBlockAlignment(block, align, syncImageToolbarState);
};

const applyEmbedAlignment = (block, align) => {
    applyBlockAlignment(block, align, syncEmbedToolbarState);
};

const ensureImageBlock = (block) => {
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
        block.querySelectorAll('.image-resize-handle').forEach((node) => {
            node.remove();
        });
        imageResizePositions.forEach((position) => {
            block.appendChild(createImageResizeHandle(position));
        });
    }

    const image = block.querySelector('img[data-media-id]');
    if (image && block.style.width === '' && image.style.width !== '') {
        block.style.width = image.style.width;
        image.style.width = '100%';
    }
    syncImageToolbarState(block);
};

const ensureEmbedBlock = (block) => {
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
};

const enhanceImageBlocks = (editor) => {
    const images = Array.prototype.slice.call(editor.querySelectorAll('img[data-media-id]'));
    images.forEach((image) => {
        let block = image.closest('.block.block-image');
        if (!block) {
            block = document.createElement('div');
            block.className = 'block block-image align-center';
            const parent = image.parentNode;
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
};

const enhanceEmbedBlocks = (editor) => {
    editor.querySelectorAll('.block.block-embed').forEach((block) => {
        ensureEmbedBlock(block);
    });
};

const serializeEditorHtml = (editor) => {
    const clone = editor.cloneNode(true);
    clone.querySelectorAll('.image-toolbar, .image-resize-handle, .image-selection-frame, .embed-toolbar, .embed-selection-frame').forEach((node) => {
        node.remove();
    });
    clone.querySelectorAll('.block.block-image, .block.block-embed').forEach((block) => {
        block.classList.remove('is-selected');
    });
    return cleanSerializedHtml(clone.innerHTML);
};

const sync = (textarea, editor) => {
    textarea.value = serializeEditorHtml(editor);
};

const createImageBreakParagraph = () => {
    const paragraph = document.createElement('p');
    paragraph.className = 'block-image-break';
    paragraph.innerHTML = '<br>';
    return paragraph;
};

const isEditableTextBlock = (node) => {
    if (!node || node.nodeType !== Node.ELEMENT_NODE) {
        return false;
    }
    if (/^(P|H1|H2|H3|H4|H5|H6|BLOCKQUOTE)$/.test(node.tagName)) {
        return true;
    }
    return node.classList.contains('block') && node.classList.contains('block-list');
};

const isStandaloneBlock = (node) => {
    if (!node || node.nodeType !== Node.ELEMENT_NODE) {
        return false;
    }
    if (node.tagName === 'HR') {
        return true;
    }
    return node.classList.contains('block')
        && (node.classList.contains('block-image') || node.classList.contains('block-embed'));
};

const ensureStandaloneBlockParagraphs = (editor) => {
    Array.prototype.slice.call(editor.children).forEach((node) => {
        if (!isStandaloneBlock(node) || isEditableTextBlock(node.nextElementSibling)) {
            return;
        }
        const paragraph = document.createElement('p');
        paragraph.innerHTML = '<br>';
        editor.insertBefore(paragraph, node.nextSibling);
    });
};

const normalizeBlocks = (editor) => {
    const nodes = Array.prototype.slice.call(editor.childNodes);
    nodes.forEach((node) => {
        if (node.nodeType === Node.TEXT_NODE && node.textContent && node.textContent.trim() !== '') {
            const paragraph = document.createElement('p');
            paragraph.textContent = node.textContent;
            editor.replaceChild(paragraph, node);
            return;
        }

        if (node.nodeType !== Node.ELEMENT_NODE) {
            return;
        }

        if (node.tagName === 'UL' || node.tagName === 'OL') {
            const listWrapper = document.createElement('div');
            listWrapper.className = 'block block-list';
            editor.replaceChild(listWrapper, node);
            listWrapper.appendChild(node);
            return;
        }

        if (node.tagName !== 'DIV') {
            return;
        }

        const childList = node.firstElementChild;
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

        const paragraph = document.createElement('p');
        paragraph.innerHTML = node.innerHTML;
        editor.replaceChild(paragraph, node);
    });
    ensureStandaloneBlockParagraphs(editor);
};

editor.blocks = {
    applyEmbedAlignment,
    applyImageAlignment,
    createImageBreakParagraph,
    createLoadingImageBlock,
    enhanceEmbedBlocks,
    enhanceImageBlocks,
    ensureEmbedBlock,
    ensureImageBlock,
    normalizeBlocks,
    placeEmbedToolbar,
    placeImageToolbar,
    sync,
    syncEmbedToolbarState,
    syncImageToolbarState,
};
})();
