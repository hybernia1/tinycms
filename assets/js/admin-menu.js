(($) => {
    const $body = $(document.body);

    $(document).on('click', '[data-admin-menu-toggle]', () => {
        $body.toggleClass('admin-menu-open');
    });

    $(document).on('click', '[data-admin-menu-close]', () => {
        $body.removeClass('admin-menu-open');
    });

    $(document).on('click', '.admin-nav-link', () => {
        if (window.matchMedia('(max-width: 900px)').matches) {
            $body.removeClass('admin-menu-open');
        }
    });
})(jQuery);
