(() => {
const body = document.body;
const mobileMedia = window.matchMedia('(max-width: 900px)');
const sidebarCookieName = 'tinycms_admin_sidebar';
const sidebarCollapsedValue = 'collapsed';

const getCookie = (name) => {
    const prefix = `${name}=`;
    return document.cookie
        .split(';')
        .map((part) => part.trim())
        .find((part) => part.startsWith(prefix))
        ?.slice(prefix.length) ?? '';
};

const setCookie = (name, value) => {
    document.cookie = `${name}=${value}; path=/; max-age=31536000; samesite=lax`;
};

const setSidebarCollapsed = (collapsed) => {
    body.classList.toggle('admin-sidebar-collapsed', collapsed);
    setCookie(sidebarCookieName, collapsed ? sidebarCollapsedValue : 'expanded');
};

const closeMobileMenu = () => {
    body.classList.remove('admin-menu-open');
};

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-admin-sidebar-toggle]')) {
        setSidebarCollapsed(!body.classList.contains('admin-sidebar-collapsed'));
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
})();
