const FLASH_AUTO_CLOSE_MS = 3500;

const closeFlash = (flash) => {
    if (flash instanceof HTMLElement) {
        flash.remove();
    }
};

const scheduleFlashClose = (flash) => {
    if (!(flash instanceof HTMLElement) || flash.dataset.flashAutoclose === '1') {
        return;
    }

    flash.dataset.flashAutoclose = '1';
    window.setTimeout(() => closeFlash(flash), FLASH_AUTO_CLOSE_MS);
};

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-flash-close]');
    if (!button) {
        return;
    }

    closeFlash(button.closest('.flash'));
});

document.querySelectorAll('.flash').forEach(scheduleFlashClose);

new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }

            if (node.matches('.flash')) {
                scheduleFlashClose(node);
            }

            node.querySelectorAll('.flash').forEach(scheduleFlashClose);
        });
    });
}).observe(document.body, { childList: true, subtree: true });
