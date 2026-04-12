(() => {
const i18n = window.tinycmsI18n || {};

const byPath = (path) => String(path || '').split('.').reduce((acc, key) => (
    acc && Object.prototype.hasOwnProperty.call(acc, key) ? acc[key] : undefined
), i18n);

window.tinycmsI18nValue = byPath;
window.tinycmsT = (path, fallback = '') => {
    const value = byPath(path);
    return typeof value === 'string' && value !== '' ? value : fallback;
};
window.tinycmsTA = (path, fallback = []) => {
    const value = byPath(path);
    return Array.isArray(value) && value.length ? value : fallback;
};
})();
