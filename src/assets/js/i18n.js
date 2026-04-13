(() => {
const i18n = window.tinycmsI18n || {};

const value = (path) => String(path || '').split('.').reduce((acc, key) => (
    acc && Object.prototype.hasOwnProperty.call(acc, key) ? acc[key] : undefined
), i18n);

const api = {
    value,
    t: (path, fallback = '') => {
        const result = value(path);
        return typeof result === 'string' && result !== '' ? result : fallback;
    },
    ta: (path, fallback = []) => {
        const result = value(path);
        return Array.isArray(result) && result.length ? result : fallback;
    },
};

window.tinycms = window.tinycms || {};
window.tinycms.i18n = api;
})();
