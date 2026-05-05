(() => {
const app = window.tinycms = window.tinycms || {};
const mediaLibrary = app.mediaLibrary = app.mediaLibrary || {};
let modal = document.querySelector('[data-media-library-modal]');
const t = app.i18n?.t || (() => '');
const modalUi = app.ui?.modal || {
    open: (node) => node?.classList.add('open'),
    close: (node) => node?.classList.remove('open'),
    confirm: () => Promise.resolve(false),
};
const confirmModal = modalUi.confirm;
const transport = mediaLibrary.transport || {};
const helpers = mediaLibrary.helpers || {};
const currentCsrf = app.support?.currentCsrf || (() => '');
const firstTrigger = Array.prototype.find.call(
    document.querySelectorAll('[data-media-library-open]'),
    (node) => node.getAttribute('data-media-library-mode') !== 'editor',
) || null;
let openTrigger = firstTrigger;
const postForm = app.api?.http?.postForm;

if (!modal) {
    modal = document.querySelector('[data-media-library-modal]');
}

if (modal && typeof postForm === 'function') {
    const loader = app.loader || null;
    const grid = modal.querySelector('[data-media-library-grid]');
    const prevButton = modal.querySelector('[data-media-library-prev]');
    const nextButton = modal.querySelector('[data-media-library-next]');
    const searchForm = modal.querySelector('[data-media-library-search]');
    const uploadForm = modal.querySelector('[data-media-library-upload-form]');
    const uploadField = modal.querySelector('[data-media-library-upload-field]');
    const uploadInput = uploadForm ? uploadForm.querySelector('input[type="file"]') : null;
    const uploadLabel = uploadField ? uploadField.querySelector('[data-custom-upload-label]') : null;
    const detailPreview = modal.querySelector('[data-media-library-detail-preview]');
    const detailNameInput = modal.querySelector('[data-media-library-detail-name-input]');
    const chooseButton = modal.querySelector('[data-media-library-choose]');
    const deleteButton = modal.querySelector('[data-media-library-delete-open]');
    const renameButton = modal.querySelector('[data-media-library-rename]');
    const status = modal.querySelector('[data-media-library-status]');

    let endpoint = '';
    let baseUrl = '';
    let currentMediaId = 0;
    let mode = 'thumbnail';
    let editorId = '';
    let contentId = 0;
    let currentMediaPath = '';
    let targetInput = '';
    let allowDelete = true;
    let allowRename = true;
    let page = 1;
    let totalPages = 1;
    let query = '';
    const configuredPerPage = Number(modal.getAttribute('data-media-library-per-page') || '10');
    const perPage = Number.isFinite(configuredPerPage) && configuredPerPage > 0
        ? Math.min(Math.floor(configuredPerPage), 50)
        : 10;
    let selectedMedia = null;
    let searchTimer = null;

    const uploadAction = (trigger) => trigger?.getAttribute('data-media-upload-endpoint') || '';
    const uploadName = (trigger) => trigger?.getAttribute('data-media-upload-name') || 'thumbnail';

    const updateUploadLabel = () => {
        if (!uploadInput || !uploadLabel) {
            return;
        }

        const label = uploadLabel.getAttribute('data-default-label') || uploadLabel.textContent || '';
        const text = uploadInput.files && uploadInput.files.length > 0
            ? Array.from(uploadInput.files).map((file) => file.name).join(', ')
            : label;

        uploadLabel.textContent = text;
        uploadLabel.setAttribute('title', text);
    };

    const renderSelected = () => {
        if (detailPreview) {
            detailPreview.innerHTML = '';
            if (selectedMedia && selectedMedia.previewPath) {
                const image = document.createElement('img');
                image.src = absoluteUrl(selectedMedia.previewPath);
                image.alt = selectedMedia.name;
                detailPreview.appendChild(image);
            }
        }

        if (detailNameInput) {
            detailNameInput.value = selectedMedia ? selectedMedia.name : '';
            detailNameInput.disabled = !selectedMedia || !selectedMedia.canEdit;
        }

        if (chooseButton) {
            chooseButton.disabled = !selectedMedia;
        }

        if (deleteButton) {
            deleteButton.disabled = !selectedMedia || !selectedMedia.canDelete;
        }

        if (renameButton) {
            renameButton.disabled = !selectedMedia || !selectedMedia.canEdit;
        }
    };

    const updatePager = () => {
        if (prevButton) {
            prevButton.classList.toggle('disabled', page <= 1);
            prevButton.setAttribute('aria-disabled', page <= 1 ? 'true' : 'false');
            prevButton.setAttribute('tabindex', page <= 1 ? '-1' : '0');
        }

        if (nextButton) {
            nextButton.classList.toggle('disabled', page >= totalPages);
            nextButton.setAttribute('aria-disabled', page >= totalPages ? 'true' : 'false');
            nextButton.setAttribute('tabindex', page >= totalPages ? '-1' : '0');
        }
    };

    const syncContentContext = (id) => {
        const numericId = Number(id || 0);
        if (numericId <= 0) {
            return;
        }

        contentId = numericId;
        if (openTrigger) {
            endpoint = transport.syncContentContext(openTrigger, uploadForm, endpoint, numericId);
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
        openTrigger.innerHTML = '<div class="media-picker-preview"><img src="' + imagePath + '" alt="' + String(media.name || '').replace(/"/g, '&quot;') + '"></div>';
        currentMediaId = Number(media.id || 0);
    };

    const setSettingValue = (media) => {
        if (!openTrigger || !media) {
            return;
        }

        const path = String(media.path || '');
        const imagePath = absoluteUrl(media.previewPath || path);
        const input = targetInput !== '' ? document.querySelector(targetInput) : null;
        if (input) {
            input.value = path;
            document.dispatchEvent(new CustomEvent('tinycms:media-setting-selected', {
                detail: { input, path },
            }));
        }
        openTrigger.classList.toggle('empty', imagePath === '');
        openTrigger.setAttribute('data-current-media-id', String(Number(media.id || 0)));
        openTrigger.setAttribute('data-current-media-path', path);
        if (imagePath !== '') {
            openTrigger.innerHTML = '<div class="media-picker-preview-compact"><img src="' + imagePath + '" alt="' + String(media.name || '').replace(/"/g, '&quot;') + '"></div>';
        }
        currentMediaId = Number(media.id || 0);
        currentMediaPath = path;
    };

    const detachSetting = () => {
        if (!openTrigger) {
            return;
        }

        const input = targetInput !== '' ? document.querySelector(targetInput) : null;
        if (input) {
            input.value = '';
            document.dispatchEvent(new CustomEvent('tinycms:media-setting-selected', {
                detail: { input, path: '' },
            }));
        }
        currentMediaId = 0;
        currentMediaPath = '';
        openTrigger.classList.add('empty');
        openTrigger.setAttribute('data-current-media-id', '0');
        openTrigger.setAttribute('data-current-media-path', '');
        openTrigger.innerHTML = `<span>${t('content.choose_image')}</span>`;
    };

    const absoluteUrl = (path) => helpers.absoluteUrl?.(path, baseUrl) || '';

    const selectCard = (target) => {
        if (!target || !grid) {
            return;
        }

        const mediaId = Number(target.getAttribute('data-media-library-select') || '0');
        if (mediaId <= 0) {
            return;
        }

        selectedMedia = helpers.mediaFromCard(target, t('media.untitled'));
        selectedMedia.canDelete = selectedMedia.canDelete && allowDelete;
        selectedMedia.canEdit = selectedMedia.canEdit && allowRename;

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
        if (!openTrigger) {
            return false;
        }
        const action = transport.mediaAction(openTrigger, contentId, 'detach');
        if (action === '') {
            return false;
        }
        const { response, data } = await postForm(action, transport.csrfData({ id: contentId }));
        if (!response.ok || !data.success) {
            setStatus(data.message || t('media.detach_failed'));
            return false;
        }
        currentMediaId = 0;
        setTriggerEmpty();
        setStatus(t('media.detached'));
        return true;
    };

    const load = async () => {
        if (!endpoint) {
            return;
        }

        if (grid) {
            grid.textContent = t('common.loading');
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
        if (currentMediaPath !== '') {
            url.searchParams.set('current_media_path', currentMediaPath);
        }
        url.searchParams.set('html', 'library');

        try {
            const response = await fetch(url.toString(), {
                headers: { Accept: 'text/html' },
            });
            if (!response.ok) {
                throw new Error('load_failed');
            }
            const templateNode = document.createElement('template');
            templateNode.innerHTML = (await response.text()).trim();
            const fragment = templateNode.content.querySelector('[data-media-library-items]');
            if (!fragment || !grid) {
                throw new Error('invalid_fragment');
            }
            const total = Number(fragment.getAttribute('data-total-pages') || 1);
            const current = Number(fragment.getAttribute('data-page') || 1);
            totalPages = Math.max(1, total);
            page = Math.min(Math.max(1, current), totalPages);
            grid.innerHTML = fragment.innerHTML;
            if (!selectedMedia && currentMediaId > 0 && grid) {
                const currentCard = grid.querySelector(`[data-media-library-select="${currentMediaId}"]`);
                if (currentCard) {
                    selectCard(currentCard);
                }
            }
            if (!selectedMedia && currentMediaPath !== '' && grid) {
                const currentCard = Array.from(grid.querySelectorAll('[data-media-library-select]'))
                    .find((card) => card.dataset.mediaPath === currentMediaPath);
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
        currentMediaPath = String(detail.currentMediaPath || '');
        targetInput = String(detail.targetInput || '');
        allowDelete = detail.allowDelete !== false;
        allowRename = detail.allowRename !== false;
        if (uploadForm && openTrigger) {
            uploadForm.action = uploadAction(openTrigger) || uploadForm.action;
        }
        if (uploadInput && openTrigger) {
            uploadInput.name = uploadName(openTrigger) || uploadInput.name;
            uploadInput.accept = openTrigger.getAttribute('data-media-upload-accept') || '';
        }
        syncContentContext(contentId);
    };

    const open = (detail) => {
        setContext(detail || {});
        modalUi.open(modal);
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
                grid.textContent = t('media.library_load_failed');
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
        modalUi.close(modal);
    };

    document.querySelectorAll('[data-media-library-open]').forEach((trigger) => {
        if (trigger.getAttribute('data-media-library-mode') === 'editor') {
            return;
        }

        trigger.addEventListener('click', async () => {
            openTrigger = trigger;
            const contentInput = document.querySelector('[data-content-id-hidden]');
            let resolvedId = Number(contentInput ? contentInput.value : '0');
            const triggerMode = trigger.getAttribute('data-media-library-mode') || 'thumbnail';
            if (resolvedId <= 0 && triggerMode !== 'settings') {
                resolvedId = await waitForDraftId();
            }
            syncContentContext(resolvedId);
            open(helpers.thumbnailDetail(trigger, resolvedId));
        });
    });

    document.addEventListener('tinycms:media-library-open', async (event) => {
        const detail = event.detail || {};
        if (Number(detail.contentId || 0) <= 0 && String(detail.mode || '') !== 'settings') {
            const resolvedId = await waitForDraftId();
            detail.contentId = resolvedId;
            detail.endpoint = transport.endpointWithContentId(detail.endpoint || '', resolvedId);
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
                if (mode === 'settings') {
                    detachSetting();
                    setStatus(t('media.detached'));
                }
                if (mode === 'thumbnail' && mediaId > 0 && mediaId === currentMediaId) {
                    await detachCurrent();
                }
                return;
            }

            selectCard(target);
        });
    }

    if (chooseButton) {
        chooseButton.addEventListener('click', async () => {
            if (!selectedMedia) {
                return;
            }

            if (mode === 'settings') {
                setSettingValue(selectedMedia);
                close();
                return;
            }

            if (mode === 'editor') {
                const imageUrl = absoluteUrl(selectedMedia.webpPath || selectedMedia.previewPath || '');
                if (imageUrl === '') {
                    setStatus(t('media.invalid_url'));
                    return;
                }
                const attachAction = openTrigger ? transport.mediaAction(openTrigger, contentId, 'attach', selectedMedia.id) : '';
                if (attachAction !== '' && contentId > 0) {
                    await postForm(attachAction, transport.csrfData({
                        content_id: contentId,
                        media_id: selectedMedia.id,
                    })).catch(() => null);
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

            if (!openTrigger) {
                return;
            }
            const selectAction = transport.mediaAction(openTrigger, contentId, 'select', selectedMedia.id);
            const { response, data } = await postForm(selectAction, transport.csrfData({
                id: contentId,
                media_id: selectedMedia.id,
            }));
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

    if (renameButton && detailNameInput) {
        renameButton.addEventListener('click', async () => {
            if (!selectedMedia) {
                return;
            }

            const value = detailNameInput.value.trim();
            if (value === '') {
                setStatus(t('media.name_required'));
                return;
            }

            const renameAction = openTrigger ? transport.mediaAction(openTrigger, contentId, 'rename', selectedMedia.id) : '';
            if (renameAction === '') {
                return;
            }
            const { response, data } = await postForm(renameAction, transport.csrfData({
                content_id: contentId,
                media_id: selectedMedia.id,
                name: value,
            }));

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
            updateUploadLabel();
            if (!uploadInput.files || uploadInput.files.length === 0) {
                return;
            }

            setStatus('');

            if (contentId <= 0 && mode !== 'settings') {
                contentId = await waitForDraftId();
                if (contentId <= 0) {
                    setStatus(t('content.draft_required'));
                    uploadInput.value = '';
                    updateUploadLabel();
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

            const csrfInput = uploadForm.querySelector('input[name="_csrf"]');
            if (csrfInput) {
                csrfInput.value = currentCsrf();
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
            updateUploadLabel();

            page = 1;
            await load().catch(() => null);
            setStatus(t('media.uploaded'));
        });
    }

    if (deleteButton) {
        deleteButton.addEventListener('click', async () => {
            if (!selectedMedia) {
                return;
            }
            if (!await confirmModal({ message: t('content.delete_image_confirm') })) {
                return;
            }

            const deleteAction = openTrigger ? transport.mediaAction(openTrigger, contentId, 'delete', selectedMedia.id) : '';
            if (deleteAction === '') {
                return;
            }
            const { response, data } = await postForm(deleteAction, transport.csrfData({
                content_id: contentId,
                media_id: selectedMedia.id,
            }));

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
            setStatus(t('media.deleted'));
        });
    }

}
})();
