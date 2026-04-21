const settingsContentPickers = document.querySelectorAll('[data-settings-content-picker]');

if (settingsContentPickers.length) {
    const requestJson = window.tinycms?.api?.http?.requestJson;

    settingsContentPickers.forEach((picker) => {
        const endpoint = picker.getAttribute('data-search-endpoint') || '';
        const searchInput = picker.querySelector('[data-settings-content-search]');
        const idInput = picker.querySelector('[data-settings-content-id]');
        const suggestions = picker.querySelector('[data-settings-content-suggestions]');
        let timer = null;

        const esc = (value) => String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const clearSuggestions = () => {
            if (!suggestions) {
                return;
            }
            suggestions.classList.remove('open');
            suggestions.innerHTML = '';
        };

        const renderSuggestions = (items) => {
            if (!suggestions) {
                return;
            }
            if (!items.length) {
                clearSuggestions();
                return;
            }
            suggestions.classList.add('open');
            suggestions.innerHTML = items.map((item) => {
                const id = Number(item.id || 0);
                const name = String(item.name || '').trim();
                if (!Number.isFinite(id) || id <= 0 || name === '') {
                    return '';
                }
                return `<button type="button" class="tag-picker-suggestion" data-settings-content-select="${id}" data-settings-content-name="${esc(name)}">${esc(name)}</button>`;
            }).join('');
        };

        const fetchSuggestions = async (query) => {
            if (!searchInput || !endpoint || typeof requestJson !== 'function') {
                clearSuggestions();
                return;
            }
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('page', '1');
            url.searchParams.set('status', 'published');
            if (query !== '') {
                url.searchParams.set('q', query);
            }

            const { response, data } = await requestJson(url.toString(), {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                clearSuggestions();
                return;
            }

            const items = Array.isArray(data.data) ? data.data : [];
            renderSuggestions(items.slice(0, 8));
        };

        if (searchInput && idInput) {
            searchInput.addEventListener('input', () => {
                idInput.value = '';
                if (timer) {
                    clearTimeout(timer);
                }
                timer = window.setTimeout(() => {
                    fetchSuggestions(searchInput.value.trim());
                }, 250);
            });

            searchInput.addEventListener('focus', () => {
                fetchSuggestions(searchInput.value.trim());
            });

            searchInput.addEventListener('blur', () => {
                setTimeout(() => clearSuggestions(), 150);
            });
        }

        picker.addEventListener('click', (event) => {
            const option = event.target.closest('[data-settings-content-select]');
            if (!option || !searchInput || !idInput) {
                return;
            }
            event.preventDefault();
            idInput.value = option.getAttribute('data-settings-content-select') || '';
            searchInput.value = option.getAttribute('data-settings-content-name') || '';
            clearSuggestions();
            searchInput.focus();
        });
    });
}
