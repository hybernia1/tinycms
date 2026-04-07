document.querySelectorAll('[data-custom-upload]').forEach((wrap) => {
    const fileInput = wrap.querySelector('input[type="file"]');
    const valueInput = wrap.querySelector('.custom-upload-value');

    if (!fileInput || !valueInput) {
        return;
    }

    const placeholder = wrap.getAttribute('data-placeholder') || valueInput.value || '';

    const sync = () => {
        const names = Array.from(fileInput.files || []).map((file) => file.name).filter((name) => name !== '');
        valueInput.value = names.length > 0 ? names.join(', ') : placeholder;
    };

    fileInput.addEventListener('change', sync);
    sync();
});
