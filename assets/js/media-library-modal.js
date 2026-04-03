const modal = document.querySelector('[data-media-library-modal]');
const openTrigger = document.querySelector('[data-media-library-open]');

if (modal && openTrigger) {
    const grid = modal.querySelector('[data-media-library-grid]');
    const pageLabel = modal.querySelector('[data-media-library-page]');
    const prevButton = modal.querySelector('[data-media-library-prev]');
    const nextButton = modal.querySelector('[data-media-library-next]');
    const closeButtons = modal.querySelectorAll('[data-media-library-close]');
    const searchForm = modal.querySelector('[data-media-library-search]');
    const selectForm = document.querySelector('[data-media-library-select-form]');
    const mediaIdField = document.querySelector('[data-media-library-media-id]');

    const endpoint = openTrigger.getAttribute('data-media-library-endpoint') || '';
    const baseUrl = openTrigger.getAttribute('data-media-base-url') || '';
    let page = 1;
    let totalPages = 1;
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
            const name = String(item.name || 'Bez názvu');
            const previewPath = String(item.preview_path || '');
            const button = document.createElement('button');
            button.className = 'media-library-card';
            button.type = 'button';
            button.setAttribute('data-media-library-select', String(Number(item.id || 0)));

            const imageWrap = document.createElement('div');
            imageWrap.className = 'media-library-card-image';

            if (previewPath !== '') {
                const image = document.createElement('img');
                image.src = absoluteUrl(previewPath);
                image.alt = name;
                imageWrap.appendChild(image);
            } else {
                const empty = document.createElement('div');
                empty.className = 'media-library-card-empty';
                imageWrap.appendChild(empty);
            }

            const label = document.createElement('span');
            label.textContent = name;

            button.appendChild(imageWrap);
            button.appendChild(label);
            grid.appendChild(button);
        });
    };

    const updatePager = () => {
        if (pageLabel) {
            pageLabel.textContent = `${page} / ${totalPages}`;
        }

        if (prevButton) {
            prevButton.disabled = page <= 1;
        }

        if (nextButton) {
            nextButton.disabled = page >= totalPages;
        }
    };

    const load = async () => {
        if (!endpoint) {
            return;
        }

        if (grid) {
            grid.innerHTML = '<p class="text-muted m-0">Načítám...</p>';
        }

        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('page', String(page));
        url.searchParams.set('per_page', '10');
        if (query !== '') {
            url.searchParams.set('q', query);
        }

        const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            throw new Error('load_failed');
        }

        const data = await response.json();
        totalPages = Math.max(1, Number(data.total_pages || 1));
        page = Math.min(Math.max(1, Number(data.page || 1)), totalPages);
        renderItems(data.items || []);
        updatePager();
    };

    const open = () => {
        modal.classList.add('open');
        page = 1;
        load().catch(() => {
            if (grid) {
                grid.innerHTML = '<p class="text-danger m-0">Nepodařilo se načíst knihovnu.</p>';
            }
        });
    };

    const close = () => {
        modal.classList.remove('open');
    };

    openTrigger.addEventListener('click', open);
    closeButtons.forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            close();
        }
    });

    if (prevButton) {
        prevButton.addEventListener('click', () => {
            if (page <= 1) {
                return;
            }
            page -= 1;
            load().catch(() => null);
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', () => {
            if (page >= totalPages) {
                return;
            }
            page += 1;
            load().catch(() => null);
        });
    }

    if (searchForm) {
        searchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const field = searchForm.querySelector('input[name="q"]');
            query = field ? field.value.trim() : '';
            page = 1;
            load().catch(() => null);
        });
    }

    if (grid && selectForm && mediaIdField) {
        grid.addEventListener('click', (event) => {
            const target = event.target.closest('[data-media-library-select]');
            if (!target) {
                return;
            }

            const mediaId = Number(target.getAttribute('data-media-library-select') || '0');
            if (mediaId <= 0) {
                return;
            }

            mediaIdField.value = String(mediaId);
            selectForm.submit();
        });
    }
}
