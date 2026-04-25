(() => {
const app = window.tinycms = window.tinycms || {};
const editor = app.editor = app.editor || {};

const create = (options) => {
    let mediaRange = null;
    let mediaReplaceBlock = null;

    const imageHtml = (url, name, mediaId) => {
        return '<img src="' + options.escapeHtml(url) + '" alt="' + options.escapeHtml(String(name || '')) + '" data-media-id="' + Number(mediaId || 0) + '">';
    };

    const imageBlockHtml = (url, name, mediaId, insertId) => {
        return '<div class="block block-image align-center"' + options.blockInsertAttr(insertId) + '>' + imageHtml(url, name, mediaId) + '</div>';
    };

    const youtubeEmbedHtml = (videoId, insertId) => {
        return '<div class="block block-embed block-embed-youtube align-center"' + options.blockInsertAttr(insertId) + '><div class="embed-frame"><iframe src="https://www.youtube.com/embed/' + options.escapeHtml(videoId) + '" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"></iframe></div></div>';
    };

    const insertYoutubeEmbed = (videoId) => {
        const insertId = options.blockInsertId('embed');
        options.insertStandaloneHtml(youtubeEmbedHtml(videoId, insertId), insertId);
    };

    const insertPastedContent = (html, text) => {
        options.ensureSelectionInsideEditor();
        document.execCommand('defaultParagraphSeparator', false, 'p');
        if (html) {
            const cleaned = options.cleanSerializedHtml(html);
            if (cleaned) {
                document.execCommand('insertHTML', false, cleaned);
            } else if (text) {
                document.execCommand('insertText', false, text);
            }
        } else if (text) {
            document.execCommand('insertText', false, text);
        }
        options.persistEditorState(true);
    };

    const absoluteMediaUrl = (path) => {
        const value = String(path || '').trim();
        if (!value) {
            return '';
        }
        if (/^https?:\/\//i.test(value)) {
            return value;
        }
        const base = String(options.textarea.dataset.mediaBaseUrl || '').trim().replace(/\/$/, '');
        const normalized = value.charAt(0) === '/' ? value : ('/' + value);
        return base === '' ? normalized : (base + normalized);
    };

    const contentApiBase = () => {
        const form = options.editorForm();
        const draftEndpoint = String(form && form.dataset ? form.dataset.draftInitEndpoint || '' : '').trim();
        if (!draftEndpoint) {
            return '';
        }
        return draftEndpoint.replace(/\/admin\/api\/v1\/content\/draft\/init.*$/, '');
    };

    const mediaEndpointForContentId = (id) => {
        const value = Number(id || 0);
        if (value <= 0) {
            return '';
        }
        const current = String(options.textarea.dataset.mediaLibraryEndpoint || '').trim();
        if (current !== '') {
            const rewritten = current.replace(/\/admin\/api\/v1\/content\/\d+\/media(?:\/.*)?$/, '/admin/api/v1/content/' + value + '/media');
            if (rewritten !== current || /\/admin\/api\/v1\/content\/\d+\/media/.test(rewritten)) {
                return rewritten;
            }
        }
        const base = contentApiBase();
        if (base === '') {
            return '';
        }
        return base + '/admin/api/v1/content/' + value + '/media';
    };

    const setContentIdEverywhere = (id) => {
        const value = Number(id || 0);
        if (value <= 0) {
            return;
        }
        const form = options.editorForm();
        if (form) {
            form.querySelectorAll('input[name="id"]').forEach((node) => {
                node.value = String(value);
            });
            form.querySelectorAll('input[name="content_id"]').forEach((node) => {
                node.value = String(value);
            });
        }
        options.textarea.dataset.contentId = String(value);
        const mediaEndpoint = mediaEndpointForContentId(value);
        if (mediaEndpoint !== '') {
            options.textarea.dataset.mediaLibraryEndpoint = mediaEndpoint;
        }
    };

    let draftInitPromise = null;
    const ensureDraftId = () => {
        const currentId = Number(options.textarea.dataset.contentId || '0');
        if (currentId > 0) {
            return Promise.resolve(currentId);
        }
        if (draftInitPromise) {
            return draftInitPromise;
        }
        const form = options.editorForm();
        const endpoint = String(form && form.dataset ? form.dataset.draftInitEndpoint || '' : '').trim();
        const csrf = options.csrfValue();
        if (!endpoint || !csrf || typeof options.requestJson !== 'function') {
            return Promise.resolve(0);
        }

        draftInitPromise = options.requestJson(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
            },
            body: '_csrf=' + encodeURIComponent(csrf)
        }).then((result) => {
            const response = result && result.response ? result.response : null;
            const normalized = result && result.data ? result.data : null;
            const newId = response && response.ok && normalized && normalized.success && normalized.data
                ? Number(normalized.data.id || 0)
                : 0;
            if (newId > 0) {
                setContentIdEverywhere(newId);
            }
            return newId;
        }).catch(() => {
            return 0;
        }).finally(() => {
            draftInitPromise = null;
        });

        return draftInitPromise;
    };

    const waitForImageReady = (url, retries) => {
        const maxRetries = typeof retries === 'number' ? retries : 8;
        return new Promise((resolve) => {
            let attempt = 0;
            function tryLoad() {
                const image = new Image();
                image.onload = () => {
                    resolve(true);
                };
                image.onerror = () => {
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
    };

    const insertLoadingImageBlock = () => {
        let imageRange = options.isSelectionInside(options.editor) ? options.rememberSelection() : null;
        if (!imageRange) {
            options.focusEditorEnd(options.editor);
            imageRange = options.rememberSelection();
        }
        const loadingBlock = options.createLoadingImageBlock();
        if (!imageRange) {
            options.editor.appendChild(loadingBlock);
            options.placeCaretAfterBlock(loadingBlock);
            return loadingBlock;
        }
        options.restoreSelection(imageRange, options.editor);
        imageRange.deleteContents();
        imageRange.insertNode(loadingBlock);
        options.placeCaretAfterBlock(loadingBlock);
        return loadingBlock;
    };

    const uploadPastedImage = (file, loadingBlock) => {
        if (!file) {
            return;
        }
        function removeLoadingBlockAndPersist() {
            if (loadingBlock && loadingBlock.parentNode) {
                loadingBlock.remove();
                options.persistEditorState(true);
            }
        }
        ensureDraftId().then((contentId) => {
            if (contentId <= 0) {
                return null;
            }
            const endpoint = mediaEndpointForContentId(contentId);
            if (!endpoint) {
                return null;
            }
            const csrf = options.csrfValue();
            if (!csrf) {
                return null;
            }
            const data = new FormData();
            data.append('_csrf', csrf);
            data.append('content_id', String(contentId));
            data.append('thumbnail', file, file.name || 'clipboard-image.png');
            if (typeof options.postForm !== 'function') {
                return null;
            }
            return options.postForm(endpoint + '/upload', data, {
                credentials: 'same-origin',
            });
        }).then((result) => {
            const response = result && result.response ? result.response : null;
            const normalized = result && result.data ? result.data : null;
            const media = response && response.ok && normalized && normalized.success && normalized.data
                ? normalized.data
                : null;
            if (!media) {
                removeLoadingBlockAndPersist();
                return;
            }
            const mediaId = Number(media.id || 0);
            const imageUrl = absoluteMediaUrl(media.webp_path || media.preview_path || '');
            if (mediaId <= 0 || !imageUrl) {
                removeLoadingBlockAndPersist();
                return;
            }
            waitForImageReady(imageUrl).then((ready) => {
                if (!loadingBlock || !loadingBlock.parentNode) {
                    return;
                }
                if (!ready) {
                    removeLoadingBlockAndPersist();
                    return;
                }
                loadingBlock.classList.remove('is-loading');
                loadingBlock.innerHTML = imageHtml(imageUrl, media.name, mediaId);
                options.ensureImageBlock(loadingBlock);
                if (!loadingBlock.nextElementSibling || loadingBlock.nextElementSibling.tagName !== 'P') {
                    loadingBlock.parentNode.insertBefore(options.createImageBreakParagraph(), loadingBlock.nextSibling);
                }
                options.persistEditorState(true);
            }).catch(() => {
                removeLoadingBlockAndPersist();
            });
        }).catch(() => {
            removeLoadingBlockAndPersist();
        });
    };

    const openLibrary = (currentMediaId) => {
        document.dispatchEvent(new CustomEvent('tinycms:media-library-open', {
            detail: {
                mode: 'editor',
                editorId: options.editorId,
                contentId: Number(options.textarea.dataset.contentId || '0'),
                endpoint: options.textarea.dataset.mediaLibraryEndpoint || '',
                baseUrl: options.textarea.dataset.mediaBaseUrl || '',
                currentMediaId: Number(currentMediaId || 0),
            },
        }));
    };

    const imageMediaId = (block) => {
        const image = block ? block.querySelector('img[data-media-id]') : null;
        return Number(image ? image.getAttribute('data-media-id') || '0' : '0');
    };

    const openInsertLibrary = () => {
        mediaRange = options.isSelectionInside(options.editor) ? options.rememberSelection() : null;
        mediaReplaceBlock = null;
        openLibrary(0);
    };

    const openReplaceLibrary = (block) => {
        mediaRange = null;
        mediaReplaceBlock = block;
        openLibrary(imageMediaId(block));
    };

    const replaceImageBlock = (block, detail, mediaId) => {
        const image = block ? block.querySelector('img[data-media-id]') : null;
        if (!image) {
            return false;
        }
        image.src = detail.url;
        image.alt = String(detail.name || '');
        image.setAttribute('data-media-id', String(mediaId));
        image.style.width = block.style.width === '' ? '' : '100%';
        options.ensureImageBlock(block);
        options.selectImageBlock(block);
        options.persistEditorState(true);
        return true;
    };

    const handleLibrarySelected = (event) => {
        const detail = event.detail || {};
        if (detail.editorId !== options.editorId || !detail.url) {
            return;
        }
        const mediaId = Number(detail.id || 0);
        if (mediaId <= 0) {
            return;
        }
        if (mediaReplaceBlock && options.editor.contains(mediaReplaceBlock)) {
            if (replaceImageBlock(mediaReplaceBlock, detail, mediaId)) {
                mediaReplaceBlock = null;
                return;
            }
        }
        mediaReplaceBlock = null;
        if (mediaRange) {
            options.restoreSelection(mediaRange, options.editor);
        } else {
            options.focusEditorEnd(options.editor);
        }
        const insertId = options.blockInsertId('image');
        options.insertStandaloneHtml(imageBlockHtml(detail.url, detail.name, mediaId, insertId), insertId);
    };

    const handlePaste = (event) => {
        if (options.htmlMode()) {
            return;
        }
        const pastedImage = options.findPastedImageFile(event);
        if (pastedImage) {
            event.preventDefault();
            const loadingBlock = insertLoadingImageBlock();
            options.sync(options.textarea, options.editor);
            uploadPastedImage(pastedImage, loadingBlock);
            return;
        }
        const clipboard = event.clipboardData;
        const text = clipboard ? clipboard.getData('text/plain') : '';
        const html = clipboard ? clipboard.getData('text/html') : '';
        if (!html) {
            const videoId = options.extractYoutubeVideoId(text);
            if (videoId) {
                event.preventDefault();
                insertYoutubeEmbed(videoId);
                return;
            }
            const pastedUrl = options.extractPastedUrl(text);
            if (pastedUrl) {
                event.preventDefault();
                options.insertPastedLink(pastedUrl);
                return;
            }
        }
        if (html || text) {
            event.preventDefault();
            insertPastedContent(html, text);
        }
    };

    return {
        handleLibrarySelected,
        handlePaste,
        openInsertLibrary,
        openReplaceLibrary,
    };
};

editor.media = { create };
})();
