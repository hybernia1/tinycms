(() => {
    const toggle = document.querySelector('[data-menu-toggle]');
    const panel = document.querySelector('[data-menu-panel]');
    const closeButtons = document.querySelectorAll('[data-menu-close]');

    if (!toggle || !panel) {
        return;
    }

    const setOpen = (open) => {
        document.body.classList.toggle('menu-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => setOpen(!document.body.classList.contains('menu-open')));
    closeButtons.forEach((button) => button.addEventListener('click', () => setOpen(false)));
})();
