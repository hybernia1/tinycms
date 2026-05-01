(() => {
    const replyButtons = Array.from(document.querySelectorAll('[data-comment-reply]'));
    if (replyButtons.length === 0) {
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
})();
