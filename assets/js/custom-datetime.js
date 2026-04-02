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

    const splitValue = (value) => {
        if (!value || !value.includes('T')) {
            return { date: '', time: '' };
        }
        const [date, time] = value.split('T');
        return { date: date || '', time: (time || '').slice(0, 5) };
    };

    const syncHidden = (hidden, dateInput, timeInput) => {
        if (!dateInput.value) {
            hidden.value = '';
            return;
        }
        hidden.value = `${dateInput.value}T${timeInput.value || '00:00'}`;
    };

    inputs.forEach((hiddenInput) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-datetime';
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

        const apply = () => {
            syncHidden(hiddenInput, dateInput, timeInput);
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        };

        dateInput.addEventListener('change', apply);
        timeInput.addEventListener('change', apply);

        wrapper.appendChild(dateInput);
        wrapper.appendChild(timeInput);
        hiddenInput.insertAdjacentElement('afterend', wrapper);
        hiddenInput.classList.add('custom-datetime-native');
    });
})();
