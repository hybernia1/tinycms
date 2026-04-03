(() => {
    const root = document.body;
    if (!root) {
        return;
    }

    const selects = Array.from(document.querySelectorAll('.admin-content select:not([multiple]):not([size])'));
    if (!selects.length) {
        return;
    }

    root.classList.add('has-custom-select');

    let opened = null;
    const sampleIconUse = document.querySelector('svg.icon use');
    const iconBase = sampleIconUse ? (sampleIconUse.getAttribute('href') || '').split('#')[0] : '';
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
        buttonIcon.className = 'custom-select-button-icon';
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
                if (option.disabled) {
                    return;
                }
                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                list.querySelectorAll('.custom-select-option').forEach((el) => {
                    el.classList.toggle('selected', el === item);
                    el.setAttribute('aria-selected', el === item ? 'true' : 'false');
                });
                sync(select, buttonLabel);
                closeOpened();
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
            }
        });

        select.addEventListener('change', () => {
            sync(select, buttonLabel);
            const selected = Array.from(list.querySelectorAll('.custom-select-option')).find((el) => el.dataset.value === select.value) || null;
            list.querySelectorAll('.custom-select-option').forEach((el) => {
                el.classList.toggle('selected', el === selected);
                el.setAttribute('aria-selected', el === selected ? 'true' : 'false');
            });
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
