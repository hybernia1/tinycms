(() => {
    const t = window.tinycms?.i18n?.t || (() => '');

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
            <button type="button" data-flash-close aria-label="${esc(t('common.close_notice'))}" title="${esc(t('common.close_notice'))}">
                ${icon('cancel')}
            </button>
        `;
        container.prepend(flash);
    };

    const storeFlash = (type, message) => {
        const text = String(message || '').trim();
        if (text === '') {
            return;
        }

        try {
            window.sessionStorage.setItem('tinycms:flash', JSON.stringify({
                type,
                message: text,
            }));
        } catch (_) {
        }
    };

    const consumeStoredFlash = () => {
        try {
            const raw = window.sessionStorage.getItem('tinycms:flash');
            if (!raw) {
                return;
            }
            window.sessionStorage.removeItem('tinycms:flash');
            const payload = JSON.parse(raw);
            pushFlash((payload && payload.type) || 'success', (payload && payload.message) || '');
        } catch (_) {
        }
    };

    consumeStoredFlash();

    window.tinycms = window.tinycms || {};
    window.tinycms.api = window.tinycms.api || {};
    window.tinycms.api.flash = {
        push: pushFlash,
        store: storeFlash,
        consume: consumeStoredFlash,
    };
    window.tinycms.api = {
        ...window.tinycms.api,
        esc,
        icon,
        pushFlash,
        storeFlash,
    };
})();
(() => {
    const pushFlash = window.tinycms?.api?.pushFlash || (() => {});
    const storeFlash = window.tinycms?.api?.storeFlash || (() => {});
    const t = window.tinycms?.i18n?.t || (() => '');

    const showError = (message) => {
        const text = String(message || '').trim();
        if (text !== '') {
            pushFlash('error', text);
        }
    };

    const escapeSelector = (value) => {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }
        return String(value).replace(/["\\]/g, '\\$&');
    };

    const clearFieldErrors = (form) => {
        form.querySelectorAll('.api-field-error').forEach((node) => node.remove());
        form.querySelectorAll('[aria-invalid="true"]').forEach((node) => node.removeAttribute('aria-invalid'));
    };

    const findField = (form, name) => {
        const normalized = String(name || '').trim();
        if (normalized === '') {
            return null;
        }

        const direct = form.querySelector(`[name="${escapeSelector(normalized)}"]`);
        if (direct) {
            return direct;
        }

        if (!normalized.includes('[')) {
            const bracketed = form.querySelector(`[name="settings[${escapeSelector(normalized)}]"]`);
            if (bracketed) {
                return bracketed;
            }
        }

        return null;
    };

    const applyFieldErrors = (form, errors) => {
        if (!errors || typeof errors !== 'object') {
            return;
        }

        Object.entries(errors).forEach(([name, message]) => {
            const field = findField(form, name);
            const text = String(message || '').trim();
            if (!field || text === '') {
                return;
            }

            field.setAttribute('aria-invalid', 'true');
            const error = document.createElement('small');
            error.className = 'text-danger api-field-error';
            error.textContent = text;
            field.insertAdjacentElement('afterend', error);
        });
    };

    const submitApiForm = async (form) => {
        const response = await fetch(form.action, {
            method: (form.method || 'POST').toUpperCase(),
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const payload = await response.json().catch(() => null);
        if (!response.ok || payload?.ok !== true) {
            clearFieldErrors(form);
            applyFieldErrors(form, payload?.error?.errors || {});
            showError(payload?.error?.message || '');
            return;
        }

        clearFieldErrors(form);

        const payloadRedirect = String(payload?.data?.redirect || '').trim();
        const fallbackRedirect = String(form.getAttribute('data-redirect-url') || '').trim();
        const redirect = payloadRedirect !== '' ? payloadRedirect : fallbackRedirect;
        const successMessage = String(payload?.data?.message || '').trim();
        if (redirect !== '') {
            if (successMessage !== '') {
                storeFlash('success', successMessage);
            } else {
                const fallbackMessage = t('common.saved', '');
                if (fallbackMessage !== '') {
                    storeFlash('success', fallbackMessage);
                }
            }
            const target = /^https?:\/\//i.test(redirect) || redirect.startsWith('/')
                ? redirect
                : '/' + redirect.replace(/^\/+/, '');
            window.location.href = target;
            return;
        }

        if (successMessage !== '') {
            pushFlash('success', successMessage);
        }

        if (form.hasAttribute('data-stay-on-page')) {
            return;
        }

        window.location.reload();
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-api-submit')) {
            return;
        }

        event.preventDefault();
        submitApiForm(form);
    });

    window.tinycms = window.tinycms || {};
    window.tinycms.api = window.tinycms.api || {};
    window.tinycms.api.form = {
        submit: submitApiForm,
        init: () => {},
    };
})();
(() => {
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

    const requestJson = async (url, options = {}) => {
        const response = await fetch(url, options);
        const raw = await response.json().catch(() => ({}));
        return { response, data: normalizePayload(raw), raw };
    };

    const postForm = async (url, formOrData, options = {}) => {
        const body = formOrData instanceof FormData ? formOrData : new FormData(formOrData);
        const requestOptions = {
            ...options,
            method: 'POST',
            body,
            headers: {
                Accept: 'application/json',
                ...(options.headers || {}),
            },
        };
        return requestJson(url, requestOptions);
    };

    window.tinycms = window.tinycms || {};
    window.tinycms.api = window.tinycms.api || {};
    window.tinycms.api.http = {
        normalizePayload,
        requestJson,
        postForm,
    };
})();
(() => {
const t = window.tinycms?.i18n?.t || (() => '');
const esc = window.tinycms?.api?.esc || ((value) => String(value || ''));
const icon = window.tinycms?.api?.icon || (() => '');
const pushFlash = window.tinycms?.api?.pushFlash || (() => {});
const requestJson = window.tinycms?.api?.http?.requestJson;
const postForm = window.tinycms?.api?.http?.postForm;

const normalizeListResponse = (payload) => {
    const meta = payload && typeof payload.meta === 'object' ? payload.meta : {};
    return {
        items: Array.isArray(payload?.data) ? payload.data : [],
        page: Number(meta.page || 1),
        totalPages: Number(meta.total_pages || 1),
        statusCounts: meta.status_counts && typeof meta.status_counts === 'object' ? meta.status_counts : {},
    };
};

const initListApi = (config) => {
    if (typeof requestJson !== 'function' || typeof postForm !== 'function') {
        return;
    }
    const root = document.querySelector(config.rootSelector);
    if (!root) {
        return;
    }

    const endpoint = root.getAttribute('data-endpoint') || '';
    const endpointBase = endpoint.replace(/\/$/, '');
    const editBase = root.getAttribute('data-edit-base') || '';
    const csrfInput = root.querySelector(`[data-${config.name}-csrf] input[name="_csrf"]`);
    const searchField = root.querySelector(`[data-${config.name}-search]`);
    const body = root.querySelector(`[data-${config.name}-list-body]`);
    const prevLink = root.querySelector(`[data-${config.name}-prev]`);
    const nextLink = root.querySelector(`[data-${config.name}-next]`);
    const deleteModal = root.querySelector(`[data-${config.name}-delete-modal]`)
        || document.querySelector(`[data-${config.name}-delete-modal]`);
    const deleteCancel = deleteModal?.querySelector('[data-modal-close]');
    const deleteConfirm = deleteModal?.querySelector('[data-modal-confirm]');
    const deleteModalText = deleteModal?.querySelector('[data-modal-text]');
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

    if (deleteModal && deleteModal.parentElement !== document.body) {
        document.body.appendChild(deleteModal);
    }

    let state = {
        page: 1,
        query: searchField?.value.trim() || '',
    };

    if (config.withStatus) {
        state = {
            ...state,
            status: filterLinks.find((link) => link.classList.contains('active'))?.getAttribute(`data-${config.name}-status`) || 'all',
        };
    }

    let pendingDeleteId = 0;
    let pendingDeleteMode = 'soft';
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

            if (config.withStatus) {
                url.searchParams.set('status', state.status);
            }

            if (state.query !== '') {
                url.searchParams.set('q', state.query);
            }

            const responseResult = await requestJson(url.toString(), {
                headers: { Accept: 'application/json' },
                signal: fetchController.signal,
            }).catch((error) => {
                if (error instanceof DOMException && error.name === 'AbortError') {
                    return null;
                }
                throw error;
            });
            if (!responseResult || !responseResult.response) {
                return;
            }
            if (!responseResult.response.ok) {
                return;
            }
            const normalized = normalizeListResponse(responseResult.data);
            state.page = Math.max(1, normalized.page || 1);
            body.innerHTML = normalized.items.map((item) => config.rowHtml(item, { editBase, context })).join('');
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

        const result = await postForm(path, formData);
        const response = result.response;
        const normalized = result.data;
        if (!response.ok) {
            const message = normalized.message;
            if (message !== '') {
                pushFlash('error', message);
            }
            return { success: false };
        }
        if (!normalized.success && normalized.message !== '') {
            pushFlash('error', normalized.message);
        }

        return {
            success: normalized.success,
            message: normalized.message,
            ...(normalized.data && typeof normalized.data === 'object' ? normalized.data : {}),
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
                    if (typeof config.togglePath !== 'function') {
                        return;
                    }
                    const togglePath = config.togglePath(endpointBase, id);
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

        if (config.restore) {
            const restore = event.target.closest(`[data-${config.name}-restore]`);
            if (restore) {
                event.preventDefault();
                const id = Number(restore.getAttribute(`data-${config.name}-restore`) || '0');
                if (id > 0) {
                    if (typeof config.restorePath !== 'function') {
                        return;
                    }
                    const restorePath = config.restorePath(endpointBase, id);
                    const result = await postAction(restorePath, { id });
                    if (result.success === true) {
                        if (config.messages?.restoreSuccess) {
                            pushFlash('success', result.message || config.messages.restoreSuccess);
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
            pendingDeleteMode = delOpen.getAttribute(`data-${config.name}-delete-mode`) === 'hard' ? 'hard' : 'soft';
            if (deleteModalText && config.messages?.deleteConfirm) {
                deleteModalText.textContent = pendingDeleteMode === 'hard'
                    ? config.messages.deleteConfirm.hard
                    : config.messages.deleteConfirm.soft;
            }
            if (deleteModal) {
                deleteModal.classList.add('open');
            }
        }
    });

    if (deleteCancel) {
        deleteCancel.addEventListener('click', () => {
            pendingDeleteId = 0;
            pendingDeleteMode = 'soft';
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
                pendingDeleteMode = 'soft';
                if (deleteModal) {
                    deleteModal.classList.remove('open');
                }
                if (config.messages?.deleteSuccess) {
                    pushFlash('success', result.message || config.messages.deleteSuccess);
                }
                await fetchList();
            }
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

window.tinycms = window.tinycms || {};
window.tinycms.api = window.tinycms.api || {};
window.tinycms.api.list = {
    init: initListApi,
};

initListApi({
    name: 'content',
    rootSelector: '[data-content-list]',
    withStatus: true,
    restore: true,
    toggle: { defaultMode: 'draft' },
    togglePath: (endpointBase, id) => `${endpointBase}/${id}/status`,
    restorePath: (endpointBase, id) => `${endpointBase}/${id}/restore`,
    deletePath: (endpointBase, id) => `${endpointBase}/${id}/delete`,
    messages: {
        deleteSuccess: t('content.moved_to_trash'),
        deleteConfirm: {
            soft: t('content.delete_confirm_move_to_trash'),
            hard: t('content.delete_confirm_hard_delete'),
        },
        restoreSuccess: t('content.restored'),
        toggleSuccess: (mode) => mode === 'publish' ? t('content.published') : t('content.switched_to_draft'),
    },
    rowHtml: (item, { editBase }) => {
        const status = String(item.status || 'draft');
        const isTrash = status === 'trash';
        const isPublished = status === 'published';
        const isPlanned = item.is_planned === true;
        const statusIcon = isPlanned ? 'calendar' : (status === 'published' ? 'success' : (status === 'draft' ? 'concept' : 'warning'));
        const toggleLabel = isPublished ? t('content.switch_to_draft') : t('content.publish');
        const canEdit = item.can_edit === true;
        const canDelete = item.can_delete === true;
        const canRestore = item.can_restore === true;

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
                </td>
                <td class="mobile-hide">${esc(item.author_name || '—')}</td>
                <td class="table-col-actions">
                    ${canEdit && !isTrash ? `
                    <button class="btn btn-light btn-icon" type="button" data-content-toggle="${Number(item.id || 0)}" data-content-mode="${isPublished ? 'draft' : 'publish'}" aria-label="${esc(toggleLabel)}" title="${esc(toggleLabel)}">
                        ${icon(isPublished ? 'hide' : 'show')}
                        <span class="sr-only">${esc(toggleLabel)}</span>
                    </button>
                    ` : ''}
                    ${canRestore ? `
                    <button class="btn btn-light btn-icon" type="button" data-content-restore="${Number(item.id || 0)}" aria-label="${esc(t('content.restore'))}" title="${esc(t('content.restore'))}">
                        ${icon('restore')}
                        <span class="sr-only">${esc(t('content.restore'))}</span>
                    </button>
                    ` : ''}
                    ${canDelete ? `
                    <button class="btn btn-light btn-icon" type="button" data-content-delete-open="${Number(item.id || 0)}" data-content-delete-mode="${canRestore ? 'hard' : 'soft'}" aria-label="${esc(t('common.delete'))}" title="${esc(t('common.delete'))}">
                        ${icon('delete')}
                        <span class="sr-only">${esc(t('common.delete'))}</span>
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
    withStatus: true,
    deletePath: (endpointBase, id) => `${endpointBase}/${id}/delete`,
    messages: { deleteSuccess: t('terms.deleted') },
    rowHtml: (item, { editBase }) => {
        const id = Number(item.id || 0);

        return `
            <tr>
                <td>
                    <a href="${esc(editBase)}${id}">${esc(item.name)}</a>
                    <div class="text-muted small">${esc(item.created_label || item.created)}</div>
                </td>
                <td class="table-col-actions">
                    <button class="btn btn-light btn-icon" type="button" data-terms-delete-open="${id}" aria-label="${esc(t('terms.delete'))}" title="${esc(t('terms.delete'))}">
                        ${icon('delete')}
                        <span class="sr-only">${esc(t('terms.delete'))}</span>
                    </button>
                </td>
            </tr>
        `;
    },
});

initListApi({
    name: 'media',
    rootSelector: '[data-media-list]',
    withStatus: true,
    deletePath: (endpointBase, id) => `${endpointBase}/${id}/delete`,
    messages: { deleteSuccess: t('media.deleted') },
    rowHtml: (item, { editBase }) => {
        const id = Number(item.id || 0);
        const preview = String(item.preview_path || '');
        const webp = String(item.path_webp || '');
        const img = preview !== '' ? preview : (webp !== '' ? webp : String(item.path || ''));
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
                    <button class="btn btn-light btn-icon" type="button" data-media-delete-open="${id}" aria-label="${esc(t('media.delete'))}" title="${esc(t('media.delete'))}">
                        ${icon('delete')}
                        <span class="sr-only">${esc(t('media.delete'))}</span>
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
    toggle: { defaultMode: 'suspend' },
    togglePath: (endpointBase, id) => `${endpointBase}/${id}/suspend`,
    deletePath: (endpointBase, id) => `${endpointBase}/${id}/delete`,
    messages: {
        deleteSuccess: t('users.deleted'),
        toggleSuccess: (mode) => mode === 'unsuspend' ? t('users.unsuspended') : t('users.suspended'),
    },
    rowHtml: (item, { editBase }) => {
        const id = Number(item.id || 0);
        const isSuspended = item.is_suspended === true;
        const isAdmin = item.is_admin === true;
        const statusIcon = isSuspended ? 'suspended' : (isAdmin ? 'admin' : 'users');
        const toggleLabel = isSuspended ? t('users.unsuspend') : t('users.suspend');

        return `
            <tr>
                <td>
                    <span class="d-flex align-center gap-2">
                        ${icon(statusIcon)}
                        <a href="${esc(editBase)}${id}">${esc(item.name)}</a>
                    </span>
                    <div class="text-muted small">${esc(item.email)}</div>
                </td>
                <td class="table-col-actions">
                    ${isAdmin ? '' : `
                    <button class="btn btn-light btn-icon" type="button" data-users-toggle="${id}" data-users-mode="${isSuspended ? 'unsuspend' : 'suspend'}" aria-label="${esc(toggleLabel)}" title="${esc(toggleLabel)}">
                        ${icon(isSuspended ? 'show' : 'hide')}
                        <span class="sr-only">${esc(toggleLabel)}</span>
                    </button>
                    <button class="btn btn-light btn-icon" type="button" data-users-delete-open="${id}" aria-label="${esc(t('users.delete'))}" title="${esc(t('users.delete'))}">
                        ${icon('delete')}
                        <span class="sr-only">${esc(t('users.delete'))}</span>
                    </button>`}
                </td>
            </tr>
        `;
    },
});
})();
