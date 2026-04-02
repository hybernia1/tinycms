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

    const weekdayLabels = ['Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne'];

    const parseValue = (value) => {
        if (!value || !value.includes('T')) {
            return null;
        }
        const [datePart, timePart] = value.split('T');
        const [year, month, day] = datePart.split('-').map(Number);
        const [hour, minute] = (timePart || '00:00').split(':').map(Number);
        if (!year || !month || !day) {
            return null;
        }
        return new Date(year, month - 1, day, hour || 0, minute || 0, 0, 0);
    };

    const toValue = (date) => {
        const year = String(date.getFullYear());
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hour = String(date.getHours()).padStart(2, '0');
        const minute = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hour}:${minute}`;
    };

    const formatLabel = (date) => {
        if (!date) {
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

    const closeOpened = () => {
        if (!opened) {
            return;
        }
        opened.wrapper.classList.remove('open');
        opened.trigger.setAttribute('aria-expanded', 'false');
        opened = null;
    };

    const sameDay = (a, b) => a && b
        && a.getFullYear() === b.getFullYear()
        && a.getMonth() === b.getMonth()
        && a.getDate() === b.getDate();

    const buildOption = (value, text) => {
        const option = document.createElement('option');
        option.value = String(value);
        option.textContent = text;
        return option;
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

        const header = document.createElement('div');
        header.className = 'custom-datetime-header';
        const prev = document.createElement('button');
        prev.type = 'button';
        prev.className = 'btn btn-light btn-icon';
        prev.textContent = '‹';
        const title = document.createElement('strong');
        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'btn btn-light btn-icon';
        next.textContent = '›';
        header.appendChild(prev);
        header.appendChild(title);
        header.appendChild(next);
        panel.appendChild(header);

        const weekdays = document.createElement('div');
        weekdays.className = 'custom-datetime-weekdays';
        weekdayLabels.forEach((label) => {
            const el = document.createElement('span');
            el.textContent = label;
            weekdays.appendChild(el);
        });
        panel.appendChild(weekdays);

        const grid = document.createElement('div');
        grid.className = 'custom-datetime-grid';
        panel.appendChild(grid);

        const timeRow = document.createElement('div');
        timeRow.className = 'custom-datetime-time-row';
        const hourSelect = document.createElement('select');
        hourSelect.className = 'custom-datetime-hour';
        for (let h = 0; h < 24; h += 1) {
            hourSelect.appendChild(buildOption(h, String(h).padStart(2, '0')));
        }
        const minuteSelect = document.createElement('select');
        minuteSelect.className = 'custom-datetime-minute';
        for (let m = 0; m < 60; m += 1) {
            minuteSelect.appendChild(buildOption(m, String(m).padStart(2, '0')));
        }
        const colon = document.createElement('span');
        colon.className = 'custom-datetime-colon';
        colon.textContent = ':';
        timeRow.appendChild(hourSelect);
        timeRow.appendChild(colon);
        timeRow.appendChild(minuteSelect);
        panel.appendChild(timeRow);

        const actions = document.createElement('div');
        actions.className = 'custom-datetime-actions';
        const todayButton = document.createElement('button');
        todayButton.type = 'button';
        todayButton.className = 'btn btn-light';
        todayButton.textContent = 'Dnes';
        const clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = 'btn btn-light';
        clearButton.textContent = 'Vymazat';
        const cancelButton = document.createElement('button');
        cancelButton.type = 'button';
        cancelButton.className = 'btn btn-light';
        cancelButton.textContent = 'Zrušit';
        const okButton = document.createElement('button');
        okButton.type = 'button';
        okButton.className = 'btn btn-primary';
        okButton.textContent = 'OK';
        actions.appendChild(todayButton);
        actions.appendChild(clearButton);
        actions.appendChild(cancelButton);
        actions.appendChild(okButton);
        panel.appendChild(actions);

        let selectedDate = parseValue(hiddenInput.value);
        let viewMonth = selectedDate ? new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1) : new Date(new Date().getFullYear(), new Date().getMonth(), 1);
        let draftDate = selectedDate ? new Date(selectedDate) : null;

        const syncTrigger = () => {
            trigger.textContent = formatLabel(selectedDate);
        };

        const syncTimeSelects = () => {
            hourSelect.value = String(draftDate ? draftDate.getHours() : 0);
            minuteSelect.value = String(draftDate ? draftDate.getMinutes() : 0);
        };

        const render = () => {
            title.textContent = new Intl.DateTimeFormat('cs-CZ', { month: 'long', year: 'numeric' }).format(viewMonth);
            grid.innerHTML = '';
            const start = new Date(viewMonth.getFullYear(), viewMonth.getMonth(), 1);
            const offset = (start.getDay() + 6) % 7;
            const firstVisible = new Date(viewMonth.getFullYear(), viewMonth.getMonth(), 1 - offset);
            const today = new Date();

            for (let i = 0; i < 42; i += 1) {
                const day = new Date(firstVisible.getFullYear(), firstVisible.getMonth(), firstVisible.getDate() + i);
                const cell = document.createElement('button');
                cell.type = 'button';
                cell.className = 'custom-datetime-day';
                cell.textContent = String(day.getDate());
                if (day.getMonth() !== viewMonth.getMonth()) {
                    cell.classList.add('muted');
                }
                if (sameDay(day, today)) {
                    cell.classList.add('today');
                }
                if (sameDay(day, draftDate)) {
                    cell.classList.add('selected');
                }
                cell.addEventListener('click', () => {
                    const base = draftDate || new Date();
                    draftDate = new Date(day.getFullYear(), day.getMonth(), day.getDate(), base.getHours(), base.getMinutes(), 0, 0);
                    viewMonth = new Date(day.getFullYear(), day.getMonth(), 1);
                    syncTimeSelects();
                    render();
                });
                grid.appendChild(cell);
            }
        };

        const open = () => {
            if (trigger.disabled) {
                return;
            }
            const parsed = parseValue(hiddenInput.value);
            selectedDate = parsed;
            draftDate = parsed ? new Date(parsed) : null;
            viewMonth = draftDate ? new Date(draftDate.getFullYear(), draftDate.getMonth(), 1) : new Date(new Date().getFullYear(), new Date().getMonth(), 1);
            syncTimeSelects();
            render();
            closeOpened();
            wrapper.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            opened = { wrapper, trigger };
        };

        prev.addEventListener('click', () => {
            viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() - 1, 1);
            render();
        });

        next.addEventListener('click', () => {
            viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() + 1, 1);
            render();
        });

        hourSelect.addEventListener('change', () => {
            if (!draftDate) {
                draftDate = new Date(viewMonth.getFullYear(), viewMonth.getMonth(), 1, 0, 0, 0, 0);
            }
            draftDate.setHours(Number(hourSelect.value), draftDate.getMinutes(), 0, 0);
        });

        minuteSelect.addEventListener('change', () => {
            if (!draftDate) {
                draftDate = new Date(viewMonth.getFullYear(), viewMonth.getMonth(), 1, 0, 0, 0, 0);
            }
            draftDate.setMinutes(Number(minuteSelect.value), 0, 0);
        });

        todayButton.addEventListener('click', () => {
            const now = new Date();
            draftDate = new Date(now);
            viewMonth = new Date(now.getFullYear(), now.getMonth(), 1);
            syncTimeSelects();
            render();
        });

        clearButton.addEventListener('click', () => {
            draftDate = null;
            selectedDate = null;
            hiddenInput.value = '';
            syncTrigger();
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            closeOpened();
        });

        cancelButton.addEventListener('click', () => {
            closeOpened();
        });

        okButton.addEventListener('click', () => {
            selectedDate = draftDate ? new Date(draftDate) : null;
            hiddenInput.value = selectedDate ? toValue(selectedDate) : '';
            syncTrigger();
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            closeOpened();
        });

        trigger.addEventListener('click', () => {
            if (wrapper.classList.contains('open')) {
                closeOpened();
                return;
            }
            open();
        });

        syncTrigger();
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
