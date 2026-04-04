const menuTreeRoot = document.querySelector('[data-menu-tree]');

if (menuTreeRoot) {
    const form = menuTreeRoot.querySelector('[data-menu-tree-form]');
    const input = menuTreeRoot.querySelector('[data-menu-tree-input]');
    const treeRoot = menuTreeRoot.querySelector('[data-menu-root]');
    let draggedItem = null;

    const ensureLevel = (item) => {
        let level = item.querySelector(':scope > .menu-tree-level');
        if (!level) {
            level = document.createElement('ul');
            level.className = 'menu-tree-level';
            level.setAttribute('data-menu-level', String(item.getAttribute('data-menu-id') || '0'));
            item.appendChild(level);
        }
        return level;
    };

    const cleanupLevels = () => {
        treeRoot.querySelectorAll('.menu-tree-item').forEach((item) => {
            const level = item.querySelector(':scope > .menu-tree-level');
            if (level && level.children.length === 0) {
                level.remove();
            }
        });
    };

    const serialize = () => {
        const payload = [];
        const walk = (level, parentId) => {
            const items = Array.from(level.children).filter((node) => node.classList.contains('menu-tree-item'));
            items.forEach((item, index) => {
                const id = Number(item.getAttribute('data-menu-id') || '0');
                if (id <= 0) {
                    return;
                }
                payload.push({ id, parent_id: parentId > 0 ? parentId : null, position: index });
                const childLevel = item.querySelector(':scope > .menu-tree-level');
                if (childLevel) {
                    walk(childLevel, id);
                }
            });
        };

        const topLevel = treeRoot.querySelector(':scope > .menu-tree-level');
        if (topLevel) {
            walk(topLevel, 0);
        }

        input.value = JSON.stringify(payload);
    };

    treeRoot.addEventListener('dragstart', (event) => {
        const card = event.target.closest('[data-menu-drag]');
        const item = card ? card.closest('[data-menu-item]') : null;
        if (!item) {
            return;
        }

        draggedItem = item;
        item.classList.add('dragging');
    });

    treeRoot.addEventListener('dragend', () => {
        if (draggedItem) {
            draggedItem.classList.remove('dragging');
        }
        draggedItem = null;
        cleanupLevels();
        serialize();
    });

    treeRoot.addEventListener('dragover', (event) => {
        if (!draggedItem) {
            return;
        }
        const item = event.target.closest('[data-menu-item]');
        if (!item) {
            return;
        }
        event.preventDefault();
    });

    treeRoot.addEventListener('drop', (event) => {
        if (!draggedItem) {
            return;
        }

        const item = event.target.closest('[data-menu-item]');
        if (!item || item === draggedItem || draggedItem.contains(item)) {
            return;
        }

        event.preventDefault();

        const rect = item.getBoundingClientRect();
        const before = event.clientY < rect.top + rect.height * 0.3;
        const after = event.clientY > rect.bottom - rect.height * 0.3;

        if (before) {
            item.parentElement?.insertBefore(draggedItem, item);
            return;
        }

        if (after) {
            item.parentElement?.insertBefore(draggedItem, item.nextSibling);
            return;
        }

        ensureLevel(item).appendChild(draggedItem);
    });

    treeRoot.addEventListener('dragover', (event) => {
        if (!draggedItem || event.target.closest('[data-menu-item]')) {
            return;
        }
        event.preventDefault();
    });

    treeRoot.addEventListener('drop', (event) => {
        if (!draggedItem || event.target.closest('[data-menu-item]')) {
            return;
        }
        event.preventDefault();
        const topLevel = treeRoot.querySelector(':scope > .menu-tree-level');
        if (topLevel) {
            topLevel.appendChild(draggedItem);
        }
    });

    if (form) {
        form.addEventListener('submit', () => {
            serialize();
        });
    }

    serialize();
}
