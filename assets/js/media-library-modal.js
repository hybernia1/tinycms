const modal = document.querySelector('[data-media-library-modal]');
const i18n = window.tinycmsI18n || {};
const t = (path, fallback = '') => {
    const value = path.split('.').reduce((acc, key) => (acc && Object.prototype.hasOwnProperty.call(acc, key) ? acc[key] : undefined), i18n);
    return typeof value === 'string' && value !== '' ? value : fallback;
};
const openTriggers = document.querySelectorAll('[data-media-library-open]');

if (modal && openTriggers.length > 0) {
    const grid = modal.querySelector('[data-media-library-grid]');
    const pageLabel = modal.querySelector('[data-media-library-page]');
    const prevButton = modal.querySelector('[data-media-library-prev]');
    const nextButton = modal.querySelector('[data-media-library-next]');
    const closeButtons = modal.querySelectorAll('[data-media-library-close]');
    const searchForm = modal.querySelector('[data-media-library-search]');
    const uploadForm = modal.querySelector('[data-media-library-upload-form]');
    const uploadButton = modal.querySelector('[data-media-library-upload-button]');
    const uploadLabel = modal.querySelector('[data-media-library-upload-label]');
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
    const detailPath = modal.querySelector('[data-media-library-detail-path]');
    const detailCreated = modal.querySelector('[data-media-library-detail-created]');
    const chooseButton = modal.querySelector('[data-media-library-choose]');
    const deleteButton = modal.querySelector('[data-media-library-delete-open]');
    const renameButton = modal.querySelector('[data-media-library-rename]');
    const status = modal.querySelector('[data-media-library-status]');
    const renameForm = document.querySelector('[data-media-library-rename-form]');
    const renameMediaId = document.querySelector('[data-media-library-rename-media-id]');
    const renameName = document.querySelector('[data-media-library-rename-name]');

    let endpoint = '';
    let uploadEndpoint = '';
    let baseUrl = '';
    let currentMediaId = 0;
    let mode = 'thumbnail';
    let editorId = '';
    let contentId = 0;
    let page = 1;
    let totalPages = 1;
    let query = '';
    let selectedMedia = null;
    let searchTimer = null;
    let activeTrigger = null;

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

    const setStatus = (message) => {
        if (status) {
            status.textContent = message;
        }
    };

    const setTriggerEmpty = () => {
        if (!activeTrigger) {
            return;
        }
        activeTrigger.classList.add('empty');
        activeTrigger.innerHTML = `<span>${t('content.choose_image', 'Choose image')}</span>`;
        activeTrigger.setAttribute('data-current-media-id', '0');
        if (mode === 'thumbnail' && detachWrap) {
            detachWrap.remove();
        }
    };

    const setTriggerThumbnail = (media) => {
        if (!activeTrigger || !media) {
            return;
        }

        const imagePath = absoluteUrl(media.preview_path || media.webp_path || media.path || '');
        if (imagePath === '') {
            return;
        }

        activeTrigger.classList.remove('empty');
        activeTrigger.setAttribute('data-current-media-id', String(Number(media.id || 0)));
        activeTrigger.innerHTML = '<div class="content-thumbnail-preview"><img src="' + imagePath + '" alt="' + String(media.name || '').replace(/"/g, '&quot;') + '"></div>';
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
            grid.innerHTML = '<p class="text-muted m-0">Žádné výsledky.</p>';
            return;
        }

        grid.innerHTML = '';
        items.forEach((item) => {
            const name = String(item.name || 'Bez názvu');
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

            const label = document.createElement('span');
            label.textContent = name;

            button.appendChild(imageWrap);
            button.appendChild(label);
            button.dataset.mediaName = name;
            button.dataset.mediaPath = String(item.path || '');
            button.dataset.mediaWebpPath = String(item.webp_path || '');
            button.dataset.mediaCreated = String(item.created || '');
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
            name: target.dataset.mediaName || 'Bez názvu',
            path: target.dataset.mediaPath || '',
            webpPath: target.dataset.mediaWebpPath || '',
            created: target.dataset.mediaCreated || '',
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

        if (detailPath) {
            detailPath.textContent = selectedMedia ? selectedMedia.path : '—';
        }

        if (detailCreated) {
            detailCreated.textContent = selectedMedia ? selectedMedia.created : '—';
        }

        if (chooseButton) {
            chooseButton.disabled = !selectedMedia;
        }

        if (deleteButton) {
            const canDelete = !!(selectedMedia && selectedMedia.canDelete && mode !== 'settings');
            deleteButton.disabled = !canDelete;
            deleteButton.classList.toggle('d-none', !canDelete);
        }

        if (renameButton) {
            const canEdit = !!(selectedMedia && selectedMedia.canEdit && mode !== 'settings');
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
        if (pageLabel) {
            pageLabel.textContent = `${page} / ${totalPages}`;
        }

        if (prevButton) {
            prevButton.disabled = page <= 1;
        }

        if (nextButton) {
            nextButton.disabled = page >= totalPages;
        }
    };

    const load = async () => {
        if (!endpoint) {
            return;
        }

        if (grid) {
            grid.innerHTML = '<p class="text-muted m-0">Načítám...</p>';
        }

        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('page', String(page));
        url.searchParams.set('per_page', '10');
        if (query !== '') {
            url.searchParams.set('q', query);
        }
        if (currentMediaId > 0) {
            url.searchParams.set('current_media_id', String(currentMediaId));
        }

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
    };

    const setContext = (detail) => {
        endpoint = String(detail.endpoint || '');
        uploadEndpoint = String(detail.uploadEndpoint || '');
        baseUrl = String(detail.baseUrl || '');
        mode = String(detail.mode || 'thumbnail');
        editorId = String(detail.editorId || '');
        contentId = Number(detail.contentId || 0);
        currentMediaId = Number(detail.currentMediaId || 0);
    };

    const open = (detail) => {
        setContext(detail || {});
        activeTrigger = detail.trigger || null;
        modal.classList.add('open');
        if (searchTimer) {
            clearTimeout(searchTimer);
            searchTimer = null;
        }
        page = 1;
        query = '';
        selectedMedia = null;
        setStatus('');
        renderSelected();
        if (searchForm) {
            const searchField = searchForm.querySelector('input[name="q"]');
            if (searchField) {
                searchField.value = '';
            }
        }
        if (uploadForm && uploadEndpoint !== '') {
            uploadForm.action = uploadEndpoint;
        }
        load().catch(() => {
            if (grid) {
                grid.innerHTML = '<p class="text-danger m-0">Nepodařilo se načíst knihovnu.</p>';
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
        modal.classList.remove('open');
    };

    openTriggers.forEach((trigger) => {
        trigger.addEventListener('click', async () => {
            const triggerMode = trigger.getAttribute('data-media-library-mode') || 'thumbnail';
            let resolvedId = Number(trigger.getAttribute('data-content-id') || '0');
            if (triggerMode === 'thumbnail') {
                const contentInput = document.querySelector('[data-media-library-attach-form] input[name="content_id"]');
                resolvedId = Number(contentInput ? contentInput.value : '0');
                if (resolvedId <= 0) {
                    resolvedId = await waitForDraftId();
                }
            }

            open({
                trigger,
                mode: triggerMode,
                endpoint: trigger.getAttribute('data-media-library-endpoint') || '',
                uploadEndpoint: trigger.getAttribute('data-media-library-upload-endpoint') || (uploadForm ? uploadForm.action : ''),
                baseUrl: trigger.getAttribute('data-media-base-url') || '',
                currentMediaId: Number(trigger.getAttribute('data-current-media-id') || '0'),
                contentId: resolvedId,
            });
        });
    });

    document.querySelectorAll('[data-settings-media-clear]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = String(button.getAttribute('data-settings-media-clear') || '');
            const trigger = target !== '' ? document.querySelector(`[data-media-library-open][data-settings-media-target=\"${target}\"]`) : null;
            const input = target !== '' ? document.querySelector(`[data-settings-media-input=\"${target}\"]`) : null;
            if (!trigger || !input) {
                return;
            }
            activeTrigger = trigger;
            mode = 'settings';
            input.value = '';
            setTriggerEmpty();
        });
    });

    document.addEventListener('tinycms:media-library-open', async (event) => {
        const detail = event.detail || {};
        if (Number(detail.contentId || 0) <= 0) {
            const resolvedId = await waitForDraftId();
            detail.contentId = resolvedId;
        }
        open(detail);
    });

    closeButtons.forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            close();
        }
    });

    if (prevButton) {
        prevButton.addEventListener('click', () => {
            if (page <= 1) {
                return;
            }
            page -= 1;
            load().catch(() => null);
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', () => {
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

    if (chooseButton) {
        chooseButton.addEventListener('click', async () => {
            if (!selectedMedia) {
                return;
            }

            if (mode === 'editor') {
                const imageUrl = absoluteUrl(selectedMedia.webpPath || selectedMedia.path || selectedMedia.previewPath || '');
                if (imageUrl === '') {
                    setStatus('Obrázek nemá platnou URL.');
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

            if (mode === 'settings') {
                const inputSelector = activeTrigger ? String(activeTrigger.getAttribute('data-settings-media-input') || '') : '';
                const settingsInput = inputSelector !== '' ? document.querySelector(inputSelector) : null;
                if (!settingsInput) {
                    setStatus('Nelze uložit vybraný obrázek.');
                    return;
                }
                settingsInput.value = String(selectedMedia.path || selectedMedia.webpPath || '');
                setTriggerThumbnail(selectedMedia);
                close();
                return;
            }

            if (!selectForm || !mediaIdField) {
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
                setStatus(data.message || 'Náhled se nepodařilo přiřadit.');
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
                setStatus('Název nesmí být prázdný.');
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
                setStatus(data.message || 'Název se nepodařilo uložit.');
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

            setStatus('Název uložen.');
        });
    }

    if (uploadForm && uploadButton && uploadLabel) {
        uploadForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            setStatus('');

            if (mode === 'thumbnail' && contentId <= 0) {
                contentId = await waitForDraftId();
                if (contentId <= 0) {
                    setStatus('Nejdřív se musí vytvořit draft.');
                    return;
                }
            }

            uploadButton.disabled = true;
            uploadLabel.textContent = 'Nahrávám...';

            const response = await fetch(uploadForm.action, {
                method: 'POST',
                body: new FormData(uploadForm),
                headers: { Accept: 'application/json' },
            });
            const data = normalizePayload(await response.json().catch(() => ({})));

            uploadButton.disabled = false;
            uploadLabel.textContent = 'Nahrát nový';

            if (!response.ok || !data.success) {
                setStatus(data.message || 'Upload se nepodařil.');
                return;
            }

            const fileInput = uploadForm.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.value = '';
            }

            page = 1;
            await load().catch(() => null);
            setStatus('Soubor nahrán.');
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
                setStatus(data.message || 'Mazání se nepodařilo.');
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
                deleteConfirmModal.classList.remove('open');
            }
            setStatus('Médium smazáno.');
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
                setStatus(data.message || 'Odpojení se nepodařilo.');
                return;
            }

            currentMediaId = 0;
            setTriggerEmpty();
            setStatus('Náhled odpojen.');
        });
    }
}
