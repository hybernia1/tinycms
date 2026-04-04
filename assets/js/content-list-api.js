const contentListRoot = document.querySelector('[data-content-list]');

if (contentListRoot) {
    const endpoint = contentListRoot.getAttribute('data-endpoint') || '';
    const editBase = contentListRoot.getAttribute('data-edit-base') || '';
    const csrfInput = contentListRoot.querySelector('[data-content-csrf] input[name="_csrf"]');
    const filterLinks = Array.from(contentListRoot.querySelectorAll('[data-content-status]'));
    const searchField = contentListRoot.querySelector('[data-content-search]');
    const perPageField = contentListRoot.querySelector('[data-content-per-page]');
    const body = contentListRoot.querySelector('[data-content-list-body]');
    const prevLink = contentListRoot.querySelector('[data-content-prev]');
    const nextLink = contentListRoot.querySelector('[data-content-next]');
    const deleteModal = contentListRoot.querySelector('[data-content-delete-modal]');
    const deleteCancel = deleteModal?.querySelector('[data-content-delete-cancel]');
    const deleteConfirm = deleteModal?.querySelector('[data-content-delete-confirm]');

    let state = {
        page: 1,
        perPage: Number(perPageField?.value || '10'),
        status: filterLinks.find((link) => link.classList.contains('active'))?.getAttribute('data-content-status') || 'all',
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

    const rowHtml = (item) => {
        const status = String(item.status || 'draft');
        const statusClass = status === 'published' ? 'text-bg-success' : (status === 'draft' ? 'text-bg-dark' : 'text-bg-primary');
        const isPublished = status === 'published';
        const toggleIcon = isPublished ? 'hide' : 'show';
        const toggleLabel = isPublished ? 'Přepnout do draftu' : 'Publikovat';
        const icon = (name) => iconSprite !== ''
            ? `<svg class="icon" aria-hidden="true" focusable="false"><use href="${esc(iconSprite)}#icon-${esc(name)}"></use></svg>`
            : '';
        return `
            <tr>
                <td>
                    <a href="${esc(editBase)}${Number(item.id || 0)}">${esc(item.name)}</a>
                    <div class="text-muted">${esc(item.created)}</div>
                    <div class="d-flex gap-2 mt-2">
                        <span class="badge ${esc(statusClass)}">${esc(status)}</span>
                        ${item.is_planned ? '<span class="badge text-bg-warning">Plánováno</span>' : ''}
                    </div>
                </td>
                <td>${esc(item.author_name || '—')}</td>
                <td class="table-col-actions">
                    <button class="btn btn-light btn-icon" type="button" data-content-toggle="${Number(item.id || 0)}" data-content-mode="${isPublished ? 'draft' : 'publish'}" aria-label="${esc(toggleLabel)}" title="${esc(toggleLabel)}">
                        ${icon(toggleIcon)}
                        <span class="sr-only">${esc(toggleLabel)}</span>
                    </button>
                    <button class="btn btn-light btn-icon" type="button" data-content-delete-open="${Number(item.id || 0)}" aria-label="Smazat" title="Smazat">
                        ${icon('delete')}
                        <span class="sr-only">Smazat</span>
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

    const syncFilters = () => {
        filterLinks.forEach((link) => {
            link.classList.toggle('active', link.getAttribute('data-content-status') === state.status);
        });
    };

    const fetchList = async () => {
        if (!endpoint || !body) {
            return;
        }

        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('page', String(state.page));
        url.searchParams.set('per_page', String(state.perPage));
        url.searchParams.set('status', state.status);
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
        syncFilters();
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

    contentListRoot.addEventListener('click', async (event) => {
        const statusLink = event.target.closest('[data-content-status]');
        if (statusLink) {
            event.preventDefault();
            state.status = statusLink.getAttribute('data-content-status') || 'all';
            state.page = 1;
            await fetchList();
            return;
        }

        const prev = event.target.closest('[data-content-prev]');
        if (prev) {
            event.preventDefault();
            if (state.page > 1) {
                state.page -= 1;
                await fetchList();
            }
            return;
        }

        const next = event.target.closest('[data-content-next]');
        if (next) {
            event.preventDefault();
            state.page += 1;
            await fetchList();
            return;
        }

        const toggle = event.target.closest('[data-content-toggle]');
        if (toggle) {
            event.preventDefault();
            const id = Number(toggle.getAttribute('data-content-toggle') || '0');
            const mode = toggle.getAttribute('data-content-mode') || 'draft';
            if (id > 0) {
                const ok = await postAction(`${endpoint.replace(/\/$/, '')}/status-toggle`, { id, mode });
                if (ok) {
                    await fetchList();
                }
            }
            return;
        }

        const delOpen = event.target.closest('[data-content-delete-open]');
        if (delOpen) {
            event.preventDefault();
            pendingDeleteId = Number(delOpen.getAttribute('data-content-delete-open') || '0');
            if (deleteModal) {
                deleteModal.classList.add('open');
            }
        }
    });

    if (deleteCancel) {
        deleteCancel.addEventListener('click', () => {
            if (deleteModal) {
                deleteModal.classList.remove('open');
            }
            pendingDeleteId = 0;
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
