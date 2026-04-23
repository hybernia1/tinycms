(() => {
    const root = document.body;
    if (!root) {
        return;
    }

    let opened = null;
    const arrowIconHref = window.tinycms?.icons?.href?.('chevron-down') || '';
    const searchMinOptions = 8;
    const typeAheadResetMs = 650;
    const normalize = (value) => String(value || '')
        .toLocaleLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
    const isTypeKey = (event) => event.key.length === 1 && event.key !== ' ' && !event.ctrlKey && !event.metaKey && !event.altKey;
    const filterItems = (list, query) => {
        const needle = normalize(query);
        let firstMatch = null;
        list.querySelectorAll('.custom-select-option').forEach((item) => {
            const haystack = item.dataset.searchLabel || '';
            const visible = needle === '' || haystack.includes(needle);
            item.hidden = !visible;
            if (!firstMatch && visible && !item.classList.contains('disabled')) {
                firstMatch = item;
            }
        });
        return firstMatch;
    };

    const closeOpened = () => {
        if (!opened) {
            return;
        }
        opened.classList.remove('open');
        const button = opened.querySelector('[data-custom-select-button]');
        if (button) {
            button.setAttribute('aria-expanded', 'false');
        }
        const list = opened.querySelector('.custom-select-list');
        if (list) {
            const searchInput = opened.querySelector('.custom-select-search');
            if (searchInput instanceof HTMLInputElement && searchInput.value !== '') {
                searchInput.value = '';
                filterItems(list, '');
            }
            setActiveItem(list, null);
        }
        opened = null;
    };

    const sync = (select, buttonLabel) => {
        const selectedOption = select.options[select.selectedIndex];
        buttonLabel.textContent = selectedOption ? selectedOption.textContent || '' : '';
    };

    const syncDisabled = (select, button, wrapper) => {
        button.disabled = select.disabled;
        wrapper.classList.toggle('disabled', select.disabled);
        if (select.disabled && opened === wrapper) {
            closeOpened();
        }
    };

    const getEnabledItems = (list) => Array.from(list.querySelectorAll('.custom-select-option:not(.disabled)')).filter((item) => !item.hidden);
    const getCurrentItem = (list) => list.querySelector('.custom-select-option.selected:not(.disabled):not([hidden])') || getEnabledItems(list)[0] || null;
    const setActiveItem = (list, item) => {
        list.querySelectorAll('.custom-select-option').forEach((el) => {
            el.classList.toggle('active', el === item);
        });
        if (item) {
            item.scrollIntoView({ block: 'nearest' });
        }
    };
    const getActiveItem = (list) => list.querySelector('.custom-select-option.active:not(.disabled):not([hidden])');
    const findByPrefix = (list, prefix) => {
        const needle = normalize(prefix);
        if (!needle) {
            return null;
        }
        const enabledItems = getEnabledItems(list);
        if (!enabledItems.length) {
            return null;
        }
        const current = getActiveItem(list) || getCurrentItem(list);
        const startIndex = Math.max(-1, enabledItems.indexOf(current));
        for (let step = 1; step <= enabledItems.length; step += 1) {
            const item = enabledItems[(startIndex + step) % enabledItems.length];
            const label = item.dataset.searchLabel || '';
            if (label.startsWith(needle)) {
                return item;
            }
        }
        return null;
    };
    const moveActiveItem = (list, direction) => {
        const enabledItems = getEnabledItems(list);
        if (!enabledItems.length) {
            return;
        }
        const activeItem = getActiveItem(list) || getCurrentItem(list);
        const activeIndex = Math.max(0, enabledItems.indexOf(activeItem));
        const nextIndex = direction === 'next'
            ? Math.min(enabledItems.length - 1, activeIndex + 1)
            : Math.max(0, activeIndex - 1);
        setActiveItem(list, enabledItems[nextIndex]);
    };
    const selectItem = (select, list, item, buttonLabel) => {
        if (!item || item.classList.contains('disabled')) {
            return;
        }
        select.value = item.dataset.value || '';
        select.dispatchEvent(new Event('change', { bubbles: true }));
        sync(select, buttonLabel);
        closeOpened();
    };
    const searchPlaceholder = () => {
        const t = window.tinycms?.i18n?.t;
        if (typeof t === 'function') {
            const translated = t('common.search_placeholder');
            if (translated) {
                return translated;
            }
        }
        return 'Search...';
    };

    const enhance = (scope = document) => {
        const selects = Array.from(scope.querySelectorAll('.admin-content select:not([multiple]):not([size]):not(.custom-select-native), [data-install-content] select:not([multiple]):not([size]):not(.custom-select-native)'));
        if (!selects.length) {
            return;
        }

        root.classList.add('has-custom-select');

        selects.forEach((select) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select';
        let typeAheadBuffer = '';
        let typeAheadTimer = null;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'custom-select-button';
        button.setAttribute('data-custom-select-button', '');
        button.setAttribute('aria-haspopup', 'listbox');
        button.setAttribute('aria-expanded', 'false');

        const buttonLabel = document.createElement('span');
        buttonLabel.className = 'custom-select-button-label';

        const buttonIcon = document.createElement('span');
        buttonIcon.className = 'field-overlay field-overlay-end field-icon';
        buttonIcon.innerHTML = `<svg class="icon" aria-hidden="true" focusable="false"><use href="${arrowIconHref}"></use></svg>`;

        button.appendChild(buttonLabel);
        button.appendChild(buttonIcon);
        sync(select, buttonLabel);

        const dropdown = document.createElement('div');
        dropdown.className = 'custom-select-dropdown';
        const list = document.createElement('ul');
        list.className = 'custom-select-list';
        list.setAttribute('role', 'listbox');
        let searchInput = null;
        if (select.options.length > 5) {
            wrapper.classList.add('is-scrollable');
        }
        if (select.options.length >= searchMinOptions) {
            const searchWrap = document.createElement('div');
            searchWrap.className = 'custom-select-search-wrap';
            searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.className = 'custom-select-search';
            searchInput.placeholder = searchPlaceholder();
            searchInput.autocomplete = 'off';
            searchWrap.appendChild(searchInput);
            dropdown.appendChild(searchWrap);
        }

        Array.from(select.options).forEach((option) => {
            const item = document.createElement('li');
            item.className = 'custom-select-option';
            if (option.disabled) {
                item.classList.add('disabled');
            }
            if (option.selected) {
                item.classList.add('selected');
            }
            item.textContent = option.textContent || '';
            item.setAttribute('role', 'option');
            item.setAttribute('aria-selected', option.selected ? 'true' : 'false');
            item.dataset.value = option.value;
            item.dataset.searchLabel = normalize(option.textContent || '');

            item.addEventListener('click', () => {
                selectItem(select, list, item, buttonLabel);
            });

            list.appendChild(item);
        });

        dropdown.appendChild(list);
        wrapper.appendChild(button);
        wrapper.appendChild(dropdown);
        select.insertAdjacentElement('afterend', wrapper);
        select.classList.add('custom-select-native');
        syncDisabled(select, button, wrapper);

        const resetTypeAhead = () => {
            typeAheadBuffer = '';
            if (typeAheadTimer) {
                window.clearTimeout(typeAheadTimer);
                typeAheadTimer = null;
            }
        };
        const pushTypeAhead = (key) => {
            typeAheadBuffer += key;
            if (typeAheadTimer) {
                window.clearTimeout(typeAheadTimer);
            }
            typeAheadTimer = window.setTimeout(resetTypeAhead, typeAheadResetMs);
            return typeAheadBuffer;
        };
        const openDropdown = (seed = '') => {
            closeOpened();
            wrapper.classList.add('open');
            button.setAttribute('aria-expanded', 'true');
            opened = wrapper;
            if (searchInput) {
                searchInput.value = seed;
                filterItems(list, searchInput.value);
                setActiveItem(list, getCurrentItem(list));
                searchInput.focus();
                searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
                return;
            }
            setActiveItem(list, getCurrentItem(list));
            if (seed) {
                const matched = findByPrefix(list, seed);
                if (matched) {
                    setActiveItem(list, matched);
                }
            }
        };

        button.addEventListener('click', () => {
            if (button.disabled) {
                return;
            }
            const isOpen = wrapper.classList.contains('open');
            if (isOpen) {
                closeOpened();
                return;
            }
            openDropdown();
        });

        button.addEventListener('keydown', (event) => {
            if (button.disabled) {
                return;
            }
            if (isTypeKey(event)) {
                event.preventDefault();
                if (searchInput) {
                    openDropdown(event.key);
                    return;
                }
                if (!wrapper.classList.contains('open')) {
                    openDropdown();
                }
                const matched = findByPrefix(list, pushTypeAhead(event.key));
                if (matched) {
                    setActiveItem(list, matched);
                }
                return;
            }
            if (!['ArrowDown', 'ArrowUp', 'Enter', ' ', 'Escape'].includes(event.key)) {
                return;
            }
            event.preventDefault();
            if (!wrapper.classList.contains('open')) {
                openDropdown();
                return;
            }
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                moveActiveItem(list, event.key === 'ArrowDown' ? 'next' : 'prev');
                return;
            }
            if (event.key === 'Enter' || event.key === ' ') {
                selectItem(select, list, getActiveItem(list) || getCurrentItem(list), buttonLabel);
                return;
            }
            if (event.key === 'Escape') {
                closeOpened();
            }
        });
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                filterItems(list, searchInput.value);
                setActiveItem(list, getCurrentItem(list));
            });
            searchInput.addEventListener('keydown', (event) => {
                if (!['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].includes(event.key)) {
                    return;
                }
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    moveActiveItem(list, event.key === 'ArrowDown' ? 'next' : 'prev');
                    return;
                }
                if (event.key === 'Enter') {
                    event.preventDefault();
                    selectItem(select, list, getActiveItem(list) || getCurrentItem(list), buttonLabel);
                    return;
                }
                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeOpened();
                    button.focus();
                }
            });
        }

        select.addEventListener('change', () => {
            sync(select, buttonLabel);
            const selected = Array.from(list.querySelectorAll('.custom-select-option')).find((el) => el.dataset.value === select.value) || null;
            list.querySelectorAll('.custom-select-option').forEach((el) => {
                el.classList.toggle('selected', el === selected);
                el.setAttribute('aria-selected', el === selected ? 'true' : 'false');
            });
            if (!wrapper.classList.contains('open')) {
                setActiveItem(list, null);
            }
            syncDisabled(select, button, wrapper);
            resetTypeAhead();
        });

        const observer = new MutationObserver(() => {
            syncDisabled(select, button, wrapper);
        });
        observer.observe(select, { attributes: true, attributeFilter: ['disabled'] });
        });
    };

    enhance(document);

    window.tinycms = window.tinycms || {};
    window.tinycms.ui = window.tinycms.ui || {};
    window.tinycms.ui.customSelect = {
        init: enhance,
    };

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }
        if (!event.target.closest('.custom-select')) {
            closeOpened();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeOpened();
        }
    });
})();
