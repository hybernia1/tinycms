(() => {
    const root = document.body;
    if (!root) {
        return;
    }

    const selects = Array.from(document.querySelectorAll('.admin-content select:not([multiple]):not([size]), [data-install-content] select:not([multiple]):not([size])'));
    if (!selects.length) {
        return;
    }

    root.classList.add('has-custom-select');

    let opened = null;
    const sampleIconUse = document.querySelector('svg.icon use');
    const iconBase = sampleIconUse ? (sampleIconUse.getAttribute('href') || '').split('#')[0] : String(window.tinycmsIconSprite || '');
    const arrowIconHref = `${iconBase}#icon-chevron-down`;

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

    const getEnabledItems = (list) => Array.from(list.querySelectorAll('.custom-select-option:not(.disabled)'));
    const getCurrentItem = (list) => list.querySelector('.custom-select-option.selected:not(.disabled)') || getEnabledItems(list)[0] || null;
    const setActiveItem = (list, item) => {
        list.querySelectorAll('.custom-select-option').forEach((el) => {
            el.classList.toggle('active', el === item);
        });
        if (item) {
            item.scrollIntoView({ block: 'nearest' });
        }
    };
    const getActiveItem = (list) => list.querySelector('.custom-select-option.active:not(.disabled)');
    const selectItem = (select, list, item, buttonLabel) => {
        if (!item || item.classList.contains('disabled')) {
            return;
        }
        select.value = item.dataset.value || '';
        select.dispatchEvent(new Event('change', { bubbles: true }));
        sync(select, buttonLabel);
        closeOpened();
    };

    selects.forEach((select) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select';

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
        if (select.options.length > 5) {
            wrapper.classList.add('is-scrollable');
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

        button.addEventListener('click', () => {
            if (button.disabled) {
                return;
            }
            const isOpen = wrapper.classList.contains('open');
            closeOpened();
            if (!isOpen) {
                wrapper.classList.add('open');
                button.setAttribute('aria-expanded', 'true');
                opened = wrapper;
                setActiveItem(list, getCurrentItem(list));
            }
        });

        button.addEventListener('keydown', (event) => {
            if (button.disabled) {
                return;
            }
            if (!['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                return;
            }
            event.preventDefault();
            if (!wrapper.classList.contains('open')) {
                closeOpened();
                wrapper.classList.add('open');
                button.setAttribute('aria-expanded', 'true');
                opened = wrapper;
                setActiveItem(list, getCurrentItem(list));
                return;
            }
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                const enabledItems = getEnabledItems(list);
                if (!enabledItems.length) {
                    return;
                }
                const activeItem = getActiveItem(list) || getCurrentItem(list);
                const activeIndex = Math.max(0, enabledItems.indexOf(activeItem));
                const nextIndex = event.key === 'ArrowDown'
                    ? Math.min(enabledItems.length - 1, activeIndex + 1)
                    : Math.max(0, activeIndex - 1);
                setActiveItem(list, enabledItems[nextIndex]);
                return;
            }
            if (event.key === 'Enter' || event.key === ' ') {
                selectItem(select, list, getActiveItem(list) || getCurrentItem(list), buttonLabel);
            }
        });

        list.addEventListener('keydown', (event) => {
            if (!wrapper.classList.contains('open')) {
                return;
            }
            if (!['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                return;
            }
            event.preventDefault();
            const enabledItems = getEnabledItems(list);
            if (!enabledItems.length) {
                return;
            }
            if (event.key === 'Enter' || event.key === ' ') {
                selectItem(select, list, getActiveItem(list) || getCurrentItem(list), buttonLabel);
                return;
            }
            const activeItem = getActiveItem(list) || getCurrentItem(list);
            const activeIndex = Math.max(0, enabledItems.indexOf(activeItem));
            const nextIndex = event.key === 'ArrowDown'
                ? Math.min(enabledItems.length - 1, activeIndex + 1)
                : Math.max(0, activeIndex - 1);
            setActiveItem(list, enabledItems[nextIndex]);
        });

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
        });

        const observer = new MutationObserver(() => {
            syncDisabled(select, button, wrapper);
        });
        observer.observe(select, { attributes: true, attributeFilter: ['disabled'] });
    });

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
