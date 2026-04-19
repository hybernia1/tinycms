(() => {
    document.documentElement.classList.add('front-ready');

    const forms = document.querySelectorAll('.search-form-expand');
    for (const form of forms) {
        const input = form.querySelector('input[type="search"]');
        if (!(input instanceof HTMLInputElement)) {
            continue;
        }

        form.addEventListener('submit', (event) => {
            if (form.classList.contains('is-open')) {
                return;
            }
            if (input.value.trim() !== '') {
                return;
            }

            event.preventDefault();
            form.classList.add('is-open');
            input.focus();
        });

        form.addEventListener('focusout', () => {
            requestAnimationFrame(() => {
                if (form.contains(document.activeElement)) {
                    return;
                }
                if (input.value.trim() !== '') {
                    return;
                }
                form.classList.remove('is-open');
            });
        });
    }
})();
