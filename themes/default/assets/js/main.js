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

    const menuToggle = document.querySelector('[data-menu-toggle]');
    const menuPanel = document.querySelector('[data-menu-panel]');
    const menuClose = document.querySelectorAll('[data-menu-close]');

    if (menuToggle instanceof HTMLButtonElement && menuPanel instanceof HTMLElement) {
        const setMenu = (open) => {
            document.documentElement.classList.toggle('is-menu-open', open);
            menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        menuToggle.addEventListener('click', () => {
            setMenu(!document.documentElement.classList.contains('is-menu-open'));
        });

        menuClose.forEach((button) => {
            button.addEventListener('click', () => setMenu(false));
        });

        menuPanel.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setMenu(false));
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setMenu(false);
            }
        });
    }
})();
