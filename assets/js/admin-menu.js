const body = document.body;

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-admin-menu-toggle]')) {
        body.classList.toggle('admin-menu-open');
        return;
    }

    if (event.target.closest('[data-admin-menu-close]')) {
        body.classList.remove('admin-menu-open');
        return;
    }

    if (event.target.closest('.admin-nav-link') && window.matchMedia('(max-width: 900px)').matches) {
        body.classList.remove('admin-menu-open');
    }
});
