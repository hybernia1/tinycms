(() => {
    const app = window.tinycms = window.tinycms || {};
    const api = app.api = app.api || {};
    const t = app.i18n?.t || (() => '');
    const postForm = api.http?.postForm;
    const pushFlash = api.pushFlash || (() => {});
    const sessionStore = app.support?.sessionStore || { get: () => '', set: () => {} };
    const confirmModal = app.ui?.modal?.confirm || (() => Promise.resolve(false));

    const initListApi = (config) => {
        if (typeof postForm !== 'function') {
            return;
        }

        const root = document.querySelector(config.rootSelector);
        if (!root || root.hasAttribute('data-list-ready')) {
            return;
        }
        root.setAttribute('data-list-ready', '1');

        const endpoint = root.getAttribute('data-endpoint') || '';
        const actionBase = root.getAttribute('data-action-base') || endpoint;
        const endpointBase = actionBase.replace(/\/$/, '');
        const csrfInput = root.querySelector(`[data-${config.name}-csrf] input[name="_csrf"]`);
        const searchField = root.querySelector(`[data-${config.name}-search]`);
        const filterLinks = config.withStatus
            ? Array.from(root.querySelectorAll(`[data-${config.name}-status]`))
            : [];
        const statusStorageKey = `tinycms.${config.name}.activeStatus`;
        const statusExists = (status) => filterLinks.some((link) => link.getAttribute(`data-${config.name}-status`) === status);
        const activeStatus = () => filterLinks.find((link) => link.classList.contains('active'))?.getAttribute(`data-${config.name}-status`) || 'all';
        const initialStatus = config.withStatus ? activeStatus() : 'all';
        const loader = app.loader || null;

        let state = {
            page: Math.max(1, Number(root.getAttribute('data-page') || 1)),
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

        const syncFilters = () => {
            if (!config.withStatus) {
                return;
            }

            filterLinks.forEach((link) => {
                link.classList.toggle('active', link.getAttribute(`data-${config.name}-status`) === state.status);
            });
        };

        const replaceList = (html, focusSearch = false) => {
            const template = document.createElement('template');
            template.innerHTML = String(html || '').trim();
            const nextRoot = template.content.querySelector(config.rootSelector);
            if (!nextRoot) {
                return;
            }

            root.replaceWith(nextRoot);
            initListApi(config);
            if (focusSearch) {
                const nextSearch = nextRoot.querySelector(`[data-${config.name}-search]`);
                if (nextSearch instanceof HTMLInputElement) {
                    nextSearch.focus();
                    const length = nextSearch.value.length;
                    nextSearch.setSelectionRange(length, length);
                }
            }
        };

        const fetchList = async () => {
            if (!endpoint) {
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

                const focusSearch = document.activeElement === searchField;
                const response = await fetch(url.toString(), {
                    headers: { Accept: 'text/html' },
                    signal: fetchController.signal,
                }).catch((error) => {
                    if (error instanceof DOMException && error.name === 'AbortError') {
                        return null;
                    }
                    throw error;
                });
                if (!response || !response.ok) {
                    return;
                }

                replaceList(await response.text(), focusSearch);
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
                    if (id > 0 && typeof config.togglePath === 'function') {
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
                    if (id > 0 && typeof config.restorePath === 'function') {
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
    });

    const termsListConfig = () => ({
        name: 'terms',
        rootSelector: '[data-terms-list]',
        withStatus: true,
        deletePath: (endpointBase, id) => `${endpointBase}/${id}/delete`,
        messages: { deleteSuccess: t('terms.deleted') },
    });

    const commentsListConfig = () => ({
        name: 'comments',
        rootSelector: '[data-comments-list]',
        withStatus: true,
        restore: true,
        toggle: { defaultMode: 'draft' },
        togglePath: (endpointBase, id) => `${endpointBase}/${id}/status`,
        restorePath: (endpointBase, id) => `${endpointBase}/${id}/restore`,
        deletePath: (endpointBase, id) => `${endpointBase}/${id}/delete`,
        messages: {
            deleteSuccess: t('comments.moved_to_trash'),
            deleteConfirm: {
                soft: t('comments.delete_confirm_move_to_trash'),
                hard: t('comments.delete_confirm_hard_delete'),
            },
            restoreSuccess: t('comments.restored'),
            toggleSuccess: (mode) => mode === 'publish' ? t('comments.published') : t('comments.switched_to_draft'),
        },
    });

    const mediaListConfig = () => ({
        name: 'media',
        rootSelector: '[data-media-list]',
        withStatus: true,
        deletePath: (endpointBase, id) => `${endpointBase}/${id}/delete`,
        messages: { deleteSuccess: t('media.deleted') },
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
    });

    const initLists = () => {
        [
            contentListConfig,
            commentsListConfig,
            termsListConfig,
            mediaListConfig,
            usersListConfig,
        ].forEach((config) => initListApi(config()));
    };

    initLists();
})();
