(() => {
const i18n = window.tinycmsI18n || {};
const t = (path, fallback = '') => {
    const value = path.split('.').reduce((acc, key) => (acc && Object.prototype.hasOwnProperty.call(acc, key) ? acc[key] : undefined), i18n);
    return typeof value === 'string' && value !== '' ? value : fallback;
};

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

const emptyRowHtml = (columnsCount) => `
    <tr>
        <td colspan="${Math.max(1, Number(columnsCount) || 1)}" class="admin-list-empty">
            <svg class="admin-list-empty-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2314 1698" aria-hidden="true">
                <g transform="translate(0 1698) scale(0.1 -0.1)" fill="currentColor">
                    <path d="M5842 16965 c-56 -24 -65 -64 -65 -260 0 -198 17 -321 73 -540 54 -212 56 -229 31 -303 -36 -107 -23 -221 51 -428 47 -132 84 -193 143 -234 85 -58 176 -63 293 -15 35 14 72 23 82 20 13 -4 27 -32 46 -87 17 -52 39 -94 61 -119 70 -77 225 -108 464 -92 164 11 221 30 252 82 9 16 34 37 54 46 33 16 44 17 93 6 123 -28 162 -32 241 -21 157 22 298 68 434 145 190 106 211 188 89 347 -118 153 -232 252 -356 307 -146 66 -444 137 -618 147 -121 7 -145 18 -160 73 -15 55 -32 66 -109 66 l-66 0 -29 55 c-130 249 -301 464 -489 615 -160 129 -428 228 -515 190z"/>
                    <path d="M3275 15024 c-306 -73 -584 -264 -828 -569 -163 -203 -233 -332 -376 -690 -51 -126 -99 -240 -107 -252 -17 -27 -50 -29 -120 -8 -27 8 -63 15 -81 15 -33 0 -38 -4 -86 -80 -40 -62 -57 -61 -213 12 -208 98 -350 138 -563 159 -200 20 -370 -4 -506 -71 -126 -61 -279 -209 -347 -335 -30 -55 -33 -67 -33 -151 0 -82 3 -94 27 -131 15 -23 43 -53 62 -68 46 -35 223 -97 404 -141 197 -48 321 -93 487 -174 216 -105 370 -215 413 -296 5 -10 29 -77 52 -149 82 -253 131 -352 246 -501 152 -196 352 -331 574 -388 36 -9 117 -21 180 -27 99 -10 120 -9 152 4 67 28 101 105 118 265 22 198 59 244 158 193 20 -11 91 -63 157 -116 204 -162 305 -198 442 -157 206 63 384 285 438 547 36 174 28 494 -15 619 -42 123 -137 255 -296 411 -70 68 -115 121 -120 138 -10 43 21 76 111 120 89 43 167 116 205 193 24 49 24 51 24 324 l-1 275 -61 305 c-33 168 -68 355 -77 415 -35 233 -120 326 -294 324 -36 0 -93 -7 -126 -15z"/>
                    <path d="M10865 14563 c-98 -26 -120 -68 -130 -243 -3 -71 -16 -171 -30 -230 -13 -58 -33 -169 -45 -248 -11 -79 -33 -183 -47 -230 -14 -48 -36 -130 -48 -182 -28 -117 -90 -302 -118 -353 -12 -21 -51 -62 -87 -91 -296 -240 -536 -563 -623 -842 -80 -253 -100 -605 -53 -906 65 -410 128 -541 429 -887 293 -337 396 -480 463 -646 64 -157 74 -281 28 -366 -47 -85 -127 -140 -242 -165 -179 -37 -414 2 -552 92 -84 55 -285 264 -389 404 -140 188 -186 262 -347 550 -374 674 -568 962 -823 1224 -274 281 -579 488 -911 620 -241 95 -375 117 -680 112 -167 -3 -221 -8 -290 -25 -229 -58 -488 -244 -624 -449 -58 -88 -137 -263 -160 -353 -44 -177 7 -350 128 -437 57 -41 96 -57 170 -71 69 -13 85 -25 125 -95 36 -65 88 -111 166 -147 115 -54 160 -61 445 -68 250 -7 269 -9 332 -33 126 -47 247 -135 432 -309 144 -136 250 -259 412 -475 191 -256 244 -339 511 -804 79 -136 311 -581 434 -831 123 -250 170 -321 294 -444 102 -102 236 -206 639 -496 126 -90 274 -201 330 -247 55 -45 124 -98 153 -117 28 -19 55 -40 58 -45 4 -6 17 -57 30 -113 97 -429 362 -1195 600 -1732 118 -266 418 -876 533 -1084 130 -235 350 -583 432 -686 17 -22 72 -83 122 -134 64 -67 100 -114 122 -160 28 -61 31 -74 30 -166 0 -88 -5 -113 -38 -210 -62 -185 -134 -303 -310 -512 -90 -108 -155 -162 -251 -210 -146 -74 -232 -140 -412 -316 -266 -261 -323 -345 -330 -497 -5 -98 5 -124 56 -155 48 -28 81 -64 121 -131 73 -121 338 -353 502 -439 93 -49 135 -63 275 -90 74 -14 154 -37 200 -58 l78 -34 290 12 c241 11 301 17 355 34 163 51 314 204 384 386 44 117 55 135 82 145 14 6 95 10 179 10 160 0 223 8 313 41 139 50 291 189 361 329 54 108 60 166 31 282 -25 99 -22 145 11 215 39 81 70 79 379 -32 100 -36 238 -81 308 -101 70 -20 133 -40 140 -46 20 -17 14 -43 -27 -125 -86 -168 -80 -420 14 -618 42 -88 65 -110 151 -141 38 -13 116 -53 174 -88 149 -91 231 -130 367 -175 191 -64 306 -84 448 -78 128 5 196 22 318 77 83 37 140 39 205 6 95 -48 356 -62 495 -26 228 58 450 234 538 426 41 90 63 104 164 104 102 0 178 25 252 81 29 23 53 44 53 48 0 3 24 32 53 64 40 42 60 57 80 57 15 0 112 -42 215 -93 285 -142 469 -197 729 -218 155 -12 339 -2 480 27 43 8 323 96 623 194 648 212 696 227 910 280 363 91 740 134 978 112 350 -33 675 -160 965 -378 47 -35 92 -64 101 -64 37 0 49 95 20 156 -37 77 -193 178 -464 299 -395 178 -843 260 -1240 226 -250 -21 -401 -47 -1025 -175 -279 -57 -559 -96 -745 -103 -237 -9 -514 39 -665 114 -120 61 -250 177 -332 298 -96 143 -108 191 -100 423 13 407 -31 839 -124 1206 -38 149 -147 494 -192 606 -55 136 -258 538 -346 683 -269 448 -652 885 -1043 1190 -249 194 -481 327 -793 452 -340 137 -614 327 -883 613 -70 74 -190 200 -267 281 -149 155 -213 245 -350 481 -43 74 -117 200 -165 280 -218 362 -303 597 -317 870 -13 275 39 384 232 489 125 69 185 119 405 340 442 444 582 685 635 1091 14 106 50 320 71 425 4 17 9 116 13 222 l6 192 42 58 c178 245 339 535 401 723 91 278 172 705 172 910 0 88 -15 182 -36 222 -49 96 -230 98 -549 8 -93 -26 -249 -69 -345 -95 -212 -57 -440 -125 -700 -210 -107 -35 -244 -77 -304 -93 -123 -35 -130 -34 -211 27 -126 97 -409 256 -567 320 -79 32 -173 72 -210 89 -141 64 -458 156 -654 188 -54 9 -185 19 -291 23 l-192 6 -35 34 c-23 22 -50 69 -77 134 -59 140 -128 227 -363 462 -276 275 -381 343 -546 350 -41 2 -84 1 -95 -2z"/>
                </g>
            </svg>
            <div class="admin-list-empty-text">${esc(t('common.nothing_found', 'Nothing found.'))}</div>
        </td>
    </tr>
`;

const normalizeListResponse = (payload) => {
    const meta = payload && typeof payload.meta === 'object' ? payload.meta : {};
    return {
        items: Array.isArray(payload?.data) ? payload.data : [],
        page: Number(meta.page || 1),
        totalPages: Number(meta.total_pages || 1),
        perPage: Number(meta.per_page || 0),
        statusCounts: meta.status_counts && typeof meta.status_counts === 'object' ? meta.status_counts : {},
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

    container.querySelectorAll('.flash').forEach((node) => node.remove());

    const flashType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'warning';
    const flash = document.createElement('div');
    const uiType = flashType === 'info' ? 'warning' : flashType;
    const flashIcon = uiType === 'success' ? 'success' : (uiType === 'error' ? 'error' : 'warning');
    flash.className = `flash flash-${uiType}`;
    flash.innerHTML = `
        <span class="d-flex align-center gap-2">${icon(flashIcon)}<span>${esc(text)}</span></span>
        <button type="button" data-flash-close aria-label="${esc(t('common.close_notice', 'Close notification'))}" title="${esc(t('common.close_notice', 'Close notification'))}">
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
    const filterBaseLabels = {};
    filterLinks.forEach((link) => {
        const statusKey = link.getAttribute(`data-${config.name}-status`) || '';
        if (statusKey !== '') {
            filterBaseLabels[statusKey] = String(link.textContent || '').replace(/\s*\(\d+\)\s*$/, '').trim();
        }
    });
    const context = typeof config.getContext === 'function' ? config.getContext(root) : {};
    const loader = window.tinycmsLoader || null;

    const defaultPerPage = Number(perPageField?.value || perPageField?.querySelector('option')?.value || '10') || 10;

    let state = {
        page: 1,
        perPage: defaultPerPage,
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

    const syncStatusCounts = (statusCounts) => {
        if (!config.withStatus || !statusCounts || typeof statusCounts !== 'object') {
            return;
        }

        filterLinks.forEach((link) => {
            const statusKey = link.getAttribute(`data-${config.name}-status`) || '';
            const baseLabel = filterBaseLabels[statusKey];
            if (statusKey === '' || typeof baseLabel !== 'string') {
                return;
            }

            const count = Number(statusCounts[statusKey] ?? 0);
            link.textContent = `${baseLabel} (${Number.isFinite(count) ? count : 0})`;
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

        if (loader) {
            loader.set(root, true);
        }
        try {
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
            state.page = Math.max(1, normalized.page || 1);
            if (normalized.perPage > 0) {
                state.perPage = normalized.perPage;
            }
            body.innerHTML = normalized.items.length > 0
                ? normalized.items.map((item) => config.rowHtml(item, { editBase, context })).join('')
                : emptyRowHtml(config.columnsCount);
            setPagination(state.page, normalized.totalPages);
            syncStatusCounts(normalized.statusCounts);
            syncFilters();
        } finally {
            if (loader) {
                loader.set(root, false);
            }
        }
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
            state.perPage = Number(perPageField.value || String(defaultPerPage)) || defaultPerPage;
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
        deleteSuccess: t('content.deleted', 'Content deleted.'),
        toggleSuccess: (mode) => mode === 'publish' ? t('content.published', 'Content published.') : t('content.switched_to_draft', 'Content switched to draft.'),
    },
    columnsCount: 3,
    rowHtml: (item, { editBase }) => {
        const status = String(item.status || 'draft');
        const isPublished = status === 'published';
        const statusIcon = status === 'published' ? 'success' : (status === 'draft' ? 'concept' : '');
        const toggleLabel = isPublished ? t('content.switch_to_draft', 'Switch to draft') : t('content.publish', 'Publish');
        const canEdit = item.can_edit === true;
        const canDelete = item.can_delete === true;

        return `
            <tr>
                <td>
                    <span class="d-flex align-center gap-2">
                        ${statusIcon !== '' ? icon(statusIcon) : ''}
                        ${canEdit
        ? `<a href="${esc(editBase)}${Number(item.id || 0)}">${esc(item.name)}</a>`
        : `<span>${esc(item.name)}</span>`}
                    </span>
                    <div class="text-muted small">${esc(item.created_label || item.created)}</div>
                    ${item.is_planned ? `<div class="mt-2"><span class="badge text-bg-warning">${esc(t('content.planned', 'Planned'))}</span></div>` : ''}
                </td>
                <td class="mobile-hide">${esc(item.author_name || '—')}</td>
                <td class="table-col-actions">
                    ${canEdit ? `
                    <button class="btn btn-light btn-icon" type="button" data-content-toggle="${Number(item.id || 0)}" data-content-mode="${isPublished ? 'draft' : 'publish'}" aria-label="${esc(toggleLabel)}" title="${esc(toggleLabel)}">
                        ${icon(isPublished ? 'hide' : 'show')}
                        <span class="sr-only">${esc(toggleLabel)}</span>
                    </button>
                    ` : ''}
                    ${canDelete ? `
                    <button class="btn btn-light btn-icon" type="button" data-content-delete-open="${Number(item.id || 0)}" aria-label="${esc(t('common.delete', 'Delete'))}" title="${esc(t('common.delete', 'Delete'))}">
                        ${icon('delete')}
                        <span class="sr-only">${esc(t('common.delete', 'Delete'))}</span>
                    </button>
                    ` : ''}
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
    messages: { deleteSuccess: t('terms.deleted', 'Tag deleted.') },
    columnsCount: 3,
    rowHtml: (item, { editBase }) => {
        const id = Number(item.id || 0);

        return `
            <tr>
                <td>
                    <a href="${esc(editBase)}${id}">${esc(item.name)}</a>
                    <div class="text-muted small">${esc(item.created_label || item.created)}</div>
                </td>
                <td class="mobile-hide">${esc(item.body || '—')}</td>
                <td class="table-col-actions">
                    <button class="btn btn-light btn-icon" type="button" data-terms-delete-open="${id}" aria-label="${esc(t('terms.delete', 'Delete tag'))}" title="${esc(t('terms.delete', 'Delete tag'))}">
                        ${icon('delete')}
                        <span class="sr-only">${esc(t('terms.delete', 'Delete tag'))}</span>
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
    messages: { deleteSuccess: t('media.deleted', 'Media deleted.') },
    getContext: (root) => ({ thumbSuffix: root.getAttribute('data-thumb-suffix') || '_100x100.webp' }),
    columnsCount: 3,
    rowHtml: (item, { editBase, context }) => {
        const thumbSuffix = context.thumbSuffix || '_100x100.webp';
        const id = Number(item.id || 0);
        const preview = String(item.preview_path || '');
        const webp = String(item.path_webp || '');
        const img = preview !== '' ? preview : (webp !== '' ? webp.replace(/\.webp$/i, thumbSuffix) : String(item.path || ''));
        const canEdit = item.can_edit === true;
        const canDelete = item.can_delete === true;

        return `
            <tr>
                <td>
                    <div class="d-flex align-center gap-2">
                        ${img !== ''
        ? `<div class="media-list-thumb"><img src="${esc(img)}" alt="${esc(item.name)}"></div>`
        : '<div class="media-list-thumb media-list-thumb-empty"></div>'}
                        <div>
                            ${canEdit ? `<a href="${esc(editBase)}${id}">${esc(item.name)}</a>` : `<span>${esc(item.name)}</span>`}
                            <div class="text-muted small">${esc(item.path)}</div>
                            <div class="text-muted small">${esc(item.created_label || item.created)}</div>
                        </div>
                    </div>
                </td>
                <td class="mobile-hide">${esc(item.author_name || '—')}</td>
                <td class="table-col-actions">
                    ${canDelete ? `
                    <button class="btn btn-light btn-icon" type="button" data-media-delete-open="${id}" aria-label="${esc(t('media.delete', 'Delete media'))}" title="${esc(t('media.delete', 'Delete media'))}">
                        ${icon('delete')}
                        <span class="sr-only">${esc(t('media.delete', 'Delete media'))}</span>
                    </button>
                    ` : ''}
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
        deleteSuccess: t('users.deleted', 'User deleted.'),
        toggleSuccess: (mode) => mode === 'unsuspend' ? t('users.unsuspended', 'User unsuspended.') : t('users.suspended', 'User suspended.'),
    },
    columnsCount: 2,
    rowHtml: (item, { editBase }) => {
        const id = Number(item.id || 0);
        const isSuspended = item.is_suspended === true;
        const isAdmin = item.is_admin === true;
        const toggleLabel = isSuspended ? t('users.unsuspend', 'Unsuspend') : t('users.suspend', 'Suspend');

        return `
            <tr>
                <td>
                    <a href="${esc(editBase)}${id}">${esc(item.name)}</a>
                    <div class="text-muted small">${esc(item.email)}</div>
                    <div class="d-flex gap-2 mt-2">
                        <span class="badge text-bg-primary">${esc(t(`users.roles.${String(item.role || '')}`, String(item.role || '')))}</span>
                        ${isSuspended ? `<span class="badge text-bg-warning">${esc(t('users.status_suspended_single', 'Suspended'))}</span>` : ''}
                    </div>
                </td>
                <td class="table-col-actions">
                    ${isAdmin ? '' : `
                    <button class="btn btn-light btn-icon" type="button" data-users-toggle="${id}" data-users-mode="${isSuspended ? 'unsuspend' : 'suspend'}" aria-label="${esc(toggleLabel)}" title="${esc(toggleLabel)}">
                        ${icon(isSuspended ? 'show' : 'hide')}
                        <span class="sr-only">${esc(toggleLabel)}</span>
                    </button>
                    <button class="btn btn-light btn-icon" type="button" data-users-delete-open="${id}" aria-label="${esc(t('users.delete', 'Delete user'))}" title="${esc(t('users.delete', 'Delete user'))}">
                        ${icon('delete')}
                        <span class="sr-only">${esc(t('users.delete', 'Delete user'))}</span>
                    </button>`}
                </td>
            </tr>
        `;
    },
});
})();
