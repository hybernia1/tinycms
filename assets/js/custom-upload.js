document.querySelectorAll('.custom-upload-field').forEach((field) => {
    const fileInput = field.querySelector('input[type="file"]');
    const label = field.querySelector('[data-custom-upload-label]');

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

    fileInput.addEventListener('change', updateLabel);
    updateLabel();
});
