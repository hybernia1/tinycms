const usersListRoot = document.querySelector('[data-users-list]');

if (usersListRoot) {
    const endpoint = usersListRoot.getAttribute('data-endpoint') || '';
    const editBase = usersListRoot.getAttribute('data-edit-base') || '';
    const csrfInput = usersListRoot.querySelector('[data-users-csrf] input[name="_csrf"]');
    const filterLinks = Array.from(usersListRoot.querySelectorAll('[data-users-status]'));
    const searchField = usersListRoot.querySelector('[data-users-search]');
    const perPageField = usersListRoot.querySelector('[data-users-per-page]');
    const body = usersListRoot.querySelector('[data-users-list-body]');
    const prevLink = usersListRoot.querySelector('[data-users-prev]');
    const nextLink = usersListRoot.querySelector('[data-users-next]');
    const deleteModal = usersListRoot.querySelector('[data-users-delete-modal]');
    const deleteCancel = deleteModal?.querySelector('[data-users-delete-cancel]');
    const deleteConfirm = deleteModal?.querySelector('[data-users-delete-confirm]');

    let state = {
        page: 1,
        perPage: Number(perPageField?.value || '10'),
        status: filterLinks.find((link) => link.classList.contains('active'))?.getAttribute('data-users-status') || 'all',
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

    const rowHtml = (item) => {
        const id = Number(item.id || 0);
        const isSuspended = item.is_suspended === true;
        const isAdmin = item.is_admin === true;
        const toggleLabel = isSuspended ? 'Odsuspendovat' : 'Suspendovat';
        const toggleIcon = isSuspended ? 'show' : 'hide';

        return `
            <tr>
                <td>
                    <a href="${esc(editBase)}${id}">${esc(item.name)}</a>
                    <div class="text-muted">${esc(item.email)}</div>
                    <div class="d-flex gap-2 mt-2">
                        <span class="badge text-bg-primary">${esc(item.role)}</span>
                        ${isSuspended ? '<span class="badge text-bg-warning">Suspendován</span>' : ''}
                    </div>
                </td>
                <td class="table-col-actions">
                    ${isAdmin ? '' : `
                    <button class="btn btn-light btn-icon" type="button" data-users-toggle="${id}" data-users-mode="${isSuspended ? 'unsuspend' : 'suspend'}" aria-label="${esc(toggleLabel)}" title="${esc(toggleLabel)}">
                        ${icon(toggleIcon)}
                        <span class="sr-only">${esc(toggleLabel)}</span>
                    </button>
                    <button class="btn btn-light btn-icon" type="button" data-users-delete-open="${id}" aria-label="Smazat uživatele" title="Smazat uživatele">
                        ${icon('delete')}
                        <span class="sr-only">Smazat uživatele</span>
                    </button>`}
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
            link.classList.toggle('active', link.getAttribute('data-users-status') === state.status);
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

    usersListRoot.addEventListener('click', async (event) => {
        const statusLink = event.target.closest('[data-users-status]');
        if (statusLink) {
            event.preventDefault();
            state.status = statusLink.getAttribute('data-users-status') || 'all';
            state.page = 1;
            await fetchList();
            return;
        }

        const prev = event.target.closest('[data-users-prev]');
        if (prev) {
            event.preventDefault();
            if (state.page > 1) {
                state.page -= 1;
                await fetchList();
            }
            return;
        }

        const next = event.target.closest('[data-users-next]');
        if (next) {
            event.preventDefault();
            state.page += 1;
            await fetchList();
            return;
        }

        const toggle = event.target.closest('[data-users-toggle]');
        if (toggle) {
            event.preventDefault();
            const id = Number(toggle.getAttribute('data-users-toggle') || '0');
            const mode = toggle.getAttribute('data-users-mode') || 'suspend';
            if (id > 0) {
                const ok = await postAction(`${endpoint.replace(/\/$/, '')}/suspend-toggle`, { id, mode });
                if (ok) {
                    await fetchList();
                }
            }
            return;
        }

        const delOpen = event.target.closest('[data-users-delete-open]');
        if (delOpen) {
            event.preventDefault();
            pendingDeleteId = Number(delOpen.getAttribute('data-users-delete-open') || '0');
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
