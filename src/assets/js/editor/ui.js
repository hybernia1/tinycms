(function () {
    var t = window.tinycms?.i18n?.t || function () { return ''; };

    function createIconButton(icon, command, title) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'wysiwyg-btn';
        button.setAttribute('data-command', command);
        button.setAttribute('aria-label', title);
        button.title = title;
        button.innerHTML = '<svg aria-hidden="true"><use href="/src/assets/svg/icons.svg#icon-' + icon + '"></use></svg>';
        return button;
    }

    function createMenuItem(icon, command, label) {
        var item = document.createElement('button');
        item.type = 'button';
        item.className = 'wysiwyg-menu-item';
        item.setAttribute('data-command', command);
        item.innerHTML = '<svg aria-hidden="true"><use href="/src/assets/svg/icons.svg#icon-' + icon + '"></use></svg><span>' + label + '</span>';
        return item;
    }

    function createLinkToolButton(icon, role, title) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'wysiwyg-link-tool-btn';
        button.setAttribute('data-role', role);
        button.setAttribute('aria-label', title);
        button.title = title;
        button.innerHTML = '<svg aria-hidden="true"><use href="/src/assets/svg/icons.svg#icon-' + icon + '"></use></svg>';
        return button;
    }

    function createHeadingGroup() {
        var group = document.createElement('div');
        group.className = 'wysiwyg-group wysiwyg-group-heading';

        var toggle = createIconButton('w-heading', 'toggleHeadingMenu', t('editor.headings'));

        var menu = document.createElement('div');
        menu.className = 'wysiwyg-menu wysiwyg-menu-heading';

        menu.appendChild(createMenuItem('w-heading', 'formatBlock:p', t('editor.paragraph')));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h1', t('editor.heading_1')));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h2', t('editor.heading_2')));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h3', t('editor.heading_3')));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h4', t('editor.heading_4')));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h5', t('editor.heading_5')));
        menu.appendChild(createMenuItem('w-heading', 'formatBlock:h6', t('editor.heading_6')));
        group.appendChild(toggle);
        group.appendChild(menu);
        return group;
    }

    function createListGroup() {
        var group = document.createElement('div');
        group.className = 'wysiwyg-group wysiwyg-group-list';

        var toggle = createIconButton('w-ul', 'toggleListMenu', t('editor.lists'));

        var menu = document.createElement('div');
        menu.className = 'wysiwyg-menu wysiwyg-menu-list';

        menu.appendChild(createMenuItem('w-ul', 'insertUnorderedList', '' + t('editor.list_bulleted') + ''));
        menu.appendChild(createMenuItem('w-ol', 'insertOrderedList', '' + t('editor.list_numbered') + ''));
        group.appendChild(toggle);
        group.appendChild(menu);
        return group;
    }

    function createAlignGroup() {
        var group = document.createElement('div');
        group.className = 'wysiwyg-group wysiwyg-group-align';

        var toggle = createIconButton('w-align-left', 'toggleAlignMenu', '' + t('editor.alignment') + '');

        var menu = document.createElement('div');
        menu.className = 'wysiwyg-menu wysiwyg-menu-align';

        menu.appendChild(createMenuItem('w-align-left', 'justifyLeft', '' + t('editor.align_left') + ''));
        menu.appendChild(createMenuItem('w-align-center', 'justifyCenter', '' + t('editor.align_center') + ''));
        menu.appendChild(createMenuItem('w-align-right', 'justifyRight', '' + t('editor.align_right') + ''));
        menu.appendChild(createMenuItem('w-align-justify', 'justifyFull', t('editor.align_justify')));
        group.appendChild(toggle);
        group.appendChild(menu);
        return group;
    }

    function createLinkModal() {
        var modal = document.createElement('div');
        modal.className = 'modal-overlay wysiwyg-link-modal';
        modal.setAttribute('data-modal', '');

        var dialog = document.createElement('div');
        dialog.className = 'modal wysiwyg-link-dialog';

        var title = document.createElement('h3');
        title.className = 'wysiwyg-link-title';
        title.textContent = '' + t('editor.insert_link') + '';

        var input = document.createElement('input');
        input.type = 'url';
        input.placeholder = 'https://';
        input.className = 'wysiwyg-link-input';
        input.setAttribute('data-role', 'link-input');

        var textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.placeholder = t('editor.link_text');
        textInput.className = 'wysiwyg-link-input';
        textInput.setAttribute('data-role', 'link-text-input');

        var options = document.createElement('div');
        options.className = 'wysiwyg-link-options';

        var targetOption = document.createElement('label');
        targetOption.className = 'wysiwyg-link-option';
        var targetInput = document.createElement('input');
        targetInput.type = 'checkbox';
        targetInput.setAttribute('data-role', 'link-target-blank');
        targetOption.appendChild(targetInput);
        targetOption.appendChild(document.createTextNode(' ' + t('editor.open_new_window')));

        var nofollowOption = document.createElement('label');
        nofollowOption.className = 'wysiwyg-link-option';
        var nofollowInput = document.createElement('input');
        nofollowInput.type = 'checkbox';
        nofollowInput.setAttribute('data-role', 'link-nofollow');
        nofollowOption.appendChild(nofollowInput);
        nofollowOption.appendChild(document.createTextNode(' ' + t('editor.add_nofollow')));

        var actions = document.createElement('div');
        actions.className = 'modal-actions wysiwyg-link-actions';

        var cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'btn btn-light';
        cancel.setAttribute('data-role', 'link-cancel');
        cancel.setAttribute('data-modal-close', '');
        cancel.textContent = '' + t('editor.cancel') + '';

        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-light';
        remove.setAttribute('data-role', 'link-remove');
        remove.textContent = t('editor.remove_link');

        var confirm = document.createElement('button');
        confirm.type = 'button';
        confirm.className = 'btn btn-primary';
        confirm.setAttribute('data-role', 'link-apply');
        confirm.setAttribute('data-modal-confirm', '');
        confirm.setAttribute('data-modal-confirm-manual', '');
        confirm.textContent = '' + t('editor.save') + '';

        actions.appendChild(cancel);
        actions.appendChild(remove);
        actions.appendChild(confirm);
        options.appendChild(targetOption);
        options.appendChild(nofollowOption);
        dialog.appendChild(title);
        dialog.appendChild(input);
        dialog.appendChild(textInput);
        dialog.appendChild(options);
        dialog.appendChild(actions);
        modal.appendChild(dialog);
        return modal;
    }

    window.tinycmsEditorUi = {
        createIconButton: createIconButton,
        createLinkToolButton: createLinkToolButton,
        createHeadingGroup: createHeadingGroup,
        createListGroup: createListGroup,
        createAlignGroup: createAlignGroup,
        createLinkModal: createLinkModal,
    };
})();
