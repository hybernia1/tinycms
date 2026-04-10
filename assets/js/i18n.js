(() => {
const dictionary = window.tinycmsI18n || {};
window.tinycmsI18nHelper = {
    t(path, fallback = '') {
        const value = String(path || '')
            .split('.')
            .reduce((acc, key) => (acc && Object.prototype.hasOwnProperty.call(acc, key) ? acc[key] : undefined), dictionary);
        return typeof value === 'string' && value !== '' ? value : fallback;
    },
};
})();
