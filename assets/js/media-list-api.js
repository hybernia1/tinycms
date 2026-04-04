const mediaListRoot = document.querySelector('[data-media-list]');

if (mediaListRoot) {
    const endpoint = mediaListRoot.getAttribute('data-endpoint') || '';
    const editBase = mediaListRoot.getAttribute('data-edit-base') || '';
    const thumbSuffix = mediaListRoot.getAttribute('data-thumb-suffix') || '_100x100.webp';
    const csrfInput = mediaListRoot.querySelector('[data-media-csrf] input[name="_csrf"]');
    const searchField = mediaListRoot.querySelector('[data-media-search]');
    const perPageField = mediaListRoot.querySelector('[data-media-per-page]');
    const body = mediaListRoot.querySelector('[data-media-list-body]');
    const prevLink = mediaListRoot.querySelector('[data-media-prev]');
    const nextLink = mediaListRoot.querySelector('[data-media-next]');
    const deleteModal = mediaListRoot.querySelector('[data-media-delete-modal]');
    const deleteCancel = deleteModal?.querySelector('[data-media-delete-cancel]');
    const deleteConfirm = deleteModal?.querySelector('[data-media-delete-confirm]');

    let state = {
        page: 1,
        perPage: Number(perPageField?.value || '10'),
        query: searchField?.value.trim() || '',
    };
    let pendingDeleteId = 0;
    let searchTimer = null;
    const iconUse = document.querySelector('svg use[href*="#icon-"]');
    const iconSprite = iconUse ? String(iconUse.getAttribute('href') || '').split('#')[0] : '';

    const esc = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const icon = (name) => iconSprite !== ''
        ? `<svg class="icon" aria-hidden="true" focusable="false"><use href="${esc(iconSprite)}#icon-${esc(name)}"></use></svg>`
        : '';

    const previewUrl = (item) => {
        const preview = String(item.preview_path || '');
        if (preview !== '') {
            return preview;
        }
        const webp = String(item.path_webp || '');
        if (webp !== '') {
            return webp.replace(/\.webp$/i, thumbSuffix);
        }
        return String(item.path || '');
    };

    const rowHtml = (item) => {
        const id = Number(item.id || 0);
        const img = previewUrl(item);
        return `
            <tr>
                <td>
                    <div class="d-flex align-center gap-2">
                        ${img !== ''
                            ? `<div class="media-list-thumb"><img src="${esc(img)}" alt="${esc(item.name)}"></div>`
                            : '<div class="media-list-thumb media-list-thumb-empty"></div>'}
                        <div>
                            <a href="${esc(editBase)}${id}">${esc(item.name)}</a>
                            <div class="text-muted">${esc(item.path)}</div>
                            <div class="text-muted">${esc(item.created)}</div>
                        </div>
                    </div>
                </td>
                <td>${esc(item.author_name || '—')}</td>
                <td class="table-col-actions">
                    <button class="btn btn-light btn-icon" type="button" data-media-delete-open="${id}" aria-label="Smazat médium" title="Smazat médium">
                        ${icon('delete')}
                        <span class="sr-only">Smazat médium</span>
                    </button>
                </td>
            </tr>
        `;
    };

    const setPagination = (page, totalPages) => {
        if (!prevLink || !nextLink) {
            return;
        }

        const prevDisabled = page <= 1;
        const nextDisabled = page >= totalPages;

        prevLink.classList.toggle('disabled', prevDisabled);
        nextLink.classList.toggle('disabled', nextDisabled);
        prevLink.setAttribute('aria-disabled', prevDisabled ? 'true' : 'false');
        nextLink.setAttribute('aria-disabled', nextDisabled ? 'true' : 'false');
    };

    const fetchList = async () => {
        if (!endpoint || !body) {
            return;
        }

        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('page', String(state.page));
        url.searchParams.set('per_page', String(state.perPage));
        if (state.query !== '') {
            url.searchParams.set('q', state.query);
        }

        const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            return;
        }

        const data = await response.json();
        const items = Array.isArray(data.items) ? data.items : [];
        body.innerHTML = items.map(rowHtml).join('');
        setPagination(Number(data.page || 1), Number(data.total_pages || 1));
    };

    const postAction = async (path, payload) => {
        const formData = new FormData();
        if (csrfInput) {
            formData.append('_csrf', csrfInput.value);
        }
        Object.entries(payload).forEach(([key, value]) => formData.append(key, String(value)));

        const response = await fetch(path, {
            method: 'POST',
            body: formData,
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) {
            return false;
        }
        const data = await response.json().catch(() => ({}));
        return data.success === true;
    };

    mediaListRoot.addEventListener('click', async (event) => {
        const prev = event.target.closest('[data-media-prev]');
        if (prev) {
            event.preventDefault();
            if (state.page > 1) {
                state.page -= 1;
                await fetchList();
            }
            return;
        }

        const next = event.target.closest('[data-media-next]');
        if (next) {
            event.preventDefault();
            state.page += 1;
            await fetchList();
            return;
        }

        const delOpen = event.target.closest('[data-media-delete-open]');
        if (delOpen) {
            event.preventDefault();
            pendingDeleteId = Number(delOpen.getAttribute('data-media-delete-open') || '0');
            if (deleteModal) {
                deleteModal.classList.add('open');
            }
        }
    });

    if (deleteCancel) {
        deleteCancel.addEventListener('click', () => {
            pendingDeleteId = 0;
            if (deleteModal) {
                deleteModal.classList.remove('open');
            }
        });
    }

    if (deleteConfirm) {
        deleteConfirm.addEventListener('click', async () => {
            if (pendingDeleteId <= 0) {
                return;
            }
            const ok = await postAction(`${endpoint.replace(/\/$/, '')}/delete`, { id: pendingDeleteId });
            if (ok) {
                pendingDeleteId = 0;
                if (deleteModal) {
                    deleteModal.classList.remove('open');
                }
                await fetchList();
            }
        });
    }

    if (perPageField) {
        perPageField.addEventListener('change', async () => {
            state.perPage = Number(perPageField.value || '10');
            state.page = 1;
            await fetchList();
        });
    }

    if (searchField) {
        searchField.addEventListener('input', () => {
            if (searchTimer) {
                clearTimeout(searchTimer);
            }
            searchTimer = window.setTimeout(async () => {
                state.query = searchField.value.trim();
                state.page = 1;
                await fetchList();
            }, 1000);
        });
    }
}
