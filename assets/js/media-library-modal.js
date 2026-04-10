const modal = document.querySelector('[data-media-library-modal]');
const i18n = window.tinycmsI18n || {};
const t = (path, fallback = '') => {
    const value = path.split('.').reduce((acc, key) => (acc && Object.prototype.hasOwnProperty.call(acc, key) ? acc[key] : undefined), i18n);
    return typeof value === 'string' && value !== '' ? value : fallback;
};
const iconSprite = (() => {
    const iconUse = document.querySelector('svg use[href*="#icon-"]');
    return iconUse ? String(iconUse.getAttribute('href') || '').split('#')[0] : '';
})();
const icon = (name) => iconSprite !== ''
    ? `<svg class="icon" aria-hidden="true" focusable="false"><use href="${iconSprite}#icon-${name}"></use></svg>`
    : '';
const openTrigger = Array.prototype.find.call(
    document.querySelectorAll('[data-media-library-open]'),
    (node) => node.getAttribute('data-media-library-mode') !== 'editor',
) || null;

if (modal && openTrigger) {
    const modalService = window.tinycmsModal || null;
    const loader = window.tinycmsLoader || null;
    const grid = modal.querySelector('[data-media-library-grid]');
    const prevButton = modal.querySelector('[data-media-library-prev]');
    const nextButton = modal.querySelector('[data-media-library-next]');
    const closeButtons = modal.querySelectorAll('[data-media-library-close]');
    const searchForm = modal.querySelector('[data-media-library-search]');
    const uploadForm = modal.querySelector('[data-media-library-upload-form]');
    const uploadField = modal.querySelector('[data-media-library-upload-field]');
    const uploadInput = uploadForm ? uploadForm.querySelector('input[type="file"]') : null;
    const detachButton = document.querySelector('[data-media-library-detach]');
    const detachWrap = document.querySelector('[data-media-library-detach-wrap]');
    const detachForm = document.querySelector('[data-media-library-detach-form]');
    const selectForm = document.querySelector('[data-media-library-select-form]');
    const mediaIdField = document.querySelector('[data-media-library-media-id]');
    const deleteMediaIdField = document.querySelector('[data-media-library-delete-media-id]');
    const deleteForm = document.getElementById('media-library-delete-form');
    const deleteConfirmButton = document.querySelector('[data-media-library-delete-confirm]');
    const deleteConfirmModal = document.getElementById('media-library-delete-modal');
    const detailPreview = modal.querySelector('[data-media-library-detail-preview]');
    const detailNameInput = modal.querySelector('[data-media-library-detail-name-input]');
    const detailCreated = modal.querySelector('[data-media-library-detail-created]');
    const chooseButton = modal.querySelector('[data-media-library-choose]');
    const deleteButton = modal.querySelector('[data-media-library-delete-open]');
    const renameButton = modal.querySelector('[data-media-library-rename]');
    const status = modal.querySelector('[data-media-library-status]');
    const renameForm = document.querySelector('[data-media-library-rename-form]');
    const renameMediaId = document.querySelector('[data-media-library-rename-media-id]');
    const renameName = document.querySelector('[data-media-library-rename-name]');

    let endpoint = '';
    let baseUrl = '';
    let currentMediaId = 0;
    let mode = 'thumbnail';
    let editorId = '';
    let contentId = 0;
    let page = 1;
    let totalPages = 1;
    let query = '';
    const configuredPerPage = Number(modal.getAttribute('data-media-library-per-page') || '10');
    const perPage = Number.isFinite(configuredPerPage) && configuredPerPage > 0
        ? Math.min(Math.floor(configuredPerPage), 50)
        : 10;
    let selectedMedia = null;
    let searchTimer = null;

    if (modalService) {
        modalService.register('media-library-modal', {
            element: modal,
            closeSelector: '[data-media-library-close]',
            closeOnBackdrop: true,
        });
        if (deleteConfirmModal) {
            modalService.register('media-library-delete-modal', {
                element: deleteConfirmModal,
                closeSelector: '[data-modal-close]',
                confirmSelector: '[data-modal-confirm]',
                closeOnBackdrop: true,
            });
        }
    }

    const normalizePayload = (payload) => {
        if (payload && Object.prototype.hasOwnProperty.call(payload, 'ok')) {
            return {
                success: payload.ok === true,
                message: String(payload.error?.message || ''),
                data: payload.data,
                meta: payload.meta || {},
            };
        }

        return {
            success: payload?.success === true || !Object.prototype.hasOwnProperty.call(payload || {}, 'success'),
            message: String(payload?.message || ''),
            data: payload,
            meta: payload || {},
        };
    };

    const resolveAction = (form, mediaId) => {
        const template = form?.getAttribute('data-action-template') || '';
        if (template !== '' && Number(mediaId) > 0) {
            return template.replace('{mediaId}', String(Number(mediaId)));
        }
        return form?.action || '';
    };

    const endpointWithContentId = (value, id) => {
        const numericId = Number(id || 0);
        if (numericId <= 0) {
            return String(value || '');
        }
        return String(value || '').replace(/\/admin\/api\/v1\/content\/\d+\//, `/admin/api/v1/content/${numericId}/`);
    };

    const syncContentContext = (id) => {
        const numericId = Number(id || 0);
        if (numericId <= 0) {
            return;
        }

        contentId = numericId;
        endpoint = endpointWithContentId(endpoint, numericId);

        if (openTrigger) {
            openTrigger.setAttribute('data-media-library-endpoint', endpointWithContentId(openTrigger.getAttribute('data-media-library-endpoint') || '', numericId));
        }
        if (uploadForm) {
            uploadForm.action = endpointWithContentId(uploadForm.action, numericId) || uploadForm.action;
            uploadForm.querySelectorAll('input[name="content_id"]').forEach((node) => {
                node.value = String(numericId);
            });
        }
        if (selectForm) {
            selectForm.action = endpointWithContentId(selectForm.action, numericId) || selectForm.action;
            selectForm.setAttribute('data-action-template', endpointWithContentId(selectForm.getAttribute('data-action-template') || '', numericId));
            selectForm.querySelectorAll('input[name="content_id"]').forEach((node) => {
                node.value = String(numericId);
            });
        }
        if (detachForm) {
            detachForm.action = endpointWithContentId(detachForm.action, numericId) || detachForm.action;
            detachForm.querySelectorAll('input[name="content_id"]').forEach((node) => {
                node.value = String(numericId);
            });
        }
        const attachForm = document.querySelector('[data-media-library-attach-form]');
        if (attachForm) {
            attachForm.action = endpointWithContentId(attachForm.action, numericId) || attachForm.action;
            attachForm.setAttribute('data-action-template', endpointWithContentId(attachForm.getAttribute('data-action-template') || '', numericId));
            attachForm.querySelectorAll('input[name="content_id"]').forEach((node) => {
                node.value = String(numericId);
            });
        }
        if (deleteForm) {
            deleteForm.action = endpointWithContentId(deleteForm.action, numericId) || deleteForm.action;
            deleteForm.setAttribute('data-action-template', endpointWithContentId(deleteForm.getAttribute('data-action-template') || '', numericId));
        }
        if (renameForm) {
            renameForm.action = endpointWithContentId(renameForm.action, numericId) || renameForm.action;
            renameForm.setAttribute('data-action-template', endpointWithContentId(renameForm.getAttribute('data-action-template') || '', numericId));
        }
    };

    const setStatus = (message) => {
        if (status) {
            status.textContent = message;
        }
    };

    const setTriggerEmpty = () => {
        openTrigger.classList.add('empty');
        openTrigger.innerHTML = `<span>${t('content.choose_image', 'Choose image')}</span>`;
        if (detachWrap) {
            detachWrap.remove();
        }
    };

    const setTriggerThumbnail = (media) => {
        if (!openTrigger || !media) {
            return;
        }

        const imagePath = absoluteUrl(media.preview_path || media.webp_path || media.path || '');
        if (imagePath === '') {
            return;
        }

        openTrigger.classList.remove('empty');
        openTrigger.setAttribute('data-current-media-id', String(Number(media.id || 0)));
        openTrigger.innerHTML = '<div class="content-thumbnail-preview"><img src="' + imagePath + '" alt="' + String(media.name || '').replace(/"/g, '&quot;') + '"></div>';
        currentMediaId = Number(media.id || 0);
    };

    const absoluteUrl = (path) => {
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

    const renderItems = (items) => {
        if (!grid) {
            return;
        }

        if (!Array.isArray(items) || items.length === 0) {
            grid.innerHTML = `<p class="text-muted m-0">${t('media.no_results', 'No results.')}</p>`;
            return;
        }

        grid.innerHTML = '';
        items.forEach((item) => {
            const name = String(item.name || t('media.untitled', 'Untitled'));
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
            button.dataset.mediaWebpPath = String(item.webp_path || '');
            button.dataset.mediaCreated = String(item.created || '');
            button.dataset.mediaCreatedLabel = String(item.created_label || item.created || '');
            button.dataset.mediaPreviewPath = previewPath;
            button.dataset.mediaCanEdit = item.can_edit === true ? '1' : '0';
            button.dataset.mediaCanDelete = item.can_delete === true ? '1' : '0';
            grid.appendChild(button);
        });
    };

    const selectCard = (target) => {
        if (!target || !grid) {
            return;
        }

        const mediaId = Number(target.getAttribute('data-media-library-select') || '0');
        if (mediaId <= 0) {
            return;
        }

        selectedMedia = {
            id: mediaId,
            name: target.dataset.mediaName || t('media.untitled', 'Untitled'),
            path: target.dataset.mediaPath || '',
            webpPath: target.dataset.mediaWebpPath || '',
            created: target.dataset.mediaCreated || '',
            createdLabel: target.dataset.mediaCreatedLabel || target.dataset.mediaCreated || '',
            previewPath: target.dataset.mediaPreviewPath || '',
            canEdit: target.dataset.mediaCanEdit === '1',
            canDelete: target.dataset.mediaCanDelete === '1',
        };

        grid.querySelectorAll('.media-library-card.selected').forEach((node) => node.classList.remove('selected'));
        target.classList.add('selected');
        setStatus('');
        renderSelected();
    };

    const renderSelected = () => {
        if (detailPreview) {
            detailPreview.innerHTML = '';
            if (selectedMedia && selectedMedia.previewPath !== '') {
                const image = document.createElement('img');
                image.src = absoluteUrl(selectedMedia.previewPath);
                image.alt = selectedMedia.name;
                detailPreview.appendChild(image);
            } else {
                detailPreview.textContent = t('media.no_preview', 'No preview');
            }
        }

        if (detailNameInput) {
            detailNameInput.value = selectedMedia ? selectedMedia.name : '';
            detailNameInput.disabled = !(selectedMedia && selectedMedia.canEdit);
        }

        if (detailCreated) {
            detailCreated.textContent = selectedMedia ? (selectedMedia.createdLabel || selectedMedia.created) : '—';
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

        if (mediaIdField) {
            mediaIdField.value = selectedMedia ? String(selectedMedia.id) : '';
        }

        if (deleteMediaIdField) {
            deleteMediaIdField.value = selectedMedia ? String(selectedMedia.id) : '';
        }

        if (renameMediaId) {
            renameMediaId.value = selectedMedia ? String(selectedMedia.id) : '';
        }
    };

    const updatePager = () => {
        if (prevButton) {
            const prevDisabled = page <= 1;
            prevButton.classList.toggle('disabled', prevDisabled);
            prevButton.setAttribute('aria-disabled', prevDisabled ? 'true' : 'false');
            prevButton.setAttribute('tabindex', prevDisabled ? '-1' : '0');
            if ('disabled' in prevButton) {
                prevButton.disabled = prevDisabled;
            }
        }

        if (nextButton) {
            const nextDisabled = page >= totalPages;
            nextButton.classList.toggle('disabled', nextDisabled);
            nextButton.setAttribute('aria-disabled', nextDisabled ? 'true' : 'false');
            nextButton.setAttribute('tabindex', nextDisabled ? '-1' : '0');
            if ('disabled' in nextButton) {
                nextButton.disabled = nextDisabled;
            }
        }
    };

    const load = async () => {
        if (!endpoint) {
            return;
        }

        if (grid) {
            grid.innerHTML = `<p class="text-muted m-0">${t('common.loading', 'Loading...')}</p>`;
            if (loader) {
                loader.set(grid, true);
            }
        }

        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('page', String(page));
        url.searchParams.set('per_page', String(perPage));
        if (query !== '') {
            url.searchParams.set('q', query);
        }
        if (currentMediaId > 0) {
            url.searchParams.set('current_media_id', String(currentMediaId));
        }

        try {
            const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                throw new Error('load_failed');
            }
            const raw = await response.json();
            const normalized = normalizePayload(raw);
            const items = Array.isArray(normalized.data) ? normalized.data : (Array.isArray(raw.items) ? raw.items : []);
            const total = Number(normalized.meta.total_pages || raw.total_pages || 1);
            const current = Number(normalized.meta.page || raw.page || 1);
            totalPages = Math.max(1, total);
            page = Math.min(Math.max(1, current), totalPages);
            renderItems(items);
            if (!selectedMedia && currentMediaId > 0 && grid) {
                const currentCard = grid.querySelector(`[data-media-library-select="${currentMediaId}"]`);
                if (currentCard) {
                    selectCard(currentCard);
                }
            }
            updatePager();
        } finally {
            if (grid && loader) {
                loader.set(grid, false);
            }
        }
    };

    const setContext = (detail) => {
        endpoint = String(detail.endpoint || '');
        baseUrl = String(detail.baseUrl || '');
        mode = String(detail.mode || 'thumbnail');
        editorId = String(detail.editorId || '');
        contentId = Number(detail.contentId || 0);
        currentMediaId = Number(detail.currentMediaId || 0);
        syncContentContext(contentId);
    };

    const open = (detail) => {
        setContext(detail || {});
        if (modalService) {
            modalService.open('media-library-modal');
        } else {
            modal.classList.add('open');
        }
        if (searchTimer) {
            clearTimeout(searchTimer);
            searchTimer = null;
        }
        page = 1;
        selectedMedia = null;
        setStatus('');
        renderSelected();
        load().catch(() => {
            if (grid) {
                grid.innerHTML = `<p class="text-danger m-0">${t('media.library_load_failed', 'Failed to load media library.')}</p>`;
            }
        });
    };

    const waitForDraftId = () => new Promise((resolve) => {
        const onReady = (event) => {
            document.removeEventListener('tinycms:content-draft-ready', onReady);
            resolve(Number(event.detail?.id || 0));
        };
        document.addEventListener('tinycms:content-draft-ready', onReady);
        document.dispatchEvent(new CustomEvent('tinycms:content-ensure-draft'));
    });

    const close = () => {
        if (modalService) {
            modalService.close('media-library-modal');
            return;
        }
        modal.classList.remove('open');
    };

    if (openTrigger) {
        openTrigger.addEventListener('click', async () => {
            const contentInput = document.querySelector('[data-media-library-attach-form] input[name="content_id"]');
            let resolvedId = Number(contentInput ? contentInput.value : '0');
            if (resolvedId <= 0) {
                resolvedId = await waitForDraftId();
            }
            syncContentContext(resolvedId);
            open({
                mode: 'thumbnail',
                endpoint: openTrigger.getAttribute('data-media-library-endpoint') || '',
                baseUrl: openTrigger.getAttribute('data-media-base-url') || '',
                currentMediaId: Number(openTrigger.getAttribute('data-current-media-id') || '0'),
                contentId: resolvedId,
            });
        });
    }

    document.addEventListener('tinycms:media-library-open', async (event) => {
        const detail = event.detail || {};
        if (Number(detail.contentId || 0) <= 0) {
            const resolvedId = await waitForDraftId();
            detail.contentId = resolvedId;
            detail.endpoint = endpointWithContentId(detail.endpoint || '', resolvedId);
        }
        open(detail);
    });
    if (!modalService) {
        closeButtons.forEach((button) => button.addEventListener('click', close));
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                close();
            }
        });
    }

    if (prevButton) {
        prevButton.addEventListener('click', (event) => {
            event.preventDefault();
            if (page <= 1) {
                return;
            }
            page -= 1;
            load().catch(() => null);
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', (event) => {
            event.preventDefault();
            if (page >= totalPages) {
                return;
            }
            page += 1;
            load().catch(() => null);
        });
    }

    if (searchForm) {
        const searchField = searchForm.querySelector('input[name="q"]');
        searchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            query = searchField ? searchField.value.trim() : '';
            page = 1;
            load().catch(() => null);
        });

        if (searchField) {
            searchField.addEventListener('input', () => {
                if (searchTimer) {
                    clearTimeout(searchTimer);
                }
                searchTimer = window.setTimeout(() => {
                    query = searchField.value.trim();
                    page = 1;
                    load().catch(() => null);
                }, 1000);
            });
        }
    }

    if (grid) {
        grid.addEventListener('click', (event) => {
            const target = event.target.closest('[data-media-library-select]');
            if (!target) {
                return;
            }

            selectCard(target);
        });
    }

    if (chooseButton && selectForm && mediaIdField) {
        chooseButton.addEventListener('click', async () => {
            if (!selectedMedia) {
                return;
            }

            if (mode === 'editor') {
                const imageUrl = absoluteUrl(selectedMedia.webpPath || selectedMedia.path || selectedMedia.previewPath || '');
                if (imageUrl === '') {
                    setStatus(t('media.invalid_url', 'Image has no valid URL.'));
                    return;
                }
                const attachForm = document.querySelector('[data-media-library-attach-form]');
                const attachMediaId = document.querySelector('[data-media-library-attach-media-id]');
                if (attachForm && attachMediaId && contentId > 0) {
                    attachMediaId.value = String(selectedMedia.id);
                    const body = new FormData(attachForm);
                    body.set('content_id', String(contentId));
                    body.set('media_id', String(selectedMedia.id));
                    const attachAction = resolveAction(attachForm, selectedMedia.id);
                    await fetch(attachAction, {
                        method: 'POST',
                        body,
                        headers: { Accept: 'application/json' },
                    }).catch(() => null);
                }
                document.dispatchEvent(new CustomEvent('tinycms:media-library-selected', {
                    detail: {
                        mode,
                        editorId,
                        id: selectedMedia.id,
                        name: selectedMedia.name,
                        url: imageUrl,
                    },
                }));
                close();
                return;
            }

            mediaIdField.value = String(selectedMedia.id);
            const selectAction = resolveAction(selectForm, selectedMedia.id);
            const response = await fetch(selectAction, {
                method: 'POST',
                body: new FormData(selectForm),
                headers: { Accept: 'application/json' },
            });
            const data = normalizePayload(await response.json().catch(() => ({})));
            if (!response.ok || !data.success) {
                setStatus(data.message || t('media.assign_failed', 'Failed to assign preview.'));
                return;
            }
            if (data.data && data.data.media) {
                setTriggerThumbnail(data.data.media);
            }
            close();
        });
    }

    if (renameButton && renameForm && detailNameInput && renameName) {
        renameButton.addEventListener('click', async () => {
            if (!selectedMedia) {
                return;
            }

            const value = detailNameInput.value.trim();
            if (value === '') {
                setStatus(t('media.name_required', 'Name cannot be empty.'));
                return;
            }

            renameName.value = value;
            const renameAction = resolveAction(renameForm, selectedMedia.id);
            const response = await fetch(renameAction, {
                method: 'POST',
                body: new FormData(renameForm),
                headers: { Accept: 'application/json' },
            });
            const data = normalizePayload(await response.json());

            if (!response.ok || !data.success) {
                setStatus(data.message || t('media.rename_failed', 'Failed to save name.'));
                return;
            }

            selectedMedia.name = value;
            const selectedCard = grid ? grid.querySelector('.media-library-card.selected') : null;
            if (selectedCard) {
                selectedCard.dataset.mediaName = value;
                const label = selectedCard.querySelector('span');
                if (label) {
                    label.textContent = value;
                }
            }

            setStatus(t('media.rename_saved', 'Name saved.'));
        });
    }

    if (uploadForm && uploadInput) {
        uploadInput.addEventListener('change', async () => {
            if (!uploadInput.files || uploadInput.files.length === 0) {
                return;
            }

            setStatus('');

            if (contentId <= 0) {
                contentId = await waitForDraftId();
                if (contentId <= 0) {
                    setStatus(t('content.draft_required', 'Draft must be created first.'));
                    uploadInput.value = '';
                    uploadInput.dispatchEvent(new Event('change'));
                    return;
                }
                syncContentContext(contentId);
            }

            if (uploadField) {
                if (loader) {
                    loader.set(uploadField, true);
                } else {
                    uploadField.classList.add('is-loading');
                }
            }

            const response = await fetch(uploadForm.action, {
                method: 'POST',
                body: new FormData(uploadForm),
                headers: { Accept: 'application/json' },
            });
            const data = normalizePayload(await response.json().catch(() => ({})));

            if (uploadField) {
                if (loader) {
                    loader.set(uploadField, false);
                } else {
                    uploadField.classList.remove('is-loading');
                }
            }

            if (!response.ok || !data.success) {
                setStatus(data.message || t('media.upload_failed', 'Upload failed.'));
                return;
            }

            uploadInput.value = '';
            uploadInput.dispatchEvent(new Event('change'));

            page = 1;
            await load().catch(() => null);
            setStatus(t('media.uploaded', 'File uploaded.'));
        });
    }

    if (deleteConfirmButton && deleteForm && deleteMediaIdField) {
        deleteConfirmButton.addEventListener('click', async () => {
            if (!selectedMedia) {
                return;
            }

            const formData = new FormData(deleteForm);
            formData.set('media_id', String(selectedMedia.id));
            const deleteAction = resolveAction(deleteForm, selectedMedia.id);
            const response = await fetch(deleteAction, {
                method: 'POST',
                body: formData,
                headers: { Accept: 'application/json' },
            });
            const data = normalizePayload(await response.json().catch(() => ({})));

            if (!response.ok || !data.success) {
                setStatus(data.message || t('media.delete_failed', 'Delete failed.'));
                return;
            }

            if (currentMediaId === selectedMedia.id) {
                currentMediaId = 0;
                setTriggerEmpty();
            }

            selectedMedia = null;
            renderSelected();
            await load().catch(() => null);
            if (deleteConfirmModal && modalService) {
                modalService.close('media-library-delete-modal');
            } else if (deleteConfirmModal) {
                deleteConfirmModal.classList.remove('open');
            }
            setStatus(t('media.deleted', 'Media deleted.'));
        });
    }

    if (detachButton && detachForm) {
        detachButton.addEventListener('click', async () => {
            const response = await fetch(detachForm.action, {
                method: 'POST',
                body: new FormData(detachForm),
                headers: { Accept: 'application/json' },
            });
            const data = normalizePayload(await response.json().catch(() => ({})));

            if (!response.ok || !data.success) {
                setStatus(data.message || t('media.detach_failed', 'Detach failed.'));
                return;
            }

            currentMediaId = 0;
            setTriggerEmpty();
            setStatus(t('media.detached', 'Preview detached.'));
        });
    }
}
