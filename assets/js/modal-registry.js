(() => {
const modalService = window.tinycmsModal || null;
if (!modalService) {
    return;
}

const registerListDeleteModals = () => {
    document.querySelectorAll('[data-modal]').forEach((modal) => {
        const names = modal.getAttributeNames().filter((name) => /^data-[a-z0-9_-]+-delete-modal$/i.test(name));
        names.forEach((attr) => {
            const prefix = attr.replace(/-delete-modal$/i, '');
            modalService.register(modal.getAttribute('id') || attr, {
                element: modal,
                closeSelector: `[${prefix}-delete-cancel]`,
                confirmSelector: `[${prefix}-delete-confirm]`,
                closeOnBackdrop: true,
            });
        });
    });
};

const registerStaticModals = () => {
    const leave = document.getElementById('content-leave-modal');
    if (leave) {
        modalService.register('content-leave-modal', {
            element: leave,
            closeSelector: '[data-content-leave-cancel]',
            confirmSelector: '[data-content-leave-confirm]',
            closeOnBackdrop: true,
        });
    }

    const mediaLibrary = document.getElementById('media-library-modal');
    if (mediaLibrary) {
        modalService.register('media-library-modal', {
            element: mediaLibrary,
            closeSelector: '[data-media-library-close]',
            closeOnBackdrop: true,
        });
    }

    const mediaLibraryDelete = document.getElementById('media-library-delete-modal');
    if (mediaLibraryDelete) {
        modalService.register('media-library-delete-modal', {
            element: mediaLibraryDelete,
            closeSelector: '[data-modal-close]',
            confirmSelector: '[data-modal-confirm]',
            closeOnBackdrop: true,
        });
    }
};

registerListDeleteModals();
registerStaticModals();
})();
