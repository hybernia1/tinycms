const modal = document.querySelector('[data-media-library-modal]');
const openTrigger = document.querySelector('[data-media-library-open]');

if (modal && openTrigger) {
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

    const endpoint = openTrigger.getAttribute('data-media-library-endpoint') || '';
    const baseUrl = openTrigger.getAttribute('data-media-base-url') || '';
    let currentMediaId = Number(openTrigger.getAttribute('data-current-media-id') || '0');
    let page = 1;
    let totalPages = 1;
    let query = '';
    let selectedMedia = null;
    let searchTimer = null;

    const setStatus = (message) => {
        if (status) {
            status.textContent = message;
        }
    };

    const setTriggerEmpty = () => {
        openTrigger.classList.add('empty');
        openTrigger.innerHTML = '<span>Zvolit obrázek</span>';
        if (detachWrap) {
            detachWrap.remove();
        }
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
            button.dataset.mediaCreated = String(item.created || '');
            button.dataset.mediaPreviewPath = previewPath;
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
            created: target.dataset.mediaCreated || '',
            previewPath: target.dataset.mediaPreviewPath || '',
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
                detailPreview.textContent = 'Bez náhledu';
            }
        }

        if (detailNameInput) {
            detailNameInput.value = selectedMedia ? selectedMedia.name : '';
            detailNameInput.disabled = !selectedMedia;
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
            deleteButton.disabled = !selectedMedia;
        }

        if (renameButton) {
            renameButton.disabled = !selectedMedia;
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

        const data = await response.json();
        totalPages = Math.max(1, Number(data.total_pages || 1));
        page = Math.min(Math.max(1, Number(data.page || 1)), totalPages);
        renderItems(data.items || []);
        if (!selectedMedia && currentMediaId > 0 && grid) {
            const currentCard = grid.querySelector(`[data-media-library-select="${currentMediaId}"]`);
            if (currentCard) {
                selectCard(currentCard);
            }
        }
        updatePager();
    };

    const open = () => {
        modal.classList.add('open');
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
                grid.innerHTML = '<p class="text-danger m-0">Nepodařilo se načíst knihovnu.</p>';
            }
        });
    };

    const close = () => {
        modal.classList.remove('open');
    };

    openTrigger.addEventListener('click', open);
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

    if (chooseButton && selectForm && mediaIdField) {
        chooseButton.addEventListener('click', () => {
            if (!selectedMedia) {
                return;
            }

            mediaIdField.value = String(selectedMedia.id);
            selectForm.submit();
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
            const response = await fetch(renameForm.action, {
                method: 'POST',
                body: new FormData(renameForm),
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();

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

            uploadButton.disabled = true;
            uploadLabel.textContent = 'Nahrávám...';

            const response = await fetch(uploadForm.action, {
                method: 'POST',
                body: new FormData(uploadForm),
                headers: { Accept: 'application/json' },
            });
            const data = await response.json().catch(() => ({}));

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
            const response = await fetch(deleteForm.action, {
                method: 'POST',
                body: formData,
                headers: { Accept: 'application/json' },
            });
            const data = await response.json().catch(() => ({}));

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
            const data = await response.json().catch(() => ({}));

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
