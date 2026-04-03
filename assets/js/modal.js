(($) => {
    const $doc = $(document);

    const openModal = ($trigger) => {
        const $modal = $('[data-modal]').first();
        if (!$modal.length) {
            return;
        }

        const type = $trigger.data('type') || 'záznam';
        const formId = $trigger.data('form-id') || '';

        $modal.find('[data-modal-text]').text(`Skutečně smazat tento ${type}?`);
        $modal.find('[data-modal-confirm]').attr('data-form-id', formId);
        $modal.addClass('open');
    };

    $doc.on('click', '[data-modal-open]', function (event) {
        event.preventDefault();
        openModal($(this));
    });

    $doc.on('click', '[data-modal-close]', () => {
        $('[data-modal]').removeClass('open');
    });

    $doc.on('click', '[data-modal-confirm]', function () {
        const formId = String($(this).attr('data-form-id') || '');
        const form = formId ? document.getElementById(formId) : null;

        if (form) {
            form.submit();
        }

        $('[data-modal]').removeClass('open');
    });
})(jQuery);
