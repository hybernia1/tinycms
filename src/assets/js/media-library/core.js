(() => {
    const app = window.tinycms = window.tinycms || {};
    const mediaLibrary = app.mediaLibrary = app.mediaLibrary || {};
    const t = app.i18n?.t || (() => '');
    const icon = app.icons?.icon || (() => '');
    const esc = app.support?.esc || ((value) => String(value || ''));
    const currentCsrf = app.support?.currentCsrf || (() => '');

    const uploadAction = (openTrigger) => {
        const endpoint = openTrigger?.getAttribute('data-media-library-endpoint') || '';
        return endpoint.replace(/\/media(?:\?.*)?$/, '/media/upload');
    };

    const createModal = (openTrigger) => {
        if (!openTrigger) {
            return null;
        }

        const uploadId = 'content-thumbnail-upload';
        const uploadTypes = openTrigger.getAttribute('data-media-upload-types-label') || '';
        const allowedTypes = uploadTypes !== ''
            ? t('common.allowed_upload_types').replace('%s', uploadTypes)
            : '';
        const node = document.createElement('div');
        node.className = 'media-library-modal modal-overlay';
        node.setAttribute('data-media-library-modal', '');
        node.setAttribute('data-media-library-per-page', openTrigger.getAttribute('data-media-library-per-page') || '10');
        node.innerHTML = `
        <div class="media-library-modal-dialog modal">
            <div class="media-library-modal-header">
                <strong>${esc(t('admin.menu.media'))}</strong>
                <button class="btn btn-light btn-icon" type="button" data-ui-modal-close aria-label="${esc(t('common.close'))}">
                    ${icon('cancel')}
                </button>
            </div>
            <div class="media-library-modal-layout">
                <div class="media-library-detail">
                    <div class="media-library-detail-preview" data-media-library-detail-preview></div>
                    <div class="media-library-detail-meta">
                        <div>
                            <label>${esc(t('common.name'))}</label>
                            <div class="d-flex gap-2">
                                <input type="text" value="" data-media-library-detail-name-input>
                                <button class="btn btn-light" type="button" data-media-library-rename disabled>${esc(t('common.save'))}</button>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted" data-media-library-status></small>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="button" data-media-library-choose disabled>${esc(t('content.choose'))}</button>
                        <button class="btn btn-danger" type="button" data-media-library-delete-open disabled>${esc(t('common.delete'))}</button>
                    </div>
                </div>
                <div class="media-library-list">
                    <form class="media-library-search" data-media-library-search>
                        <div class="search-field field-with-icon">
                            <input class="search-input" type="search" name="q" placeholder="${esc(t('content.search_image'))}">
                            <span class="field-overlay field-overlay-end field-icon field-icon-soft" aria-hidden="true">${icon('search')}</span>
                        </div>
                    </form>
                    <form class="media-library-upload" method="post" enctype="multipart/form-data" action="${esc(uploadAction(openTrigger))}" data-media-library-upload-form>
                        <input type="hidden" name="_csrf" value="${esc(currentCsrf())}">
                        <input type="hidden" name="content_id" value="">
                        <div class="custom-upload-field" data-media-library-upload-field>
                            <label class="btn btn-light custom-upload-button" for="${uploadId}">
                                <span class="custom-upload-main-icon" data-custom-upload-icon>${icon('upload')}</span>
                                <span class="custom-upload-label" data-custom-upload-label data-default-label="${esc(t('common.upload_add_files'))}">${esc(t('common.upload_add_files'))}</span>
                                <span class="custom-upload-spinner" data-custom-upload-spinner aria-hidden="true">${icon('loader')}</span>
                            </label>
                            <input id="${uploadId}" type="file" name="thumbnail" accept="${esc(openTrigger.getAttribute('data-media-upload-accept') || '')}" required>
                        </div>
                        ${allowedTypes !== '' ? `<small class="text-muted d-block mt-2">${esc(allowedTypes)}</small>` : ''}
                    </form>
                    <div class="media-library-grid" data-media-library-grid></div>
                    <div class="pagination pagination-centered">
                        <a class="pagination-link disabled" href="#" data-media-library-prev aria-disabled="true" tabindex="-1">${icon('prev')}<span>${esc(t('common.previous'))}</span></a>
                        <a class="pagination-link disabled" href="#" data-media-library-next aria-disabled="true" tabindex="-1"><span>${esc(t('common.next'))}</span>${icon('next')}</a>
                    </div>
                </div>
            </div>
        </div>
    `;
        document.body.appendChild(node);
        return node;
    };

    mediaLibrary.template = {
        createModal,
        currentCsrf,
    };
})();

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

(() => {
    const app = window.tinycms = window.tinycms || {};
    const mediaLibrary = app.mediaLibrary = app.mediaLibrary || {};
    const t = app.i18n?.t || (() => '');
    const icon = app.icons?.icon || (() => '');

    const updateUploadLabel = (uploadInput, uploadLabel) => {
        if (!uploadInput || !uploadLabel) {
            return;
        }
        const defaultLabel = uploadLabel.getAttribute('data-default-label') || uploadLabel.textContent || '';
        const files = Array.from(uploadInput.files || []);
        if (files.length === 0) {
            uploadLabel.textContent = defaultLabel;
            uploadLabel.removeAttribute('title');
            return;
        }
        const text = files.length === 1 ? files[0].name : `${files[0].name} +${files.length - 1}`;
        uploadLabel.textContent = text;
        uploadLabel.setAttribute('title', text);
    };

    const renderItems = (grid, items, absoluteUrl) => {
        if (!grid) {
            return;
        }

        if (!Array.isArray(items) || items.length === 0) {
            grid.innerHTML = `<p class="text-muted m-0">${t('media.no_results')}</p>`;
            return;
        }

        grid.innerHTML = '';
        items.forEach((item) => {
            const name = String(item.name || t('media.untitled'));
            const previewPath = String(item.preview_path || '');
            const button = document.createElement('button');
            button.className = 'media-library-card';
            button.type = 'button';
            button.setAttribute('data-media-library-select', String(Number(item.id || 0)));

            const imageWrap = document.createElement('div');
            imageWrap.className = 'media-library-card-image';

            if (previewPath !== '') {
                const image = document.createElement('img');
                image.src = absoluteUrl(previewPath);
                image.alt = name;
                imageWrap.appendChild(image);
            } else {
                const empty = document.createElement('div');
                empty.className = 'media-library-card-empty';
                imageWrap.appendChild(empty);
            }

            const check = document.createElement('span');
            check.className = 'media-library-card-check';
            check.innerHTML = icon('check');

            button.appendChild(imageWrap);
            button.appendChild(check);
            button.dataset.mediaName = name;
            button.dataset.mediaPath = String(item.path || '');
            button.dataset.mediaCreated = String(item.created || '');
            button.dataset.mediaCreatedLabel = String(item.created_label || item.created || '');
            button.dataset.mediaPreviewPath = previewPath;
            button.dataset.mediaCanEdit = item.can_edit === true ? '1' : '0';
            button.dataset.mediaCanDelete = item.can_delete === true ? '1' : '0';
            button.dataset.mediaWebpPath = String(item.webp_path || '');
            grid.appendChild(button);
        });
    };

    const renderSelected = (nodes, selectedMedia, absoluteUrl) => {
        const { detailPreview, detailNameInput, chooseButton, deleteButton, renameButton } = nodes;
        if (detailPreview) {
            detailPreview.innerHTML = '';
            const detailImagePath = selectedMedia ? selectedMedia.previewPath : '';
            if (selectedMedia && detailImagePath !== '') {
                const image = document.createElement('img');
                image.src = absoluteUrl(detailImagePath);
                image.alt = selectedMedia.name;
                detailPreview.appendChild(image);
                const createdBadge = document.createElement('span');
                createdBadge.className = 'badge media-library-detail-created-badge';
                createdBadge.textContent = selectedMedia.createdLabel || selectedMedia.created || '-';
                detailPreview.appendChild(createdBadge);
            } else {
                detailPreview.textContent = t('media.no_preview');
            }
        }

        if (detailNameInput) {
            detailNameInput.value = selectedMedia ? selectedMedia.name : '';
            detailNameInput.disabled = !(selectedMedia && selectedMedia.canEdit);
        }

        if (chooseButton) {
            chooseButton.disabled = !selectedMedia;
        }

        if (deleteButton) {
            const canDelete = !!(selectedMedia && selectedMedia.canDelete);
            deleteButton.disabled = !canDelete;
            deleteButton.classList.toggle('d-none', !canDelete);
        }

        if (renameButton) {
            const canEdit = !!(selectedMedia && selectedMedia.canEdit);
            renameButton.disabled = !canEdit;
            renameButton.classList.toggle('d-none', !canEdit);
        }
    };

    const updatePager = (prevButton, nextButton, page, totalPages) => {
        const sync = (button, disabled) => {
            if (!button) {
                return;
            }
            button.classList.toggle('disabled', disabled);
            button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
            button.setAttribute('tabindex', disabled ? '-1' : '0');
            if ('disabled' in button) {
                button.disabled = disabled;
            }
        };

        sync(prevButton, page <= 1);
        sync(nextButton, page >= totalPages);
    };

    mediaLibrary.renderer = {
        renderItems,
        renderSelected,
        updatePager,
        updateUploadLabel,
    };
})();

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
        mode: 'thumbnail',
        endpoint: trigger.getAttribute('data-media-library-endpoint') || '',
        baseUrl: trigger.getAttribute('data-media-base-url') || '',
        currentMediaId: Number(trigger.getAttribute('data-current-media-id') || '0'),
        contentId,
    });

    mediaLibrary.helpers = {
        absoluteUrl,
        mediaFromCard,
        thumbnailDetail,
    };
})();
