(() => {
    const inputs = Array.from(document.querySelectorAll('.admin-content input[type="datetime-local"]'));
    if (!inputs.length) {
        return;
    }

    const sampleIconUse = document.querySelector('svg.icon use');
    const iconBase = sampleIconUse ? (sampleIconUse.getAttribute('href') || '').split('#')[0] : '';
    const iconHref = (name) => `${iconBase}#icon-${name}`;

    inputs.forEach((input) => {
        if (input.closest('.custom-datetime')) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'custom-datetime';
        input.insertAdjacentElement('beforebegin', wrapper);
        wrapper.appendChild(input);
        input.classList.add('field-control-with-end-icon');

        const icon = document.createElement('span');
        icon.className = 'field-overlay field-overlay-end field-icon field-icon-soft';
        icon.innerHTML = `<svg class="icon" aria-hidden="true" focusable="false"><use href="${iconHref('calendar')}"></use></svg>`;
        wrapper.appendChild(icon);
    });
})();
