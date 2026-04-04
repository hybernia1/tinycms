document.querySelectorAll('[data-file-input]').forEach((root) => {
    const input = root.querySelector('input[type="file"]');
    const label = root.querySelector('[data-file-input-label]');
    if (!input || !label) {
        return;
    }

    const fallback = label.textContent || 'Nevybrán žádný soubor';

    const render = () => {
        const files = input.files;
        if (!files || files.length === 0) {
            label.textContent = fallback;
            return;
        }
        if (files.length === 1) {
            label.textContent = files[0].name;
            return;
        }
        label.textContent = `Vybráno souborů: ${files.length}`;
    };

    input.addEventListener('change', render);
    render();
});
