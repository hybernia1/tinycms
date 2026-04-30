(() => {
    const app = window.tinycms = window.tinycms || {};
    const api = app.api = app.api || {};
    const t = app.i18n?.t || (() => '');
    const requestJson = api.http?.requestJson;
    const postForm = api.http?.postForm;
    const pushFlash = api.pushFlash || (() => {});
    const sessionStore = app.support?.sessionStore || { get: () => '', set: () => {} };
    const confirmModal = app.ui?.modal?.confirm || (() => Promise.resolve(false));

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
        const statusStorageKey = `tinycms.${config.name}.activeStatus`;
        const statusExists = (status) => filterLinks.some((link) => link.getAttribute(`data-${config.name}-status`) === status);
        const activeStatus = () => filterLinks.find((link) => link.classList.contains('active'))?.getAttribute(`data-${config.name}-status`) || 'all';
        const initialStatus = config.withStatus ? activeStatus() : 'all';
        const context = typeof config.getContext === 'function' ? config.getContext(root) : {};
        const loader = app.loader || null;

        let state = {
            page: 1,
            query: searchField?.value.trim() || '',
        };

        if (config.withStatus) {
            const urlStatus = new URLSearchParams(window.location.search).get('status') || '';
            const storedStatus = sessionStore.get(statusStorageKey);
            state = {
                ...state,
                status: statusExists(urlStatus) ? urlStatus : (statusExists(storedStatus) ? storedStatus : initialStatus),
            };
            sessionStore.set(statusStorageKey, state.status);
        }

        let searchTimer = null;
        let fetchController = null;

        const setButtonDisabled = (button, disabled) => {
            if (!button) {
                return;
            }
            button.disabled = disabled;
            button.classList.toggle('disabled', disabled);
            button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        };

        const setPagination = (page, totalPages) => {
            if (!prevLink || !nextLink) {
                return;
            }

            setButtonDisabled(prevLink, page <= 1);
            setButtonDisabled(nextLink, page >= totalPages);
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
                if (!responseResult || !responseResult.response || !responseResult.response.ok) {
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
            if (!response.ok || !normalized.success) {
                const errorCode = String(normalized.errorCode || '');
                const fallbackMessage = errorCode === 'INVALID_CSRF' ? t('common.invalid_csrf') : '';
                const message = String(normalized.message || fallbackMessage || '').trim();
                if (message !== '') {
                    pushFlash('error', message);
                }
                return {
                    success: false,
                    message,
                    errorCode,
                    errors: normalized.errors && typeof normalized.errors === 'object' ? normalized.errors : {},
                };
            }

            return {
                success: normalized.success,
                message: normalized.message,
                errorCode: normalized.errorCode,
                errors: normalized.errors && typeof normalized.errors === 'object' ? normalized.errors : {},
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
                    sessionStore.set(statusStorageKey, state.status);
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
                        const result = await postAction(config.togglePath(endpointBase, id), { id, mode });
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
                        const result = await postAction(config.restorePath(endpointBase, id), { id });
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
                const deleteId = Number(delOpen.getAttribute(`data-${config.name}-delete-open`) || '0');
                const deleteMode = delOpen.getAttribute(`data-${config.name}-delete-mode`) === 'hard' ? 'hard' : 'soft';
                if (deleteId <= 0) {
                    return;
                }

                const message = config.messages?.deleteConfirm
                    ? (deleteMode === 'hard' ? config.messages.deleteConfirm.hard : config.messages.deleteConfirm.soft)
                    : t(`${config.name}.delete_confirm`);
                if (!await confirmModal({ message })) {
                    return;
                }
                const deletePath = typeof config.deletePath === 'function'
                    ? config.deletePath(endpointBase, deleteId)
                    : `${endpointBase}/delete`;
                const result = await postAction(deletePath, { id: deleteId });
                if (result.success === true) {
                    if (config.messages?.deleteSuccess) {
                        pushFlash('success', result.message || config.messages.deleteSuccess);
                    }
                    await fetchList();
                }
            }
        });

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

        if (config.withStatus && state.status !== initialStatus) {
            syncFilters();
            fetchList();
        }
    };

    const renderers = api.listRenderers || {};

    const contentListConfig = () => ({
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
        rowHtml: renderers.contentRowHtml,
    });

    const termsListConfig = () => ({
        name: 'terms',
        rootSelector: '[data-terms-list]',
        withStatus: true,
        deletePath: (endpointBase, id) => `${endpointBase}/${id}/delete`,
        messages: { deleteSuccess: t('terms.deleted') },
        rowHtml: renderers.termsRowHtml,
    });

    const mediaListConfig = () => ({
        name: 'media',
        rootSelector: '[data-media-list]',
        withStatus: true,
        deletePath: (endpointBase, id) => `${endpointBase}/${id}/delete`,
        messages: { deleteSuccess: t('media.deleted') },
        rowHtml: renderers.mediaRowHtml,
    });

    const usersListConfig = () => ({
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
        rowHtml: renderers.usersRowHtml,
    });

    const initLists = () => {
        [
            contentListConfig,
            termsListConfig,
            mediaListConfig,
            usersListConfig,
        ].forEach((config) => initListApi(config()));
    };

    initLists();
})();
