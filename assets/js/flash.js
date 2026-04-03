(($) => {
    $(document).on('click', '[data-flash-close]', function () {
        $(this).closest('.flash').remove();
    });
})(jQuery);
