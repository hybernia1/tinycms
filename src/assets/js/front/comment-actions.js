(() => {
    const app = window.tinycms = window.tinycms || {};

    const closeForm = (button, form) => {
        if (!form) {
            return;
        }
        form.hidden = true;
        button.setAttribute('aria-expanded', 'false');
    };

    const initReplies = (root = document) => {
        root.querySelectorAll('[data-comment-reply]').forEach((button) => {
            if (button.dataset.commentReplyReady === '1') {
                return;
            }

            const target = String(button.getAttribute('data-comment-reply-target') || '').trim();
            const form = target !== '' ? document.getElementById(target) : null;
            if (!form) {
                return;
            }

            button.dataset.commentReplyReady = '1';
            form.hidden = true;
            button.setAttribute('aria-expanded', 'false');

            button.addEventListener('click', () => {
                const willOpen = form.hidden;
                document.querySelectorAll('[data-comment-reply]').forEach((otherButton) => {
                    const otherTarget = String(otherButton.getAttribute('data-comment-reply-target') || '').trim();
                    if (otherTarget !== target) {
                        closeForm(otherButton, otherTarget !== '' ? document.getElementById(otherTarget) : null);
                    }
                });

                form.hidden = !willOpen;
                button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                if (willOpen) {
                    form.querySelector('textarea')?.focus();
                }
            });
        });
    };

    const loadMore = async (list, button) => {
        const endpoint = String(list.dataset.commentsEndpoint || '').trim();
        const page = Math.max(1, Number.parseInt(String(button.dataset.commentsNextPage || '1'), 10) || 1);
        const items = list.querySelector('[data-comments-items]');
        const pagination = list.querySelector('[data-comments-pagination]');
        if (endpoint === '' || !items || !pagination) {
            window.location.href = button.href;
            return;
        }

        button.setAttribute('aria-busy', 'true');
        button.classList.add('is-loading');

        try {
            const url = new URL(endpoint, window.location.href);
            url.searchParams.set('page', String(page));
            url.searchParams.set('sort', String(list.dataset.commentsSort || 'relevant'));
            const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (!response.ok || payload?.ok !== true) {
                window.location.href = button.href;
                return;
            }

            const html = String(payload.data?.items_html || '');
            const paginationHtml = String(payload.data?.pagination_html || '');
            if (html !== '') {
                items.insertAdjacentHTML('beforeend', html);
                initReplies(items);
            }
            pagination.outerHTML = paginationHtml !== '' ? paginationHtml : '<div class="comments-more" data-comments-pagination></div>';
        } catch {
            window.location.href = button.href;
        }
    };

    const loadMoreReplies = async (list, button) => {
        const endpoint = String(list.dataset.commentsEndpoint || '').trim();
        const parent = Math.max(1, Number.parseInt(String(button.dataset.commentParent || '0'), 10) || 0);
        const page = Math.max(1, Number.parseInt(String(button.dataset.commentsNextPage || '1'), 10) || 1);
        const items = list.querySelector(`[data-comment-replies="${parent}"][data-comment-replies-items]`);
        const pagination = list.querySelector(`[data-comment-replies-pagination="${parent}"]`);
        if (endpoint === '' || parent <= 0 || !items || !pagination) {
            window.location.href = button.href;
            return;
        }

        button.setAttribute('aria-busy', 'true');
        button.classList.add('is-loading');

        try {
            const url = new URL(`${endpoint.replace(/\/$/, '')}/replies/${parent}`, window.location.href);
            url.searchParams.set('page', String(page));
            const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (!response.ok || payload?.ok !== true) {
                window.location.href = button.href;
                return;
            }

            const html = String(payload.data?.items_html || '');
            const paginationHtml = String(payload.data?.pagination_html || '');
            if (html !== '') {
                items.insertAdjacentHTML('beforeend', html);
                initReplies(items);
            }
            pagination.remove();
            items.insertAdjacentHTML('afterend', paginationHtml !== '' ? paginationHtml : `<div class="comments-more comment-replies-more" data-comment-replies-pagination="${parent}"></div>`);
        } catch {
            window.location.href = button.href;
        }
    };

    const initLoadMore = () => {
        document.querySelectorAll('[data-comments-list]').forEach((list) => {
            if (list.dataset.commentsLoadReady === '1') {
                return;
            }

            list.dataset.commentsLoadReady = '1';
            list.addEventListener('click', (event) => {
                const repliesButton = event.target instanceof Element ? event.target.closest('[data-comment-replies-load-more]') : null;
                if (repliesButton instanceof HTMLAnchorElement) {
                    event.preventDefault();
                    loadMoreReplies(list, repliesButton);
                    return;
                }

                const button = event.target instanceof Element ? event.target.closest('[data-comments-load-more]') : null;
                if (!(button instanceof HTMLAnchorElement)) {
                    return;
                }

                event.preventDefault();
                loadMore(list, button);
            });
        });
    };

    app.comments = {
        init: () => {
            initReplies();
            initLoadMore();
        },
    };

    app.comments.init();
})();
