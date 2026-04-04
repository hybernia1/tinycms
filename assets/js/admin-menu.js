const body = document.body;
const root = document.documentElement;

const syncAppHeight = () => {
    const height = window.visualViewport ? window.visualViewport.height : window.innerHeight;
    root.style.setProperty('--app-height', `${Math.round(height)}px`);
};

syncAppHeight();
window.addEventListener('resize', syncAppHeight);
window.addEventListener('orientationchange', syncAppHeight);
if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', syncAppHeight);
    window.visualViewport.addEventListener('scroll', syncAppHeight);
}

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
