const tagPickers = document.querySelectorAll('[data-tag-picker]');

if (tagPickers.length) {
    const requestJson = window.tinycms?.api?.http?.requestJson;
    tagPickers.forEach((picker) => {
        const chips = picker.querySelector('[data-tag-picker-chips]');
        const input = picker.querySelector('[data-tag-picker-input]');
        const valueField = picker.querySelector('[data-tag-picker-value]');
        const suggestions = picker.querySelector('[data-tag-picker-suggestions]');
        const endpoint = picker.getAttribute('data-search-endpoint') || '';
        const initialRaw = picker.getAttribute('data-initial') || '[]';

        let tags = [];
        let timer = null;
        const iconUse = document.querySelector('svg use[href*="#icon-"]');
        const iconSprite = iconUse ? String(iconUse.getAttribute('href') || '').split('#')[0] : '';

        try {
            const initial = JSON.parse(initialRaw);
            if (Array.isArray(initial)) {
                tags = initial.map((item) => String(item || '').trim()).filter((item) => item !== '');
            }
        } catch (_) {
            tags = [];
        }

        const esc = (value) => String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const normalize = (value) => value.trim().toLowerCase();

        const sync = () => {
            if (!chips || !valueField) {
                return;
            }
            chips.innerHTML = tags.map((tag) => `
                <button type="button" class="tag-picker-chip" data-tag-remove="${esc(tag)}">
                    <span>${esc(tag)}</span>
                    <span aria-hidden="true">${iconSprite !== "" ? `<svg class="icon" aria-hidden="true" focusable="false"><use href="${esc(iconSprite)}#icon-cancel"></use></svg>` : ""}</span>
                </button>
            `).join('');
            valueField.value = tags.join(', ');
        };

        const addTag = (value) => {
            const clean = value.trim();
            if (clean === '') {
                return;
            }
            if (tags.some((tag) => normalize(tag) === normalize(clean))) {
                return;
            }
            tags.push(clean);
            sync();
        };

        const removeTag = (value) => {
            tags = tags.filter((tag) => normalize(tag) !== normalize(value));
            sync();
        };

        const renderSuggestions = (items) => {
            if (!suggestions) {
                return;
            }
            if (!items.length) {
                suggestions.classList.remove('open');
                suggestions.innerHTML = '';
                return;
            }
            suggestions.classList.add('open');
            suggestions.innerHTML = items.map((item) => `
                <button type="button" class="tag-picker-suggestion" data-tag-suggest="${esc(item)}">${esc(item)}</button>
            `).join('');
        };

        const fetchSuggestions = async (query) => {
            if (!endpoint || typeof requestJson !== 'function') {
                renderSuggestions([]);
                return;
            }

            const url = new URL(endpoint, window.location.origin);
            if (query !== '') {
                url.searchParams.set('q', query);
            }

            const { response, data } = await requestJson(url.toString(), {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                return;
            }
            const items = Array.isArray(data.data) ? data.data : [];
            const names = items
                .map((item) => String(item.name || '').trim())
                .filter((name) => name !== '' && !tags.some((tag) => normalize(tag) === normalize(name)));
            renderSuggestions(names.slice(0, 8));
        };

        sync();

        if (input) {
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ',') {
                    event.preventDefault();
                    addTag(input.value);
                    input.value = '';
                    renderSuggestions([]);
                }
            });

            input.addEventListener('blur', () => {
                if (input.value.trim() !== '') {
                    addTag(input.value);
                    input.value = '';
                }
                setTimeout(() => renderSuggestions([]), 150);
            });

            input.addEventListener('input', () => {
                if (timer) {
                    clearTimeout(timer);
                }
                timer = window.setTimeout(() => {
                    fetchSuggestions(input.value.trim());
                }, 250);
            });
        }

        picker.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-tag-remove]');
            if (remove) {
                event.preventDefault();
                removeTag(remove.getAttribute('data-tag-remove') || '');
                return;
            }

            const suggestion = event.target.closest('[data-tag-suggest]');
            if (suggestion && input) {
                event.preventDefault();
                addTag(suggestion.getAttribute('data-tag-suggest') || '');
                input.value = '';
                renderSuggestions([]);
                input.focus();
            }
        });
    });
}
