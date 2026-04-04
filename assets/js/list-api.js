const esc = (value) => String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

const iconUse = document.querySelector('svg use[href*="#icon-"]');
const iconSprite = iconUse ? String(iconUse.getAttribute('href') || '').split('#')[0] : '';

const icon = (name) => iconSprite !== ''
    ? `<svg class="icon" aria-hidden="true" focusable="false"><use href="${esc(iconSprite)}#icon-${esc(name)}"></use></svg>`
    : '';

const showFlash = (type, message) => {
    if (window.TinyCmsFlash?.add) {
        window.TinyCmsFlash.add(type, message);
    }
};

const setPagination = (prevLink, nextLink, page, totalPages) => {
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

const createListApi = ({ root, endpoint, body, prevLink, nextLink, perPageField, searchField, csrfInput, fetchParams, renderRows, onData }) => {
    let state = {
        page: 1,
        perPage: Number(perPageField?.value || '10'),
        query: searchField?.value.trim() || '',
    };
    let searchTimer = null;

    const fetchList = async () => {
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('page', String(state.page));
        url.searchParams.set('per_page', String(state.perPage));
        fetchParams(url, state);

        const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            showFlash('error', 'Načtení seznamu se nepodařilo.');
            return;
        }

        const data = await response.json();
        body.innerHTML = renderRows(Array.isArray(data.items) ? data.items : []);
        setPagination(prevLink, nextLink, Number(data.page || 1), Number(data.total_pages || 1));
        onData(state, data);
    };

    const postAction = async (path, payload, successMessage) => {
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
        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.success !== true) {
            showFlash('error', data.message || 'Operaci se nepodařilo dokončit.');
            return false;
        }

        showFlash('success', data.message || successMessage);
        return true;
    };

    if (prevLink) {
        prevLink.addEventListener('click', async (event) => {
            event.preventDefault();
            if (state.page <= 1) {
                return;
            }
            state.page -= 1;
            await fetchList();
        });
    }

    if (nextLink) {
        nextLink.addEventListener('click', async (event) => {
            event.preventDefault();
            state.page += 1;
            await fetchList();
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
            }, 500);
        });
    }

    return {
        state,
        fetchList,
        postAction,
    };
};

const bindDelete = ({ root, openSelector, modal, cancel, confirm, getId, onConfirm }) => {
    let pendingDeleteId = 0;

    root.addEventListener('click', (event) => {
        const open = event.target.closest(openSelector);
        if (!open) {
            return;
        }
        event.preventDefault();
        pendingDeleteId = getId(open);
        modal?.classList.add('open');
    });

    cancel?.addEventListener('click', () => {
        pendingDeleteId = 0;
        modal?.classList.remove('open');
    });

    confirm?.addEventListener('click', async () => {
        if (pendingDeleteId <= 0) {
            return;
        }
        const ok = await onConfirm(pendingDeleteId);
        if (ok) {
            pendingDeleteId = 0;
            modal?.classList.remove('open');
        }
    });
};

(() => {
    const root = document.querySelector('[data-users-list]');
    if (!root) {
        return;
    }

    const endpoint = root.getAttribute('data-endpoint') || '';
    const editBase = root.getAttribute('data-edit-base') || '';
    const filterLinks = Array.from(root.querySelectorAll('[data-users-status]'));
    const api = createListApi({
        root,
        endpoint,
        body: root.querySelector('[data-users-list-body]'),
        prevLink: root.querySelector('[data-users-prev]'),
        nextLink: root.querySelector('[data-users-next]'),
        perPageField: root.querySelector('[data-users-per-page]'),
        searchField: root.querySelector('[data-users-search]'),
        csrfInput: root.querySelector('[data-users-csrf] input[name="_csrf"]'),
        fetchParams: (url, state) => {
            url.searchParams.set('status', state.status || 'all');
            if (state.query !== '') {
                url.searchParams.set('q', state.query);
            }
        },
        renderRows: (items) => items.map((item) => {
            const id = Number(item.id || 0);
            const isSuspended = item.is_suspended === true;
            const isAdmin = item.is_admin === true;
            const toggleLabel = isSuspended ? 'Odsuspendovat' : 'Suspendovat';
            return `<tr><td><a href="${esc(editBase)}${id}">${esc(item.name)}</a><div class="text-muted">${esc(item.email)}</div><div class="d-flex gap-2 mt-2"><span class="badge text-bg-primary">${esc(item.role)}</span>${isSuspended ? '<span class="badge text-bg-warning">Suspendován</span>' : ''}</div></td><td class="table-col-actions">${isAdmin ? '' : `<button class="btn btn-light btn-icon" type="button" data-users-toggle="${id}" data-users-mode="${isSuspended ? 'unsuspend' : 'suspend'}" aria-label="${esc(toggleLabel)}" title="${esc(toggleLabel)}">${icon(isSuspended ? 'show' : 'hide')}<span class="sr-only">${esc(toggleLabel)}</span></button><button class="btn btn-light btn-icon" type="button" data-users-delete-open="${id}" aria-label="Smazat uživatele" title="Smazat uživatele">${icon('delete')}<span class="sr-only">Smazat uživatele</span></button>`}</td></tr>`;
        }).join(''),
        onData: (state) => {
            filterLinks.forEach((link) => {
                link.classList.toggle('active', link.getAttribute('data-users-status') === state.status);
            });
        },
    });

    api.state.status = filterLinks.find((link) => link.classList.contains('active'))?.getAttribute('data-users-status') || 'all';

    root.addEventListener('click', async (event) => {
        const statusLink = event.target.closest('[data-users-status]');
        if (statusLink) {
            event.preventDefault();
            api.state.status = statusLink.getAttribute('data-users-status') || 'all';
            api.state.page = 1;
            await api.fetchList();
            return;
        }

        const toggle = event.target.closest('[data-users-toggle]');
        if (!toggle) {
            return;
        }

        event.preventDefault();
        const id = Number(toggle.getAttribute('data-users-toggle') || '0');
        const mode = toggle.getAttribute('data-users-mode') || 'suspend';
        if (id <= 0) {
            return;
        }
        const ok = await api.postAction(`${endpoint.replace(/\/$/, '')}/suspend-toggle`, { id, mode }, mode === 'unsuspend' ? 'Uživatel odsuspendován.' : 'Uživatel suspendován.');
        if (ok) {
            await api.fetchList();
        }
    });

    bindDelete({
        root,
        openSelector: '[data-users-delete-open]',
        modal: root.querySelector('[data-users-delete-modal]'),
        cancel: root.querySelector('[data-users-delete-cancel]'),
        confirm: root.querySelector('[data-users-delete-confirm]'),
        getId: (node) => Number(node.getAttribute('data-users-delete-open') || '0'),
        onConfirm: async (id) => {
            const ok = await api.postAction(`${endpoint.replace(/\/$/, '')}/delete`, { id }, 'Uživatel smazán.');
            if (ok) {
                await api.fetchList();
            }
            return ok;
        },
    });
})();

(() => {
    const root = document.querySelector('[data-media-list]');
    if (!root) {
        return;
    }

    const endpoint = root.getAttribute('data-endpoint') || '';
    const editBase = root.getAttribute('data-edit-base') || '';
    const thumbSuffix = root.getAttribute('data-thumb-suffix') || '_100x100.webp';
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

    const api = createListApi({
        root,
        endpoint,
        body: root.querySelector('[data-media-list-body]'),
        prevLink: root.querySelector('[data-media-prev]'),
        nextLink: root.querySelector('[data-media-next]'),
        perPageField: root.querySelector('[data-media-per-page]'),
        searchField: root.querySelector('[data-media-search]'),
        csrfInput: root.querySelector('[data-media-csrf] input[name="_csrf"]'),
        fetchParams: (url, state) => {
            if (state.query !== '') {
                url.searchParams.set('q', state.query);
            }
        },
        renderRows: (items) => items.map((item) => {
            const id = Number(item.id || 0);
            const img = previewUrl(item);
            return `<tr><td><div class="d-flex align-center gap-2">${img !== '' ? `<div class="media-list-thumb"><img src="${esc(img)}" alt="${esc(item.name)}"></div>` : '<div class="media-list-thumb media-list-thumb-empty"></div>'}<div><a href="${esc(editBase)}${id}">${esc(item.name)}</a><div class="text-muted">${esc(item.path)}</div><div class="text-muted">${esc(item.created)}</div></div></div></td><td>${esc(item.author_name || '—')}</td><td class="table-col-actions"><button class="btn btn-light btn-icon" type="button" data-media-delete-open="${id}" aria-label="Smazat médium" title="Smazat médium">${icon('delete')}<span class="sr-only">Smazat médium</span></button></td></tr>`;
        }).join(''),
        onData: () => {},
    });

    bindDelete({
        root,
        openSelector: '[data-media-delete-open]',
        modal: root.querySelector('[data-media-delete-modal]'),
        cancel: root.querySelector('[data-media-delete-cancel]'),
        confirm: root.querySelector('[data-media-delete-confirm]'),
        getId: (node) => Number(node.getAttribute('data-media-delete-open') || '0'),
        onConfirm: async (id) => {
            const ok = await api.postAction(`${endpoint.replace(/\/$/, '')}/delete`, { id }, 'Médium smazáno.');
            if (ok) {
                await api.fetchList();
            }
            return ok;
        },
    });
})();

(() => {
    const root = document.querySelector('[data-terms-list]');
    if (!root) {
        return;
    }

    const endpoint = root.getAttribute('data-endpoint') || '';
    const editBase = root.getAttribute('data-edit-base') || '';
    const api = createListApi({
        root,
        endpoint,
        body: root.querySelector('[data-terms-list-body]'),
        prevLink: root.querySelector('[data-terms-prev]'),
        nextLink: root.querySelector('[data-terms-next]'),
        perPageField: root.querySelector('[data-terms-per-page]'),
        searchField: root.querySelector('[data-terms-search]'),
        csrfInput: root.querySelector('[data-terms-csrf] input[name="_csrf"]'),
        fetchParams: (url, state) => {
            if (state.query !== '') {
                url.searchParams.set('q', state.query);
            }
        },
        renderRows: (items) => items.map((item) => {
            const id = Number(item.id || 0);
            return `<tr><td><a href="${esc(editBase)}${id}">${esc(item.name)}</a><div class="text-muted">${esc(item.created)}</div></td><td>${esc(item.body || '—')}</td><td class="table-col-actions"><button class="btn btn-light btn-icon" type="button" data-terms-delete-open="${id}" aria-label="Smazat štítek" title="Smazat štítek">${icon('delete')}<span class="sr-only">Smazat štítek</span></button></td></tr>`;
        }).join(''),
        onData: () => {},
    });

    bindDelete({
        root,
        openSelector: '[data-terms-delete-open]',
        modal: root.querySelector('[data-terms-delete-modal]'),
        cancel: root.querySelector('[data-terms-delete-cancel]'),
        confirm: root.querySelector('[data-terms-delete-confirm]'),
        getId: (node) => Number(node.getAttribute('data-terms-delete-open') || '0'),
        onConfirm: async (id) => {
            const ok = await api.postAction(`${endpoint.replace(/\/$/, '')}/delete`, { id }, 'Štítek smazán.');
            if (ok) {
                await api.fetchList();
            }
            return ok;
        },
    });
})();

(() => {
    const root = document.querySelector('[data-content-list]');
    if (!root) {
        return;
    }

    const endpoint = root.getAttribute('data-endpoint') || '';
    const editBase = root.getAttribute('data-edit-base') || '';
    const filterLinks = Array.from(root.querySelectorAll('[data-content-status]'));
    const api = createListApi({
        root,
        endpoint,
        body: root.querySelector('[data-content-list-body]'),
        prevLink: root.querySelector('[data-content-prev]'),
        nextLink: root.querySelector('[data-content-next]'),
        perPageField: root.querySelector('[data-content-per-page]'),
        searchField: root.querySelector('[data-content-search]'),
        csrfInput: root.querySelector('[data-content-csrf] input[name="_csrf"]'),
        fetchParams: (url, state) => {
            url.searchParams.set('status', state.status || 'all');
            if (state.query !== '') {
                url.searchParams.set('q', state.query);
            }
        },
        renderRows: (items) => items.map((item) => {
            const id = Number(item.id || 0);
            const status = String(item.status || 'draft');
            const statusClass = status === 'published' ? 'text-bg-success' : (status === 'draft' ? 'text-bg-dark' : 'text-bg-primary');
            const isPublished = status === 'published';
            const toggleLabel = isPublished ? 'Přepnout do draftu' : 'Publikovat';
            return `<tr><td><a href="${esc(editBase)}${id}">${esc(item.name)}</a><div class="text-muted">${esc(item.created)}</div><div class="d-flex gap-2 mt-2"><span class="badge ${esc(statusClass)}">${esc(status)}</span>${item.is_planned ? '<span class="badge text-bg-warning">Plánováno</span>' : ''}</div></td><td>${esc(item.author_name || '—')}</td><td class="table-col-actions"><button class="btn btn-light btn-icon" type="button" data-content-toggle="${id}" data-content-mode="${isPublished ? 'draft' : 'publish'}" aria-label="${esc(toggleLabel)}" title="${esc(toggleLabel)}">${icon(isPublished ? 'hide' : 'show')}<span class="sr-only">${esc(toggleLabel)}</span></button><button class="btn btn-light btn-icon" type="button" data-content-delete-open="${id}" aria-label="Smazat" title="Smazat">${icon('delete')}<span class="sr-only">Smazat</span></button></td></tr>`;
        }).join(''),
        onData: (state) => {
            filterLinks.forEach((link) => {
                link.classList.toggle('active', link.getAttribute('data-content-status') === state.status);
            });
        },
    });

    api.state.status = filterLinks.find((link) => link.classList.contains('active'))?.getAttribute('data-content-status') || 'all';

    root.addEventListener('click', async (event) => {
        const statusLink = event.target.closest('[data-content-status]');
        if (statusLink) {
            event.preventDefault();
            api.state.status = statusLink.getAttribute('data-content-status') || 'all';
            api.state.page = 1;
            await api.fetchList();
            return;
        }

        const toggle = event.target.closest('[data-content-toggle]');
        if (!toggle) {
            return;
        }

        event.preventDefault();
        const id = Number(toggle.getAttribute('data-content-toggle') || '0');
        const mode = toggle.getAttribute('data-content-mode') || 'draft';
        if (id <= 0) {
            return;
        }
        const ok = await api.postAction(`${endpoint.replace(/\/$/, '')}/status-toggle`, { id, mode }, mode === 'publish' ? 'Obsah publikován.' : 'Obsah přepnut do draftu.');
        if (ok) {
            await api.fetchList();
        }
    });

    bindDelete({
        root,
        openSelector: '[data-content-delete-open]',
        modal: root.querySelector('[data-content-delete-modal]'),
        cancel: root.querySelector('[data-content-delete-cancel]'),
        confirm: root.querySelector('[data-content-delete-confirm]'),
        getId: (node) => Number(node.getAttribute('data-content-delete-open') || '0'),
        onConfirm: async (id) => {
            const ok = await api.postAction(`${endpoint.replace(/\/$/, '')}/delete`, { id }, 'Obsah smazán.');
            if (ok) {
                await api.fetchList();
            }
            return ok;
        },
    });
})();
