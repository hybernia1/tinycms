(() => {
    const app = window.tinycms || null;
    const t = (key, fallback = '') => {
        if (app?.i18n?.t) {
            return app.i18n.t(key, fallback);
        }
        return fallback || key;
    };
    const confirmModal = app?.ui?.modal?.confirm || null;

    const replyButtons = Array.from(document.querySelectorAll('[data-comment-reply]'));
    const editButtons = Array.from(document.querySelectorAll('[data-comment-edit]'));
    const deleteForms = Array.from(document.querySelectorAll('.comment-admin-delete'));
    if (replyButtons.length === 0 && editButtons.length === 0 && deleteForms.length === 0) {
        return;
    }

    const closeForm = (button, form) => {
        if (!form) {
            return;
        }
        form.hidden = true;
        button.setAttribute('aria-expanded', 'false');
    };

    replyButtons.forEach((button) => {
        const target = String(button.getAttribute('data-comment-reply-target') || '').trim();
        const form = target !== '' ? document.getElementById(target) : null;
        if (!form) {
            return;
        }

        form.hidden = true;
        button.setAttribute('aria-expanded', 'false');

        button.addEventListener('click', () => {
            const willOpen = form.hidden;
            replyButtons.forEach((otherButton) => {
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

    editButtons.forEach((button) => {
        const target = String(button.getAttribute('data-comment-edit-target') || '').trim();
        const form = target !== '' ? document.getElementById(target) : null;
        if (!form) {
            return;
        }

        form.hidden = true;
        button.setAttribute('aria-expanded', 'false');

        button.addEventListener('click', () => {
            const willOpen = form.hidden;
            form.hidden = !willOpen;
            button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (willOpen) {
                form.querySelector('textarea')?.focus();
            }
        });
    });

    deleteForms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            if (typeof confirmModal !== 'function') {
                return;
            }
            event.preventDefault();
            const confirmed = await confirmModal({
                message: t('comments.delete_confirm', 'Do you really want to delete this comment?'),
            });
            if (confirmed) {
                form.submit();
            }
        });
    });
})();
