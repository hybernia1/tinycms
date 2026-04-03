(($) => {
    $(document).on('click', '[data-password-toggle]', function () {
        const $button = $(this);
        const $input = $button.closest('.input-with-icon').find('input[data-password-input]').first();
        const $icon = $button.find('use').first();

        if (!$input.length || !$icon.length) {
            return;
        }

        const show = $input.attr('type') === 'password';
        $input.attr('type', show ? 'text' : 'password');
        $icon.attr('href', show ? '#icon-hide' : '#icon-show');

        const label = show ? 'Skrýt heslo' : 'Zobrazit heslo';
        $button.attr('aria-label', label).attr('title', label);
    });
})(jQuery);
