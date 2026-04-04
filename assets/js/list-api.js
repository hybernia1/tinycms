const esc = (value) => String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

const iconSprite = (() => {
    const iconUse = document.querySelector('svg use[href*="#icon-"]');
    return iconUse ? String(iconUse.getAttribute('href') || '').split('#')[0] : '';
})();

const icon = (name) => iconSprite !== ''
    ? `<svg class="icon" aria-hidden="true" focusable="false"><use href="${esc(iconSprite)}#icon-${esc(name)}"></use></svg>`
    : '';

const normalizeListResponse = (payload) => {
    const meta = payload && typeof payload.meta === 'object' ? payload.meta : {};
    return {
        items: Array.isArray(payload?.data) ? payload.data : [],
        page: Number(meta.page || 1),
        totalPages: Number(meta.total_pages || 1),
    };
};

const normalizeActionResponse = (response, payload) => {
    return {
        success: payload?.ok === true,
        message: String(payload?.error?.message || ''),
        data: payload?.data && typeof payload.data === 'object' ? payload.data : {},
        statusOk: response.ok,
    };
};

const pushFlash = (type, message) => {
    const text = String(message || '').trim();
    if (text === '') {
        return;
    }

    const container = document.querySelector('.admin-content');
    if (!container) {
        return;
    }

    const flashType = ['success', 'error', 'info'].includes(type) ? type : 'info';
    const flash = document.createElement('div');
    flash.className = `flash flash-${flashType}`;
    flash.innerHTML = `
        <span>${esc(text)}</span>
        <button type="button" data-flash-close aria-label="Zavřít notifikaci" title="Zavřít notifikaci">
            ${icon('cancel')}
        </button>
    `;
    container.prepend(flash);
};

const initListApi = (config) => {
    const root = document.querySelector(config.rootSelector);
    if (!root) {
        return;
    }

    const endpoint = root.getAttribute('data-endpoint') || '';
    const endpointBase = endpoint.replace(/\/$/, '');
    const editBase = root.getAttribute('data-edit-base') || '';
    const csrfInput = root.querySelector(`[data-${config.name}-csrf] input[name="_csrf"]`);
    const searchField = root.querySelector(`[data-${config.name}-search]`);
    const perPageField = root.querySelector(`[data-${config.name}-per-page]`);
    const body = root.querySelector(`[data-${config.name}-list-body]`);
    const prevLink = root.querySelector(`[data-${config.name}-prev]`);
    const nextLink = root.querySelector(`[data-${config.name}-next]`);
    const deleteModal = root.querySelector(`[data-${config.name}-delete-modal]`);
    const deleteCancel = deleteModal?.querySelector(`[data-${config.name}-delete-cancel]`);
    const deleteConfirm = deleteModal?.querySelector(`[data-${config.name}-delete-confirm]`);
    const filterLinks = config.withStatus
        ? Array.from(root.querySelectorAll(`[data-${config.name}-status]`))
        : [];
    const context = typeof config.getContext === 'function' ? config.getContext(root) : {};

    let state = {
        page: 1,
        perPage: Number(perPageField?.value || '10'),
        query: searchField?.value.trim() || '',
    };

    if (config.withStatus) {
        state = {
            ...state,
            status: filterLinks.find((link) => link.classList.contains('active'))?.getAttribute(`data-${config.name}-status`) || 'all',
        };
    }

    let pendingDeleteId = 0;
    let searchTimer = null;
    let fetchController = null;

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
        if (!config.withStatus) {
            return;
        }

        filterLinks.forEach((link) => {
            link.classList.toggle('active', link.getAttribute(`data-${config.name}-status`) === state.status);
        });
    };

    const fetchList = async () => {
        if (!endpoint || !body) {
            return;
        }

        if (fetchController) {
            fetchController.abort();
        }
        fetchController = new AbortController();

        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('page', String(state.page));
        url.searchParams.set('per_page', String(state.perPage));

        if (config.withStatus) {
            url.searchParams.set('status', state.status);
        }

        if (state.query !== '') {
            url.searchParams.set('q', state.query);
        }

        const response = await fetch(url.toString(), {
            headers: { Accept: 'application/json' },
            signal: fetchController.signal,
        }).catch((error) => {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return null;
            }
            throw error;
        });
        if (!response) {
            return;
        }
        if (!response.ok) {
            return;
        }

        const data = await response.json();
        const normalized = normalizeListResponse(data);
        body.innerHTML = normalized.items.map((item) => config.rowHtml(item, { editBase, context })).join('');
        setPagination(normalized.page, normalized.totalPages);
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
            const errorData = await response.json().catch(() => ({}));
            const normalizedError = normalizeActionResponse(response, errorData);
            const message = normalizedError.message;
            if (message !== '') {
                pushFlash('error', message);
            }
            return { success: false };
        }

        const data = await response.json().catch(() => ({}));
        const normalized = normalizeActionResponse(response, data);
        if (!normalized.success && normalized.message !== '') {
            pushFlash('error', normalized.message);
        }

        return {
            success: normalized.success,
            message: normalized.message,
            ...normalized.data,
        };
    };

    root.addEventListener('click', async (event) => {
        if (config.withStatus) {
            const statusLink = event.target.closest(`[data-${config.name}-status]`);
            if (statusLink) {
                event.preventDefault();
                state.status = statusLink.getAttribute(`data-${config.name}-status`) || 'all';
                state.page = 1;
                await fetchList();
                return;
            }
        }

        const prev = event.target.closest(`[data-${config.name}-prev]`);
        if (prev) {
            event.preventDefault();
            if (state.page > 1) {
                state.page -= 1;
                await fetchList();
            }
            return;
        }

        const next = event.target.closest(`[data-${config.name}-next]`);
        if (next) {
            event.preventDefault();
            state.page += 1;
            await fetchList();
            return;
        }

        if (config.toggle) {
            const toggle = event.target.closest(`[data-${config.name}-toggle]`);
            if (toggle) {
                event.preventDefault();
                const id = Number(toggle.getAttribute(`data-${config.name}-toggle`) || '0');
                const mode = toggle.getAttribute(`data-${config.name}-mode`) || config.toggle.defaultMode;
                if (id > 0) {
                    const togglePath = typeof config.togglePath === 'function'
                        ? config.togglePath(endpointBase, id)
                        : `${endpointBase}/${config.toggle.path}`;
                    const result = await postAction(togglePath, { id, mode });
                    if (result.success === true) {
                        if (config.messages?.toggleSuccess) {
                            pushFlash('success', config.messages.toggleSuccess(mode));
                        }
                        await fetchList();
                    }
                }
                return;
            }
        }

        const delOpen = event.target.closest(`[data-${config.name}-delete-open]`);
        if (delOpen) {
            event.preventDefault();
            pendingDeleteId = Number(delOpen.getAttribute(`data-${config.name}-delete-open`) || '0');
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

            deleteConfirm.disabled = true;
            const deletePath = typeof config.deletePath === 'function'
                ? config.deletePath(endpointBase, pendingDeleteId)
                : `${endpointBase}/delete`;
            const result = await postAction(deletePath, { id: pendingDeleteId });
            deleteConfirm.disabled = false;
            if (result.success === true) {
                pendingDeleteId = 0;
                if (deleteModal) {
                    deleteModal.classList.remove('open');
                }
                if (config.messages?.deleteSuccess) {
                    pushFlash('success', config.messages.deleteSuccess);
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
};

initListApi({
    name: 'content',
    rootSelector: '[data-content-list]',
    withStatus: true,
    toggle: { path: 'status-toggle', defaultMode: 'draft' },
    togglePath: (endpointBase, id) => `${endpointBase}/${id}/status`,
    deletePath: (endpointBase, id) => `${endpointBase}/${id}/delete`,
    messages: {
        deleteSuccess: 'Obsah smazán.',
        toggleSuccess: (mode) => mode === 'publish' ? 'Obsah publikován.' : 'Obsah přepnut do draftu.',
    },
    rowHtml: (item, { editBase }) => {
        const status = String(item.status || 'draft');
        const statusClass = status === 'published' ? 'text-bg-success' : (status === 'draft' ? 'text-bg-dark' : 'text-bg-primary');
        const isPublished = status === 'published';
        const toggleLabel = isPublished ? 'Přepnout do draftu' : 'Publikovat';

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
                        ${icon(isPublished ? 'hide' : 'show')}
                        <span class="sr-only">${esc(toggleLabel)}</span>
                    </button>
                    <button class="btn btn-light btn-icon" type="button" data-content-delete-open="${Number(item.id || 0)}" aria-label="Smazat" title="Smazat">
                        ${icon('delete')}
                        <span class="sr-only">Smazat</span>
                    </button>
                </td>
            </tr>
        `;
    },
});

initListApi({
    name: 'terms',
    rootSelector: '[data-terms-list]',
    withStatus: false,
    deletePath: (endpointBase, id) => `${endpointBase}/${id}/delete`,
    messages: { deleteSuccess: 'Štítek smazán.' },
    rowHtml: (item, { editBase }) => {
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
                        ${icon('delete')}
                        <span class="sr-only">Smazat štítek</span>
                    </button>
                </td>
            </tr>
        `;
    },
});

initListApi({
    name: 'media',
    rootSelector: '[data-media-list]',
    withStatus: false,
    deletePath: (endpointBase, id) => `${endpointBase}/${id}/delete`,
    messages: { deleteSuccess: 'Médium smazáno.' },
    getContext: (root) => ({ thumbSuffix: root.getAttribute('data-thumb-suffix') || '_100x100.webp' }),
    rowHtml: (item, { editBase, context }) => {
        const thumbSuffix = context.thumbSuffix || '_100x100.webp';
        const id = Number(item.id || 0);
        const preview = String(item.preview_path || '');
        const webp = String(item.path_webp || '');
        const img = preview !== '' ? preview : (webp !== '' ? webp.replace(/\.webp$/i, thumbSuffix) : String(item.path || ''));

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
    },
});

initListApi({
    name: 'users',
    rootSelector: '[data-users-list]',
    withStatus: true,
    toggle: { path: 'suspend-toggle', defaultMode: 'suspend' },
    togglePath: (endpointBase, id) => `${endpointBase}/${id}/suspend`,
    deletePath: (endpointBase, id) => `${endpointBase}/${id}/delete`,
    messages: {
        deleteSuccess: 'Uživatel smazán.',
        toggleSuccess: (mode) => mode === 'unsuspend' ? 'Uživatel odsuspendován.' : 'Uživatel suspendován.',
    },
    rowHtml: (item, { editBase }) => {
        const id = Number(item.id || 0);
        const isSuspended = item.is_suspended === true;
        const isAdmin = item.is_admin === true;
        const toggleLabel = isSuspended ? 'Odsuspendovat' : 'Suspendovat';

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
                        ${icon(isSuspended ? 'show' : 'hide')}
                        <span class="sr-only">${esc(toggleLabel)}</span>
                    </button>
                    <button class="btn btn-light btn-icon" type="button" data-users-delete-open="${id}" aria-label="Smazat uživatele" title="Smazat uživatele">
                        ${icon('delete')}
                        <span class="sr-only">Smazat uživatele</span>
                    </button>`}
                </td>
            </tr>
        `;
    },
});
