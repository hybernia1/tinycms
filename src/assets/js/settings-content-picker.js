const entityPickers = document.querySelectorAll('[data-entity-picker]');

if (entityPickers.length) {
    const requestJson = window.tinycms?.api?.http?.requestJson;

    entityPickers.forEach((picker) => {
        const endpoint = picker.getAttribute('data-search-endpoint') || '';
        const searchStatus = (picker.getAttribute('data-search-status') || '').trim();
        const labelTemplate = (picker.getAttribute('data-label-template') || '{name}').trim();
        const searchInput = picker.querySelector('[data-entity-picker-search]');
        const valueInput = picker.querySelector('[data-entity-picker-value]');
        const suggestions = picker.querySelector('[data-entity-picker-suggestions]');
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

        const resolveLabel = (item) => {
            const fallback = String(item.name || '').trim();
            const resolved = labelTemplate
                .replace('{name}', String(item.name || '').trim())
                .replace('{email}', String(item.email || '').trim())
                .trim();
            return resolved !== '' ? resolved : fallback;
        };

        const renderSuggestions = (items) => {
            if (!suggestions) {
                return;
            }
            const rows = items
                .map((item) => {
                    const id = Number(item.id || 0);
                    if (!Number.isFinite(id) || id <= 0) {
                        return '';
                    }
                    const label = resolveLabel(item);
                    if (label === '') {
                        return '';
                    }
                    return `<button type="button" class="tag-picker-suggestion" data-entity-picker-select="${id}" data-entity-picker-label="${esc(label)}">${esc(label)}</button>`;
                })
                .filter((item) => item !== '');
            if (!rows.length) {
                clearSuggestions();
                return;
            }
            suggestions.classList.add('open');
            suggestions.innerHTML = rows.join('');
        };

        const fetchSuggestions = async (query) => {
            if (!searchInput || !endpoint || typeof requestJson !== 'function') {
                clearSuggestions();
                return;
            }
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('page', '1');
            if (searchStatus !== '') {
                url.searchParams.set('status', searchStatus);
            }
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

        if (searchInput && valueInput) {
            searchInput.addEventListener('input', () => {
                valueInput.value = '';
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
            const option = event.target.closest('[data-entity-picker-select]');
            if (!option || !searchInput || !valueInput) {
                return;
            }
            event.preventDefault();
            valueInput.value = option.getAttribute('data-entity-picker-select') || '';
            searchInput.value = option.getAttribute('data-entity-picker-label') || '';
            clearSuggestions();
            searchInput.focus();
        });
    });
}
