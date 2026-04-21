document.querySelectorAll('[data-settings-tabs]').forEach((tabs) => {
    const root = tabs.closest('form') || document;
    const buttons = Array.from(tabs.querySelectorAll('[data-settings-tab]'));
    const panels = Array.from(root.querySelectorAll('[data-settings-tab-panel]'));
    const storageKey = 'tinycms.settings.activeTab';
    const sessionStore = window.tinycms?.api?.sessionStore || { get: () => '', set: () => {} };

    const hasTab = (tab) => buttons.some((button) => button.getAttribute('data-settings-tab') === tab);
    const activate = (active) => {
        if (!hasTab(active)) {
            return;
        }

        buttons.forEach((item) => {
            item.classList.toggle('active', item.getAttribute('data-settings-tab') === active);
        });
        panels.forEach((panel) => {
            panel.hidden = panel.getAttribute('data-settings-tab-panel') !== active;
        });
    };
    const stored = sessionStore.get(storageKey);
    const hashed = window.location.hash.replace(/^#/, '');
    activate(hashed || stored);
    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const active = button.getAttribute('data-settings-tab') || '';

            sessionStore.set(storageKey, active);
            activate(active);
        });
    });
});
