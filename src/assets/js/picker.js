(() => {
    const app = window.tinycms = window.tinycms || {};
    const pickers = document.querySelectorAll('[data-picker]');
    if (!pickers.length) {
        return;
    }

    const requestJson = app.api?.http?.requestJson;
    const icon = app.icons?.icon || (() => '');
    const esc = app.support?.esc || ((value) => String(value || ''));
    const normalize = (value) => String(value || '').trim().toLowerCase();

    const resolveNodes = (picker) => ({
        chips: picker.querySelector('[data-picker-chips]'),
        input: picker.querySelector('[data-picker-input]'),
        valueField: picker.querySelector('[data-picker-value]'),
        suggestions: picker.querySelector('[data-picker-suggestions]'),
    });

    const openSuggestions = (suggestions, open) => {
        if (!suggestions) {
            return;
        }
        suggestions.classList.toggle('open', open);
        if (!open) {
            suggestions.innerHTML = '';
        }
    };

    const mapItems = (data) => {
        const list = Array.isArray(data) ? data : [];
        return list.map((item) => {
            const id = Number.parseInt(String(item?.id ?? item?.ID ?? ''), 10);
            const safeId = Number.isFinite(id) && id > 0 ? id : 0;
            const email = String(item?.email ?? '').trim();
            const rawName = String(item?.name ?? item?.label ?? '').trim();
            const name = rawName !== '' ? rawName : (email !== '' ? email : (safeId > 0 ? `#${safeId}` : ''));
            return {
                id: safeId,
                name,
                email,
            };
        }).filter((item) => item.name !== '');
    };

    const fetchSuggestions = async (picker, query) => {
        const endpoint = String(picker.getAttribute('data-search-endpoint') || '').trim();
        if (endpoint === '' || typeof requestJson !== 'function') {
            return [];
        }

        const searchStatus = String(picker.getAttribute('data-search-status') || '').trim();
        const searchParam = String(picker.getAttribute('data-search-param') || 'q').trim() || 'q';
        const limitRaw = Number.parseInt(String(picker.getAttribute('data-search-limit') || ''), 10);
        const searchLimit = Number.isFinite(limitRaw) && limitRaw > 0 ? Math.min(limitRaw, 50) : 15;
        const url = new URL(endpoint, window.location.origin);

        if (searchStatus !== '') {
            url.searchParams.set('status', searchStatus);
        }
        if (String(picker.getAttribute('data-search-public') || '').trim() === '1') {
            url.searchParams.set('public', '1');
        }
        url.searchParams.set('limit', String(searchLimit));
        if (query !== '') {
            url.searchParams.set(searchParam, query);
        }

        let payload = null;
        try {
            payload = await requestJson(url.toString(), {
                headers: { Accept: 'application/json' },
            });
        } catch (_) {
            return [];
        }

        const { response, data } = payload || {};
        if (!response || !response.ok || !data || data.success !== true) {
            return [];
        }

        return mapItems(data.data);
    };

    const initSingle = (picker, nodes) => {
        const { chips, input, valueField, suggestions } = nodes;
        if (!(input instanceof HTMLInputElement) || !(valueField instanceof HTMLInputElement)) {
            return;
        }

        const emptyLabel = String(picker.getAttribute('data-empty-label') || 'None').trim();
        const noResultsLabel = String(picker.getAttribute('data-no-results-label') || 'No results.').trim();
        const placeholder = String(picker.getAttribute('data-search-placeholder') || 'Search...').trim();
        const allowEmpty = String(picker.getAttribute('data-allow-empty') || 'true').trim().toLowerCase() !== 'false';
        let selectedId = String(valueField.value || '').trim();
        let selectedLabel = String(picker.getAttribute('data-selected-label') || '').trim();
        let timer = null;
        let requestToken = 0;

        const syncValue = (emit = false) => {
            valueField.value = selectedId;
            if (emit) {
                valueField.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };

        const renderChips = () => {
            if (!chips) {
                return;
            }
            if (selectedId !== '') {
                const label = selectedLabel !== '' ? selectedLabel : `#${selectedId}`;
                chips.innerHTML = `
                    <button type="button" class="tag-picker-chip" data-picker-clear>
                        <span>${esc(label)}</span>
                        <span aria-hidden="true">${icon('cancel')}</span>
                    </button>
                `;
                return;
            }
            chips.innerHTML = `<span class="tag-picker-chip picker-empty">${esc(emptyLabel)}</span>`;
        };

        const setSelected = (id, label) => {
            const numeric = Number.parseInt(String(id || '').trim(), 10);
            if (Number.isFinite(numeric) && numeric > 0) {
                selectedId = String(numeric);
                selectedLabel = String(label || '').trim() || `#${selectedId}`;
            } else {
                selectedId = '';
                selectedLabel = '';
            }
            syncValue(true);
            renderChips();
        };

        const renderSuggestions = (items, query) => {
            if (!suggestions) {
                return;
            }

            const emptyOption = `<button type="button" class="tag-picker-suggestion" data-picker-option data-id="" data-label="">${esc(emptyLabel)}</button>`;
            const rows = [];
            if (allowEmpty && query === '') {
                rows.push(emptyOption);
            }
            items.forEach((item) => {
                const meta = item.email !== '' ? item.email : `#${item.id}`;
                rows.push(`<button type="button" class="tag-picker-suggestion" data-picker-option data-id="${esc(item.id)}" data-label="${esc(item.name)}">${esc(item.name)} <small class="text-muted">${esc(meta)}</small></button>`);
            });

            if (items.length === 0 && query !== '') {
                rows.push(`<div class="tag-picker-suggestion text-muted">${esc(noResultsLabel)}</div>`);
            }
            if (allowEmpty && query !== '') {
                rows.push(emptyOption);
            }
            if (rows.length === 0) {
                openSuggestions(suggestions, false);
                return;
            }

            suggestions.innerHTML = rows.join('');
            openSuggestions(suggestions, true);
        };

        const applyOption = (option) => {
            if (!(option instanceof Element)) {
                return;
            }
            setSelected(option.getAttribute('data-id') || '', option.getAttribute('data-label') || '');
            openSuggestions(suggestions, false);
            input.value = '';
            input.focus();
        };

        const refreshSuggestions = async () => {
            const token = ++requestToken;
            const query = input.value.trim();
            const items = await fetchSuggestions(picker, query);
            if (token !== requestToken) {
                return;
            }
            renderSuggestions(items, query);
        };

        input.placeholder = placeholder;
        input.addEventListener('focus', refreshSuggestions);
        input.addEventListener('input', () => {
            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(refreshSuggestions, 220);
        });
        input.addEventListener('blur', () => {
            window.setTimeout(() => openSuggestions(suggestions, false), 120);
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                openSuggestions(suggestions, false);
                return;
            }
            if (event.key === 'Enter') {
                event.preventDefault();
                const options = Array.from(suggestions?.querySelectorAll('[data-picker-option]') || []);
                if (options.length === 0) {
                    return;
                }
                const prefersNonEmpty = input.value.trim() !== '';
                const first = prefersNonEmpty
                    ? (options.find((option) => String(option.getAttribute('data-id') || '').trim() !== '') || options[0])
                    : options[0];
                if (first instanceof Element) {
                    applyOption(first);
                }
            }
        });

        picker.addEventListener('mousedown', (event) => {
            const option = event.target.closest('[data-picker-option]');
            if (!(option instanceof Element)) {
                return;
            }
            event.preventDefault();
            applyOption(option);
        });
        picker.addEventListener('click', (event) => {
            const clear = event.target.closest('[data-picker-clear]');
            if (!(clear instanceof Element)) {
                return;
            }
            event.preventDefault();
            setSelected('', '');
            input.focus();
            refreshSuggestions();
        });
        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element) || picker.contains(event.target)) {
                return;
            }
            openSuggestions(suggestions, false);
        });

        syncValue();
        renderChips();
    };

    const initMulti = (picker, nodes) => {
        const { chips, input, valueField, suggestions } = nodes;
        if (!(input instanceof HTMLInputElement) || !(valueField instanceof HTMLInputElement) || !chips) {
            return;
        }

        const initialRaw = String(picker.getAttribute('data-initial') || '').trim();
        let values = [];
        let timer = null;
        let requestToken = 0;

        if (initialRaw !== '') {
            try {
                const parsed = JSON.parse(initialRaw);
                if (Array.isArray(parsed)) {
                    values = parsed.map((item) => String(item || '').trim()).filter((item) => item !== '');
                }
            } catch (_) {
                values = [];
            }
        }
        if (values.length === 0 && valueField.value.trim() !== '') {
            values = valueField.value.split(',').map((item) => item.trim()).filter((item) => item !== '');
        }

        const syncValue = () => {
            valueField.value = values.join(', ');
        };

        const renderChips = () => {
            chips.innerHTML = values.map((value) => `
                <button type="button" class="tag-picker-chip" data-picker-remove="${esc(value)}">
                    <span>${esc(value)}</span>
                    <span aria-hidden="true">${icon('cancel')}</span>
                </button>
            `).join('');
        };

        const addValue = (value) => {
            const clean = String(value || '').trim();
            if (clean === '') {
                return;
            }
            if (values.some((item) => normalize(item) === normalize(clean))) {
                return;
            }
            values.push(clean);
            renderChips();
            syncValue();
        };

        const removeValue = (value) => {
            const target = normalize(value);
            values = values.filter((item) => normalize(item) !== target);
            renderChips();
            syncValue();
        };

        const renderSuggestions = (items) => {
            if (!suggestions) {
                return;
            }
            const names = items
                .map((item) => item.name)
                .filter((name) => name !== '' && !values.some((value) => normalize(value) === normalize(name)));
            if (names.length === 0) {
                openSuggestions(suggestions, false);
                return;
            }

            suggestions.innerHTML = names.slice(0, 12).map((name) => `
                <button type="button" class="tag-picker-suggestion" data-picker-suggest="${esc(name)}">${esc(name)}</button>
            `).join('');
            openSuggestions(suggestions, true);
        };

        const refreshSuggestions = async () => {
            const token = ++requestToken;
            const query = input.value.trim();
            const items = await fetchSuggestions(picker, query);
            if (token !== requestToken) {
                return;
            }
            renderSuggestions(items);
        };

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ',') {
                event.preventDefault();
                addValue(input.value);
                input.value = '';
                openSuggestions(suggestions, false);
            }
            if (event.key === 'Escape') {
                openSuggestions(suggestions, false);
            }
        });
        input.addEventListener('blur', () => {
            if (input.value.trim() !== '') {
                addValue(input.value);
                input.value = '';
            }
            window.setTimeout(() => openSuggestions(suggestions, false), 120);
        });
        input.addEventListener('input', () => {
            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(refreshSuggestions, 220);
        });

        picker.addEventListener('mousedown', (event) => {
            const suggestion = event.target.closest('[data-picker-suggest]');
            if (!(suggestion instanceof Element)) {
                return;
            }
            event.preventDefault();
            addValue(suggestion.getAttribute('data-picker-suggest') || '');
            input.value = '';
            openSuggestions(suggestions, false);
            input.focus();
        });
        picker.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-picker-remove]');
            if (!(remove instanceof Element)) {
                return;
            }
            event.preventDefault();
            removeValue(remove.getAttribute('data-picker-remove') || '');
        });
        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element) || picker.contains(event.target)) {
                return;
            }
            openSuggestions(suggestions, false);
        });

        renderChips();
        syncValue();
    };

    pickers.forEach((picker) => {
        const mode = String(picker.getAttribute('data-picker-mode') || 'single').trim();
        const nodes = resolveNodes(picker);
        if (mode === 'multi') {
            initMulti(picker, nodes);
            return;
        }
        initSingle(picker, nodes);
    });
})();
