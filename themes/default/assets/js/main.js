(() => {
    document.documentElement.classList.add('front-ready');

    const forms = document.querySelectorAll('.search-form-expand');
    for (const form of forms) {
        form.addEventListener('submit', (event) => {
            const input = form.querySelector('input[type="search"]');
            if (!(input instanceof HTMLInputElement)) {
                return;
            }
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
    }
})();
