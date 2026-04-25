(() => {
    const app = window.tinycms = window.tinycms || {};
    const mediaLibrary = app.mediaLibrary = app.mediaLibrary || {};
    const currentCsrf = app.support?.currentCsrf || (() => '');

    const endpointWithContentId = (value, id) => {
        const numericId = Number(id || 0);
        if (numericId <= 0) {
            return String(value || '');
        }
        return String(value || '').replace(/\/admin\/api\/v1\/content\/\d+\//, `/admin/api/v1/content/${numericId}/`);
    };

    const csrfData = (values = {}) => {
        const body = new FormData();
        body.set('_csrf', currentCsrf());
        Object.entries(values).forEach(([key, value]) => {
            body.set(key, String(value));
        });
        return body;
    };

    const mediaAction = (openTrigger, contentId, name, mediaId = 0) => {
        const attrs = {
            select: 'data-media-select-template',
            delete: 'data-media-delete-template',
            rename: 'data-media-rename-template',
            attach: 'data-media-attach-template',
            detach: 'data-media-detach-endpoint',
        };
        const attr = attrs[name] || '';
        const value = attr !== '' ? openTrigger.getAttribute(attr) || '' : '';
        return endpointWithContentId(value, contentId).replace('{mediaId}', String(Number(mediaId || 0)));
    };

    const syncContentContext = (openTrigger, uploadForm, endpoint, id) => {
        const numericId = Number(id || 0);
        if (numericId <= 0) {
            return endpoint;
        }

        const nextEndpoint = endpointWithContentId(endpoint, numericId);
        openTrigger.setAttribute('data-media-library-endpoint', endpointWithContentId(openTrigger.getAttribute('data-media-library-endpoint') || '', numericId));
        [
            'data-media-select-template',
            'data-media-delete-template',
            'data-media-rename-template',
            'data-media-attach-template',
            'data-media-detach-endpoint',
        ].forEach((attr) => {
            openTrigger.setAttribute(attr, endpointWithContentId(openTrigger.getAttribute(attr) || '', numericId));
        });

        if (uploadForm) {
            uploadForm.action = endpointWithContentId(uploadForm.action, numericId) || uploadForm.action;
            uploadForm.querySelectorAll('input[name="content_id"]').forEach((node) => {
                node.value = String(numericId);
            });
        }

        return nextEndpoint;
    };

    mediaLibrary.transport = {
        csrfData,
        endpointWithContentId,
        mediaAction,
        syncContentContext,
    };
})();
