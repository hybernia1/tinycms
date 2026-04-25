(() => {
    const app = window.tinycms = window.tinycms || {};
    const mediaLibrary = app.mediaLibrary = app.mediaLibrary || {};

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
        mode: trigger.getAttribute('data-media-library-mode') || 'thumbnail',
        endpoint: trigger.getAttribute('data-media-library-endpoint') || '',
        baseUrl: trigger.getAttribute('data-media-base-url') || '',
        currentMediaId: Number(trigger.getAttribute('data-current-media-id') || '0'),
        currentMediaPath: trigger.getAttribute('data-current-media-path') || '',
        targetInput: trigger.getAttribute('data-media-target-input') || '',
        allowDelete: trigger.getAttribute('data-media-library-allow-delete') !== '0',
        allowRename: trigger.getAttribute('data-media-library-allow-rename') !== '0',
        contentId,
    });

    mediaLibrary.helpers = {
        absoluteUrl,
        mediaFromCard,
        thumbnailDetail,
    };
})();
