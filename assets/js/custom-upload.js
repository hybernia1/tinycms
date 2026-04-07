document.querySelectorAll('.custom-upload-field').forEach((field) => {
    const fileInput = field.querySelector('input[type="file"]');
    const label = field.querySelector('[data-custom-upload-label]');
    const autoSubmit = field.hasAttribute('data-custom-upload-auto-submit');

    if (!fileInput || !label) {
        return;
    }

    const defaultLabel = label.getAttribute('data-default-label') || label.textContent || '';

    const updateLabel = () => {
        const files = Array.from(fileInput.files || []);
        if (files.length === 0) {
            label.textContent = defaultLabel;
            label.removeAttribute('title');
            return;
        }

        const text = files.length === 1 ? files[0].name : `${files[0].name} +${files.length - 1}`;
        label.textContent = text;
        label.setAttribute('title', text);
    };

    const submitForm = () => {
        if (!autoSubmit || !fileInput.files || fileInput.files.length === 0) {
            return;
        }

        const form = field.closest('form');
        if (!form) {
            return;
        }

        const nameInput = form.querySelector('input[name="name"]');
        if (nameInput && nameInput.value.trim() === '') {
            const fileName = String(fileInput.files[0]?.name || '').replace(/\.[^/.]+$/, '');
            nameInput.value = fileName;
        }

        field.classList.add('is-loading');
        form.requestSubmit();
    };

    fileInput.addEventListener('change', () => {
        updateLabel();
        submitForm();
    });
    updateLabel();
});
