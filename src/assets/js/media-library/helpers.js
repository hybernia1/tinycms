(() => {
const absoluteUrl = (path, baseUrl) => {
    if (!path) {
        return '';
    }

    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    const root = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl;
    const normalized = path.startsWith('/') ? path : `/${path}`;
    return `${root}${normalized}`;
};

const mediaFromCard = (target, fallbackName) => ({
    id: Number(target.getAttribute('data-media-library-select') || '0'),
    name: target.dataset.mediaName || fallbackName,
    path: target.dataset.mediaPath || '',
    created: target.dataset.mediaCreated || '',
    createdLabel: target.dataset.mediaCreatedLabel || target.dataset.mediaCreated || '',
    previewPath: target.dataset.mediaPreviewPath || '',
    webpPath: target.dataset.mediaWebpPath || '',
    canEdit: target.dataset.mediaCanEdit === '1',
    canDelete: target.dataset.mediaCanDelete === '1',
});

const thumbnailDetail = (trigger, contentId) => ({
    mode: 'thumbnail',
    endpoint: trigger.getAttribute('data-media-library-endpoint') || '',
    baseUrl: trigger.getAttribute('data-media-base-url') || '',
    currentMediaId: Number(trigger.getAttribute('data-current-media-id') || '0'),
    contentId,
});

window.tinycms = window.tinycms || {};
window.tinycms.mediaLibrary = window.tinycms.mediaLibrary || {};
window.tinycms.mediaLibrary.helpers = {
    absoluteUrl,
    mediaFromCard,
    thumbnailDetail,
};
})();
