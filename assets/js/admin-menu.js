const body = document.body;
const mobileMedia = window.matchMedia('(max-width: 900px)');

const closeMobileMenu = () => {
    body.classList.remove('admin-menu-open');
};

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-admin-sidebar-toggle]')) {
        body.classList.toggle('admin-sidebar-collapsed');
        return;
    }

    if (event.target.closest('[data-admin-menu-toggle]')) {
        body.classList.toggle('admin-menu-open');
        return;
    }

    if (event.target.closest('[data-admin-menu-close]')) {
        closeMobileMenu();
        return;
    }

    if (event.target.closest('.admin-nav-link') && mobileMedia.matches) {
        closeMobileMenu();
    }
});

mobileMedia.addEventListener('change', () => {
    if (!mobileMedia.matches) {
        closeMobileMenu();
    }
});
