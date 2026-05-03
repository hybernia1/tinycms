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

const navGroups = () => Array.from(document.querySelectorAll('[data-admin-nav-group]'));

const syncNavGroup = (group) => {
    const children = group.querySelector('.admin-nav-children');
    const toggle = group.querySelector('[data-admin-nav-toggle]');
    if (!children) {
        return;
    }

    const collapsed = body.classList.contains('admin-sidebar-collapsed');
    if (collapsed) {
        const open = group.classList.contains('flyout-open');
        children.hidden = !open;
        toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
        return;
    }

    group.classList.remove('flyout-open');
    group.style.removeProperty('--admin-nav-flyout-top');
    const open = group.classList.contains('open');
    children.hidden = !open;
    toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
};

const syncNavGroups = () => {
    navGroups().forEach(syncNavGroup);
};

const closeNavFlyouts = () => {
    navGroups().forEach((group) => {
        group.classList.remove('flyout-open');
        group.style.removeProperty('--admin-nav-flyout-top');
        syncNavGroup(group);
    });
};

const setSidebarCollapsed = (collapsed) => {
    body.classList.toggle('admin-sidebar-collapsed', collapsed);
    setCookie(sidebarCookieName, collapsed ? sidebarCollapsedValue : 'expanded');
    syncNavGroups();
};

const closeMobileMenu = () => {
    body.classList.remove('admin-menu-open');
};

document.addEventListener('click', (event) => {
    const navToggle = event.target.closest('[data-admin-nav-toggle]');
    if (navToggle) {
        const group = navToggle.closest('[data-admin-nav-group]');
        if (body.classList.contains('admin-sidebar-collapsed')) {
            const open = !group?.classList.contains('flyout-open');
            closeNavFlyouts();
            group?.classList.toggle('flyout-open', open);
            group?.style.setProperty('--admin-nav-flyout-top', `${navToggle.getBoundingClientRect().top}px`);
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (group) {
                syncNavGroup(group);
            }
            return;
        }

        const children = group?.querySelector('.admin-nav-children');
        const open = !group?.classList.contains('open');
        group?.classList.toggle('open', open);
        navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (children) {
            children.hidden = !open;
        }
        return;
    }

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

    if (body.classList.contains('admin-sidebar-collapsed') && !event.target.closest('[data-admin-nav-group]')) {
        closeNavFlyouts();
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

syncNavGroups();
})();
