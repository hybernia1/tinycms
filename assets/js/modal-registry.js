(() => {
const modalService = window.tinycmsModal || null;
if (!modalService) {
    return;
}

document.querySelectorAll('[data-modal]').forEach((modal, index) => {
    const id = modal.getAttribute('id') || `modal-${index + 1}`;
    modalService.register(id, { element: modal });
});
})();
