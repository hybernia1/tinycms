(() => {
    const app = window.tinycms = window.tinycms || {};

    const afterElement = (container, itemSelector, y) => {
        const items = Array.from(container.querySelectorAll(itemSelector)).filter((item) => !item.classList.contains('is-dragging'));

        return items.reduce((closest, item) => {
            const box = item.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            return offset < 0 && offset > closest.offset ? { offset, item } : closest;
        }, { offset: Number.NEGATIVE_INFINITY, item: null }).item;
    };

    app.builderDnd = {
        init({ root, itemSelector, containerSelector, fallbackSelector = '', handleSelector, onChange }) {
            if (!root || !itemSelector || !containerSelector || !handleSelector) {
                return;
            }

            let dragged = null;
            const placeholder = document.createElement('div');
            placeholder.className = 'builder-drag-placeholder';

            const containerFrom = (target) => {
                const element = target instanceof Element ? target : null;
                return element?.closest(containerSelector)
                    || (fallbackSelector !== '' ? element?.closest(fallbackSelector)?.querySelector(containerSelector) : null)
                    || null;
            };

            const finish = () => {
                dragged?.classList.remove('is-dragging');
                dragged = null;
                placeholder.remove();
            };

            root.addEventListener('dragstart', (event) => {
                if (!(event.target instanceof Element) || !event.target.closest(handleSelector)) {
                    return;
                }

                const item = event.target.closest(itemSelector);
                if (!item) {
                    return;
                }

                if (!event.dataTransfer) {
                    return;
                }

                dragged = item;
                placeholder.style.height = `${item.offsetHeight}px`;
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', '');
                requestAnimationFrame(() => item.classList.add('is-dragging'));
            });

            root.addEventListener('dragover', (event) => {
                if (!dragged) {
                    return;
                }

                const container = containerFrom(event.target);
                if (!container || !root.contains(container)) {
                    return;
                }

                event.preventDefault();
                if (event.dataTransfer) {
                    event.dataTransfer.dropEffect = 'move';
                }
                const after = afterElement(container, itemSelector, event.clientY);
                if (after) {
                    container.insertBefore(placeholder, after);
                    return;
                }

                container.appendChild(placeholder);
            });

            root.addEventListener('drop', (event) => {
                if (!dragged || !placeholder.parentElement) {
                    return;
                }

                event.preventDefault();
                placeholder.parentElement.insertBefore(dragged, placeholder);
                finish();
                onChange?.();
            });

            root.addEventListener('dragend', finish);
        },
    };
})();
