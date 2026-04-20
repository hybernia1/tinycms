(function () {
    const roots = Array.from(document.querySelectorAll('[data-settings-tabs]'));
    if (roots.length === 0) {
        return;
    }

    roots.forEach((root) => {
        const triggers = Array.from(root.querySelectorAll('[data-settings-tab-trigger]'));
        const panels = Array.from(root.querySelectorAll('[data-settings-tab-panel]'));
        if (triggers.length === 0 || panels.length === 0) {
            return;
        }

        const setActive = (tabId) => {
            triggers.forEach((trigger) => {
                const active = (trigger.getAttribute('data-settings-tab-trigger') || '') === tabId;
                trigger.classList.toggle('active', active);
                trigger.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            panels.forEach((panel) => {
                panel.hidden = (panel.getAttribute('data-settings-tab-panel') || '') !== tabId;
            });
        };

        triggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const tabId = trigger.getAttribute('data-settings-tab-trigger') || '';
                if (tabId !== '') {
                    setActive(tabId);
                }
            });
        });

        const firstTab = triggers[0]?.getAttribute('data-settings-tab-trigger') || '';
        if (firstTab !== '') {
            setActive(firstTab);
        }
    });
})();
