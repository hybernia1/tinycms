const entityPickers = document.querySelectorAll('[data-entity-picker]');

if (entityPickers.length) {
    const requestJson = window.tinycms?.api?.http?.requestJson;
    const esc = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    entityPickers.forEach((picker) => {
        const endpoint = picker.getAttribute('data-search-endpoint') || '';
        const hidden = picker.querySelector('[data-entity-picker-value]');
        const input = picker.querySelector('[data-entity-picker-input]');
        const dropdown = picker.querySelector('[data-entity-picker-suggestions]');
        const clear = picker.querySelector('[data-entity-picker-clear]');
        const initialId = parseInt(picker.getAttribute('data-initial-id') || '0', 10);
        const initialLabel = String(picker.getAttribute('data-initial-label') || '').trim();
        const emptyLabel = String(picker.getAttribute('data-empty-label') || '').trim();
        let selectedId = Number.isInteger(initialId) && initialId > 0 ? initialId : 0;
        let selectedLabel = initialLabel;
        let page = 1;
        let totalPages = 1;
        let busy = false;
        let timer = null;
        let lastQuery = '';
        let open = false;

        const sync = () => {
            if (!hidden || !input) {
                return;
            }
            hidden.value = selectedId > 0 ? String(selectedId) : '';
            input.value = selectedId > 0 ? selectedLabel : '';
            input.placeholder = selectedId > 0 ? '' : emptyLabel;
            if (clear) {
                clear.hidden = selectedId <= 0;
            }
            picker.classList.toggle('is-selected', selectedId > 0);
        };

        const closeDropdown = () => {
            if (!dropdown) {
                return;
            }
            dropdown.innerHTML = '';
            dropdown.classList.remove('open');
            open = false;
        };

        const render = (items, append = false) => {
            if (!dropdown) {
                return;
            }
            const rows = items.map((item) => {
                const id = parseInt(item.id || 0, 10);
                const label = String(item.label || '').trim();
                if (id <= 0 || label === '') {
                    return '';
                }
                return `<button type="button" class="entity-picker-suggestion" data-entity-picker-option="${id}" data-entity-picker-label="${esc(label)}">${esc(label)}</button>`;
            }).filter((item) => item !== '');

            const more = page < totalPages ? '<button type="button" class="entity-picker-more" data-entity-picker-more>Load more</button>' : '';
            if (!append) {
                dropdown.innerHTML = rows.join('') + more;
            } else {
                const moreButton = dropdown.querySelector('[data-entity-picker-more]');
                if (moreButton) {
                    moreButton.remove();
                }
                dropdown.insertAdjacentHTML('beforeend', rows.join('') + more);
            }
            dropdown.classList.toggle('open', rows.length > 0 || page < totalPages);
            open = dropdown.classList.contains('open');
        };

        const fetchList = async (query, nextPage, append) => {
            if (!endpoint || typeof requestJson !== 'function' || busy) {
                return;
            }
            busy = true;
            const url = new URL(endpoint, window.location.origin);
            if (query !== '') {
                url.searchParams.set('q', query);
            }
            url.searchParams.set('page', String(nextPage));
            url.searchParams.set('per_page', '15');

            const { response, data } = await requestJson(url.toString(), {
                headers: { Accept: 'application/json' },
            });
            busy = false;
            if (!response.ok) {
                return;
            }

            page = parseInt(data.meta?.page || nextPage, 10) || 1;
            totalPages = parseInt(data.meta?.total_pages || 1, 10) || 1;
            render(Array.isArray(data.data) ? data.data : [], append);
        };

        sync();

        if (input) {
            input.addEventListener('focus', () => {
                input.value = '';
                lastQuery = '';
                page = 1;
                totalPages = 1;
                fetchList('', 1, false);
            });

            input.addEventListener('input', () => {
                selectedId = 0;
                selectedLabel = '';
                sync();
                if (timer) {
                    clearTimeout(timer);
                }
                timer = window.setTimeout(() => {
                    const query = input.value.trim();
                    lastQuery = query;
                    page = 1;
                    totalPages = 1;
                    fetchList(query, 1, false);
                }, 250);
            });
        }

        picker.addEventListener('click', (event) => {
            const option = event.target.closest('[data-entity-picker-option]');
            if (option) {
                event.preventDefault();
                selectedId = parseInt(option.getAttribute('data-entity-picker-option') || '0', 10);
                selectedLabel = String(option.getAttribute('data-entity-picker-label') || '');
                sync();
                closeDropdown();
                return;
            }

            const more = event.target.closest('[data-entity-picker-more]');
            if (more) {
                event.preventDefault();
                if (page < totalPages) {
                    fetchList(lastQuery, page + 1, true);
                }
            }
        });

        if (clear) {
            clear.addEventListener('click', (event) => {
                event.preventDefault();
                selectedId = 0;
                selectedLabel = '';
                sync();
                if (input) {
                    input.focus();
                }
                closeDropdown();
            });
        }

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element) || event.target.closest('[data-entity-picker]') !== picker || !open) {
                closeDropdown();
            }
        });
    });
}
