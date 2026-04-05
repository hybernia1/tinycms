const settingsMediaModal = document.querySelector('[data-settings-media-modal]');

if (settingsMediaModal) {
    const searchInput = settingsMediaModal.querySelector('[data-settings-media-search]');
    const grid = settingsMediaModal.querySelector('[data-settings-media-grid]');
    const uploadForm = settingsMediaModal.querySelector('[data-settings-media-upload]');
    const endpoint = settingsMediaModal.getAttribute('data-endpoint') || '';
    const uploadEndpoint = settingsMediaModal.getAttribute('data-upload-endpoint') || '';
    const baseUrl = settingsMediaModal.getAttribute('data-base-url') || '';
    let activeTarget = '';
    let query = '';

    const absoluteUrl = (path) => {
        if (!path) {
            return '';
        }
        if (path.startsWith('http://') || path.startsWith('https://')) {
            return path;
        }
        const root = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl;
        const normalized = path.startsWith('/') ? path : `/${path}`;
        return `${root}${normalized}`;
    };

    const closeModal = () => settingsMediaModal.classList.remove('is-open');

    const renderItems = (items) => {
        if (!grid) {
            return;
        }

        if (!Array.isArray(items) || items.length === 0) {
            grid.innerHTML = '<p class="text-muted m-0">Žádné výsledky.</p>';
            return;
        }

        grid.innerHTML = '';
        items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'media-library-card';

            const imageWrap = document.createElement('div');
            imageWrap.className = 'media-library-card-image';
            const previewPath = String(item.preview_path || item.webp_path || item.path || '');
            if (previewPath !== '') {
                const image = document.createElement('img');
                image.src = absoluteUrl(previewPath);
                image.alt = String(item.name || '');
                imageWrap.appendChild(image);
            }

            const label = document.createElement('span');
            label.textContent = String(item.name || '');

            button.appendChild(imageWrap);
            button.appendChild(label);

            button.addEventListener('click', () => {
                const input = document.querySelector(`[data-settings-media-input="${activeTarget}"]`);
                const trigger = document.querySelector(`[data-settings-media-open][data-settings-media-target="${activeTarget}"]`);
                if (!input || !trigger) {
                    return;
                }

                const selectedPath = String(item.path || item.webp_path || '');
                input.value = selectedPath;
                const preview = absoluteUrl(previewPath || selectedPath);
                if (preview !== '') {
                    trigger.classList.remove('empty');
                    trigger.innerHTML = `<div class="content-thumbnail-preview"><img src="${preview}" alt=""></div>`;
                }
                closeModal();
            });

            grid.appendChild(button);
        });
    };

    const loadItems = async () => {
        if (endpoint === '') {
            return;
        }
        const url = new URL(endpoint, window.location.origin);
        if (query !== '') {
            url.searchParams.set('q', query);
        }

        const response = await fetch(url.toString(), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const payload = await response.json();
        renderItems(payload.data || []);
    };

    document.querySelectorAll('[data-settings-media-open]').forEach((button) => {
        button.addEventListener('click', () => {
            activeTarget = button.getAttribute('data-settings-media-target') || '';
            settingsMediaModal.classList.add('is-open');
            void loadItems();
        });
    });

    document.querySelectorAll('[data-settings-media-clear]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.getAttribute('data-settings-media-target') || '';
            const input = document.querySelector(`[data-settings-media-input="${target}"]`);
            const trigger = document.querySelector(`[data-settings-media-open][data-settings-media-target="${target}"]`);
            if (!input || !trigger) {
                return;
            }
            input.value = '';
            trigger.classList.add('empty');
            trigger.innerHTML = '<span>Choose image</span>';
        });
    });

    settingsMediaModal.querySelectorAll('[data-settings-media-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    settingsMediaModal.addEventListener('click', (event) => {
        if (event.target === settingsMediaModal) {
            closeModal();
        }
    });

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            query = searchInput.value.trim();
            void loadItems();
        });
    }

    if (uploadForm) {
        uploadForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (uploadEndpoint === '') {
                return;
            }

            const formData = new FormData(uploadForm);
            const response = await fetch(uploadEndpoint, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });

            const payload = await response.json();
            if (payload.ok === true) {
                uploadForm.reset();
                void loadItems();
            }
        });
    }
}
