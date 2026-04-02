document.addEventListener('click', function (event) {
    const toggle = event.target.closest('[data-admin-menu-toggle]');

    if (toggle) {
        document.body.classList.toggle('admin-menu-open');
        return;
    }

    const close = event.target.closest('[data-admin-menu-close]');

    if (close) {
        document.body.classList.remove('admin-menu-open');
        return;
    }

    const navLink = event.target.closest('.admin-nav-link');

    if (navLink && window.matchMedia('(max-width: 900px)').matches) {
        document.body.classList.remove('admin-menu-open');
    }
});
