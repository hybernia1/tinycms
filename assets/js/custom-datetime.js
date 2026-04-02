(() => {
    const root = document.body;
    if (!root) {
        return;
    }

    const inputs = Array.from(document.querySelectorAll('.admin-content input[type="datetime-local"]'));
    if (!inputs.length) {
        return;
    }

    root.classList.add('has-custom-datetime');
    let opened = null;

    const splitValue = (value) => {
        if (!value || !value.includes('T')) {
            return { date: '', time: '' };
        }
        const [date, time] = value.split('T');
        return { date: date || '', time: (time || '').slice(0, 5) };
    };

    const formatLabel = (value) => {
        if (!value) {
            return 'Vybrat datum a čas';
        }
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return 'Vybrat datum a čas';
        }
        return new Intl.DateTimeFormat('cs-CZ', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    };

    const syncHidden = (hidden, dateInput, timeInput, trigger) => {
        if (!dateInput.value) {
            hidden.value = '';
            trigger.textContent = formatLabel('');
            return;
        }
        hidden.value = `${dateInput.value}T${timeInput.value || '00:00'}`;
        trigger.textContent = formatLabel(hidden.value);
    };

    const closeOpened = () => {
        if (!opened) {
            return;
        }
        opened.wrapper.classList.remove('open');
        opened.trigger.setAttribute('aria-expanded', 'false');
        opened = null;
    };

    const setNow = (dateInput, timeInput) => {
        const now = new Date();
        const year = String(now.getFullYear());
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        dateInput.value = `${year}-${month}-${day}`;
        timeInput.value = `${hours}:${minutes}`;
    };

    inputs.forEach((hiddenInput) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-datetime';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'custom-datetime-trigger';
        trigger.setAttribute('aria-haspopup', 'dialog');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.disabled = hiddenInput.disabled;

        const panel = document.createElement('div');
        panel.className = 'custom-datetime-panel';

        const controls = document.createElement('div');
        controls.className = 'custom-datetime-controls';

        const dateInput = document.createElement('input');
        dateInput.type = 'date';
        dateInput.className = 'custom-datetime-date';
        dateInput.required = hiddenInput.required;
        dateInput.disabled = hiddenInput.disabled;

        const timeInput = document.createElement('input');
        timeInput.type = 'time';
        timeInput.className = 'custom-datetime-time';
        timeInput.step = 60;
        timeInput.disabled = hiddenInput.disabled;

        const { date, time } = splitValue(hiddenInput.value);
        dateInput.value = date;
        timeInput.value = time;
        trigger.textContent = formatLabel(hiddenInput.value);

        controls.appendChild(dateInput);
        controls.appendChild(timeInput);
        panel.appendChild(controls);

        const actions = document.createElement('div');
        actions.className = 'custom-datetime-actions';
        const nowButton = document.createElement('button');
        nowButton.type = 'button';
        nowButton.className = 'btn btn-light';
        nowButton.textContent = 'Teď';
        nowButton.disabled = hiddenInput.disabled;
        const clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = 'btn btn-light';
        clearButton.textContent = 'Vymazat';
        clearButton.disabled = hiddenInput.disabled;
        const applyButton = document.createElement('button');
        applyButton.type = 'button';
        applyButton.className = 'btn btn-primary';
        applyButton.textContent = 'Použít';
        applyButton.disabled = hiddenInput.disabled;
        actions.appendChild(nowButton);
        actions.appendChild(clearButton);
        actions.appendChild(applyButton);
        panel.appendChild(actions);

        nowButton.addEventListener('click', () => {
            setNow(dateInput, timeInput);
        });

        clearButton.addEventListener('click', () => {
            dateInput.value = '';
            timeInput.value = '';
            syncHidden(hiddenInput, dateInput, timeInput, trigger);
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            closeOpened();
        });

        applyButton.addEventListener('click', () => {
            syncHidden(hiddenInput, dateInput, timeInput, trigger);
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            closeOpened();
        });

        trigger.addEventListener('click', () => {
            if (trigger.disabled) {
                return;
            }
            const isOpen = wrapper.classList.contains('open');
            closeOpened();
            if (!isOpen) {
                wrapper.classList.add('open');
                trigger.setAttribute('aria-expanded', 'true');
                opened = { wrapper, trigger };
            }
        });

        wrapper.appendChild(trigger);
        wrapper.appendChild(panel);
        hiddenInput.insertAdjacentElement('afterend', wrapper);
        hiddenInput.classList.add('custom-datetime-native');
    });

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }
        if (!event.target.closest('.custom-datetime')) {
            closeOpened();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeOpened();
        }
    });
})();
