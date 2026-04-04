const flashRoot = document.querySelector('.admin-content');

const esc = (value) => String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

const iconUse = document.querySelector('svg use[href*="#icon-"]');
const iconSprite = iconUse ? String(iconUse.getAttribute('href') || '').split('#')[0] : '';

const icon = (name) => iconSprite !== ''
    ? `<svg class="icon" aria-hidden="true" focusable="false"><use href="${esc(iconSprite)}#icon-${esc(name)}"></use></svg>`
    : '';

const closeButtonHtml = () => `
    <button type="button" data-flash-close aria-label="Zavřít notifikaci" title="Zavřít notifikaci">
        ${icon('cancel')}
    </button>
`;

const addFlash = (type, message) => {
    if (!flashRoot || String(message || '').trim() === '') {
        return;
    }

    const flash = document.createElement('div');
    flash.className = `flash flash-${esc(type || 'info')}`;
    flash.innerHTML = `<span>${esc(message)}</span>${closeButtonHtml()}`;
    flashRoot.prepend(flash);
};

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-flash-close]');
    if (!button) {
        return;
    }

    const flash = button.closest('.flash');
    if (flash) {
        flash.remove();
    }
});

window.TinyCmsFlash = {
    add: addFlash,
};
