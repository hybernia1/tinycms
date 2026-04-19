(() => {
const modal = document.querySelector('[data-media-library-modal]');
const t = window.tinycms?.i18n?.t || (() => '');
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
const requestJson = window.tinycms?.api?.http?.requestJson;
const postForm = window.tinycms?.api?.http?.postForm;
const modalApi = window.tinycms?.modal;
const openModalElement = modalApi?.open;
const closeModalElement = modalApi?.close;

if (modal && openTrigger && typeof requestJson === 'function' && typeof postForm === 'function' && typeof openModalElement === 'function' && typeof closeModalElement === 'function') {
    const loader = window.tinycmsLoader || null;
    const grid = modal.querySelector('[data-media-library-grid]');
    const prevButton = modal.querySelector('[data-media-library-prev]');
    const nextButton = modal.querySelector('[data-media-library-next]');
    const searchForm = modal.querySelector('[data-media-library-search]');
    const uploadForm = modal.querySelector('[data-media-library-upload-form]');
    const uploadField = modal.querySelector('[data-media-library-upload-field]');
    const uploadInput = uploadForm ? uploadForm.querySelector('input[type="file"]') : null;
    const detachForm = document.querySelector('[data-media-library-detach-form]');
    const selectForm = document.querySelector('[data-media-library-select-form]');
    const mediaIdField = document.querySelector('[data-media-library-media-id]');
    const deleteMediaIdField = document.querySelector('[data-media-library-delete-media-id]');
    const deleteForm = document.getElementById('media-library-delete-form');
    const deleteConfirmModal = document.getElementById('media-library-delete-modal');
    const deleteConfirmButton = deleteConfirmModal?.querySelector('[data-modal-confirm]');
    const detailPreview = modal.querySelector('[data-media-library-detail-preview]');
    const detailNameInput = modal.querySelector('[data-media-library-detail-name-input]');
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
        openTrigger.setAttribute('data-current-media-id', '0');
        openTrigger.innerHTML = `<span>${t('content.choose_image')}</span>`;
    };

    const setTriggerThumbnail = (media) => {
        if (!openTrigger || !media) {
            return;
        }

        const imagePath = absoluteUrl(media.preview_path || '');
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
            name: target.dataset.mediaName || t('media.untitled'),
            path: target.dataset.mediaPath || '',
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

    const clearSelected = () => {
        selectedMedia = null;
        if (grid) {
            grid.querySelectorAll('.media-library-card.selected').forEach((node) => node.classList.remove('selected'));
        }
        setStatus('');
        renderSelected();
    };

    const detachCurrent = async () => {
        if (!detachForm) {
            return false;
        }
        const { response, data } = await postForm(detachForm.action, detachForm);
        if (!response.ok || !data.success) {
            setStatus(data.message || t('media.detach_failed'));
            return false;
        }
        currentMediaId = 0;
        setTriggerEmpty();
        setStatus(t('media.detached'));
        return true;
    };

    const renderSelected = () => {
        if (detailPreview) {
            detailPreview.innerHTML = '';
            const detailImagePath = selectedMedia
                ? selectedMedia.previewPath
                : '';
            if (selectedMedia && detailImagePath !== '') {
                const image = document.createElement('img');
                image.src = absoluteUrl(detailImagePath);
                image.alt = selectedMedia.name;
                detailPreview.appendChild(image);
                const createdBadge = document.createElement('span');
                createdBadge.className = 'badge media-library-detail-created-badge';
                createdBadge.textContent = selectedMedia.createdLabel || selectedMedia.created || '—';
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
            grid.innerHTML = `<p class="text-muted m-0">${t('common.loading')}</p>`;
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
            const { response, data: normalized, raw } = await requestJson(url.toString(), {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                throw new Error('load_failed');
            }
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
        openModalElement(modal);
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
                grid.innerHTML = `<p class="text-danger m-0">${t('media.library_load_failed')}</p>`;
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
        closeModalElement(modal);
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
        grid.addEventListener('click', async (event) => {
            const target = event.target.closest('[data-media-library-select]');
            if (!target) {
                return;
            }
            const mediaId = Number(target.getAttribute('data-media-library-select') || '0');
            const isSameSelection = !!(selectedMedia && selectedMedia.id === mediaId);
            if (isSameSelection) {
                clearSelected();
                if (mode === 'thumbnail' && mediaId > 0 && mediaId === currentMediaId) {
                    await detachCurrent();
                }
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
                const imageUrl = absoluteUrl(selectedMedia.previewPath || '');
                if (imageUrl === '') {
                    setStatus(t('media.invalid_url'));
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
                    await postForm(attachAction, body).catch(() => null);
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
            const { response, data } = await postForm(selectAction, selectForm);
            if (!response.ok || !data.success) {
                setStatus(data.message || t('media.assign_failed'));
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
                setStatus(t('media.name_required'));
                return;
            }

            renameName.value = value;
            const renameAction = resolveAction(renameForm, selectedMedia.id);
            const { response, data } = await postForm(renameAction, renameForm);

            if (!response.ok || !data.success) {
                setStatus(data.message || t('media.rename_failed'));
                return;
            }

            selectedMedia.name = value;
            const selectedCard = grid ? grid.querySelector('.media-library-card.selected') : null;
            if (selectedCard) {
                selectedCard.dataset.mediaName = value;
            }

            setStatus(t('media.rename_saved'));
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
                    setStatus(t('content.draft_required'));
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

            const { response, data } = await postForm(uploadForm.action, uploadForm);

            if (uploadField) {
                if (loader) {
                    loader.set(uploadField, false);
                } else {
                    uploadField.classList.remove('is-loading');
                }
            }

            if (!response.ok || !data.success) {
                setStatus(data.message || t('media.upload_failed'));
                return;
            }

            uploadInput.value = '';
            uploadInput.dispatchEvent(new Event('change'));

            page = 1;
            await load().catch(() => null);
            setStatus(t('media.uploaded'));
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
            const { response, data } = await postForm(deleteAction, formData);

            if (!response.ok || !data.success) {
                setStatus(data.message || t('media.delete_failed'));
                return;
            }

            if (currentMediaId === selectedMedia.id) {
                currentMediaId = 0;
                setTriggerEmpty();
            }

            selectedMedia = null;
            renderSelected();
            await load().catch(() => null);
            if (deleteConfirmModal) {
                closeModalElement(deleteConfirmModal);
            }
            setStatus(t('media.deleted'));
        });
    }

}
})();
