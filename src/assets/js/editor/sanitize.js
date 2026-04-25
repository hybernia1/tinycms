(() => {
const app = window.tinycms = window.tinycms || {};
const editor = app.editor = app.editor || {};

const normalizeHtml = (html) => html === '<br>' ? '' : html;

const unwrapNode = (node) => {
    const parent = node.parentNode;
    if (!parent) {
        return;
    }
    while (node.firstChild) {
        parent.insertBefore(node.firstChild, node);
    }
    parent.removeChild(node);
};

const renameNode = (node, tagName) => {
    const replacement = document.createElement(tagName);
    while (node.firstChild) {
        replacement.appendChild(node.firstChild);
    }
    node.parentNode.replaceChild(replacement, node);
    return replacement;
};

const normalizeLinkUrl = (url) => {
    const value = String(url || '').trim();
    if (!value) {
        return '';
    }
    if (/^(mailto:|tel:|https?:\/\/|\/|#)/i.test(value)) {
        return value;
    }
    return 'https://' + value.replace(/^\/+/, '');
};

const safeUrl = (value, allowRelative) => {
    const url = String(value || '').trim();
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
};

const safeHref = (value) => {
    const url = String(value || '').trim();
    if (!url || (/^[a-zA-Z][a-zA-Z\d+\-.]*:/.test(url) && !/^(https?:|mailto:|tel:)/i.test(url))) {
        return '';
    }
    return safeUrl(normalizeLinkUrl(url), true);
};

const safeYoutubeEmbedUrl = (value) => {
    const url = String(value || '').trim();
    if (!url) {
        return '';
    }
    try {
        const parsed = /^https?:\/\//i.test(url) ? new URL(url) : new URL(url, window.location.origin);
        const host = parsed.hostname.toLowerCase().replace(/^www\./, '').replace(/^m\./, '');
        const match = parsed.pathname.match(/^\/embed\/([a-zA-Z0-9_-]{11})$/);
        if ((host === 'youtube.com' || host === 'youtube-nocookie.com') && match) {
            return 'https://www.youtube.com/embed/' + match[1];
        }
    } catch (error) {
        return '';
    }
    return '';
};

const safeWidthStyle = (value) => {
    const match = String(value || '').match(/(?:^|;)\s*width\s*:\s*(\d+(?:\.\d+)?)%\s*(?:;|$)/i);
    if (!match) {
        return '';
    }
    const width = Math.max(10, Math.min(100, Number(match[1])));
    return 'width: ' + width.toFixed(2).replace(/\.00$/, '') + '%;';
};

const isEmptyTextBlock = (node) => {
    if (!node) {
        return false;
    }
    if (node.querySelector('img, iframe, hr, video, audio, table')) {
        return false;
    }
    const text = (node.textContent || '').replace(/\u00a0/g, ' ').trim();
    if (text !== '') {
        return false;
    }
    const html = String(node.innerHTML || '').replace(/\u00a0/g, ' ').replace(/\s+/g, '').toLowerCase();
    return html === '' || html === '<br>' || html === '<br/>';
};

const normalizeTextNodes = (root) => {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    const nodes = [];
    let node = walker.nextNode();
    while (node) {
        nodes.push(node);
        node = walker.nextNode();
    }
    nodes.forEach((textNode) => {
        textNode.textContent = textNode.textContent.replace(/\u00a0/g, ' ');
    });
};

const blockedCleanTags = ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'option'];
const allowedCleanTags = ['p', 'br', 'strong', 'em', 'a', 'ul', 'ol', 'li', 'blockquote', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'img', 'iframe', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col'];

const cleanElement = (node) => {
    Array.prototype.slice.call(node.children).forEach(cleanElement);

    let tag = node.tagName.toLowerCase();
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

    const values = {};
    Array.prototype.slice.call(node.attributes).forEach((attribute) => {
        values[attribute.name.toLowerCase()] = attribute.value;
        node.removeAttribute(attribute.name);
    });

    if (tag === 'a') {
        const href = safeHref(values.href || '');
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
        const src = safeUrl(values.src || '', true);
        const mediaId = String(values['data-media-id'] || '').trim();
        if (!src) {
            node.remove();
            return;
        }
        node.setAttribute('src', src);
        node.setAttribute('alt', String(values.alt || '').trim());
        if (/^\d+$/.test(mediaId)) {
            node.setAttribute('data-media-id', mediaId);
        }
        const imageStyle = safeWidthStyle(values.style || '');
        if (imageStyle) {
            node.setAttribute('style', imageStyle);
        }
        return;
    }

    if (tag === 'iframe') {
        const iframeSrc = safeYoutubeEmbedUrl(values.src || '');
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
        const colspan = String(values.colspan || '').trim();
        const rowspan = String(values.rowspan || '').trim();
        if (/^\d+$/.test(colspan) && Number(colspan) > 1) {
            node.setAttribute('colspan', colspan);
        }
        if (/^\d+$/.test(rowspan) && Number(rowspan) > 1) {
            node.setAttribute('rowspan', rowspan);
        }
        return;
    }

    if (tag !== 'div') {
        return;
    }

    const className = ' ' + (values.class || '') + ' ';
    if (className.indexOf(' block-list ') >= 0) {
        unwrapNode(node);
        return;
    }
    if (className.indexOf(' block-image ') >= 0) {
        const align = className.indexOf(' align-left ') >= 0 ? 'left' : (className.indexOf(' align-right ') >= 0 ? 'right' : 'center');
        node.className = 'block block-image align-' + align;
        const blockStyle = safeWidthStyle(values.style || '');
        if (blockStyle) {
            node.setAttribute('style', blockStyle);
        }
        return;
    }
    if (className.indexOf(' block-embed-youtube ') >= 0) {
        const embedAlign = className.indexOf(' align-left ') >= 0 ? 'left' : (className.indexOf(' align-right ') >= 0 ? 'right' : 'center');
        node.className = 'block block-embed block-embed-youtube align-' + embedAlign;
        const embedStyle = safeWidthStyle(values.style || '');
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
};

const cleanSerializedHtml = (html) => {
    const template = document.createElement('template');
    template.innerHTML = String(html || '');
    Array.prototype.slice.call(template.content.childNodes).forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
            cleanElement(node);
        } else if (node.nodeType !== Node.TEXT_NODE || node.textContent.trim() === '') {
            node.remove();
        }
    });
    normalizeTextNodes(template.content);
    template.content.querySelectorAll('p.block-image-break').forEach((node) => {
        node.removeAttribute('class');
    });
    template.content.querySelectorAll('.block.block-image').forEach((node) => {
        if (!node.querySelector('img')) {
            node.remove();
        }
    });
    template.content.querySelectorAll('.block.block-embed').forEach((node) => {
        if (!node.querySelector('iframe')) {
            node.remove();
        }
    });
    template.content.querySelectorAll('p').forEach((node) => {
        if (isEmptyTextBlock(node)) {
            node.remove();
        }
    });
    return normalizeHtml(template.innerHTML.trim());
};

const extractYoutubeVideoId = (value) => {
    const raw = String(value || '').trim();
    if (!raw) {
        return null;
    }
    if (/^[a-zA-Z0-9_-]{11}$/.test(raw)) {
        return raw;
    }

    let normalized = raw;
    if (!/^[a-zA-Z][a-zA-Z\d+\-.]*:\/\//.test(normalized)) {
        normalized = 'https://' + normalized.replace(/^\/+/, '');
    }

    let parsed = null;
    try {
        parsed = new URL(normalized);
    } catch (error) {
        return null;
    }

    const host = parsed.hostname.toLowerCase().replace(/^www\./, '').replace(/^m\./, '');
    let id = '';
    if (host === 'youtu.be') {
        id = (parsed.pathname.split('/')[1] || '').trim();
    } else if (host === 'youtube.com' || host === 'youtube-nocookie.com') {
        if (parsed.pathname === '/watch') {
            id = (parsed.searchParams.get('v') || '').trim();
        } else {
            const pathMatch = parsed.pathname.match(/^\/(embed|shorts|live)\/([^/?#]+)/);
            id = pathMatch ? pathMatch[2].trim() : '';
        }
    }

    return /^[a-zA-Z0-9_-]{11}$/.test(id) ? id : null;
};

const escapeHtml = app.support?.esc || ((value) => String(value || ''));

const extractPastedUrl = (value) => {
    const raw = String(value || '').trim();
    if (!raw || /\s/.test(raw)) {
        return '';
    }
    const normalized = normalizeLinkUrl(raw);
    if (!/^https?:\/\//i.test(normalized)) {
        return '';
    }
    try {
        new URL(normalized);
    } catch (error) {
        return '';
    }
    return normalized;
};

const findPastedImageFile = (event) => {
    const clipboard = event.clipboardData;
    if (!clipboard || !clipboard.items) {
        return null;
    }
    for (let i = 0; i < clipboard.items.length; i += 1) {
        const item = clipboard.items[i];
        if (item && item.kind === 'file' && /^image\//i.test(item.type || '')) {
            return item.getAsFile();
        }
    }
    return null;
};

editor.sanitize = {
    cleanSerializedHtml,
    escapeHtml,
    extractPastedUrl,
    extractYoutubeVideoId,
    findPastedImageFile,
    isEmptyTextBlock,
    normalizeLinkUrl,
};
})();
