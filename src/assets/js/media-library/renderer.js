(() => {
const t = window.tinycms?.i18n?.t || (() => '');
const icon = window.tinycms?.icons?.icon || (() => '');

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

window.tinycms = window.tinycms || {};
window.tinycms.mediaLibrary = window.tinycms.mediaLibrary || {};
window.tinycms.mediaLibrary.renderer = {
    renderItems,
    renderSelected,
    updatePager,
    updateUploadLabel,
};
})();
