const termsListRoot = document.querySelector('[data-terms-list]');

if (termsListRoot) {
    const endpoint = termsListRoot.getAttribute('data-endpoint') || '';
    const editBase = termsListRoot.getAttribute('data-edit-base') || '';
    const csrfInput = termsListRoot.querySelector('[data-terms-csrf] input[name="_csrf"]');
    const searchField = termsListRoot.querySelector('[data-terms-search]');
    const perPageField = termsListRoot.querySelector('[data-terms-per-page]');
    const body = termsListRoot.querySelector('[data-terms-list-body]');
    const prevLink = termsListRoot.querySelector('[data-terms-prev]');
    const nextLink = termsListRoot.querySelector('[data-terms-next]');
    const deleteModal = termsListRoot.querySelector('[data-terms-delete-modal]');
    const deleteCancel = deleteModal?.querySelector('[data-terms-delete-cancel]');
    const deleteConfirm = deleteModal?.querySelector('[data-terms-delete-confirm]');

    let state = {
        page: 1,
        perPage: Number(perPageField?.value || '10'),
        query: searchField?.value.trim() || '',
    };
    let pendingDeleteId = 0;
    let searchTimer = null;

    const esc = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const rowHtml = (item) => {
        const id = Number(item.id || 0);
        return `
            <tr>
                <td>
                    <a href="${esc(editBase)}${id}">${esc(item.name)}</a>
                    <div class="text-muted">${esc(item.created)}</div>
                </td>
                <td>${esc(item.body || '—')}</td>
                <td class="table-col-actions">
                    <button class="btn btn-light btn-icon" type="button" data-terms-delete-open="${id}" aria-label="Smazat štítek" title="Smazat štítek">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="${esc((document.querySelector('svg use[href*="#icon-"]')?.getAttribute('href') || '').split('#')[0])}#icon-delete"></use></svg>
                        <span class="sr-only">Smazat štítek</span>
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

    termsListRoot.addEventListener('click', async (event) => {
        const prev = event.target.closest('[data-terms-prev]');
        if (prev) {
            event.preventDefault();
            if (state.page > 1) {
                state.page -= 1;
                await fetchList();
            }
            return;
        }

        const next = event.target.closest('[data-terms-next]');
        if (next) {
            event.preventDefault();
            state.page += 1;
            await fetchList();
            return;
        }

        const delOpen = event.target.closest('[data-terms-delete-open]');
        if (delOpen) {
            event.preventDefault();
            pendingDeleteId = Number(delOpen.getAttribute('data-terms-delete-open') || '0');
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
