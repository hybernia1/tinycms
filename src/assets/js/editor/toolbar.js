(() => {
const app = window.tinycms = window.tinycms || {};
const editor = app.editor = app.editor || {};
const t = app.i18n?.t || (() => '');
const iconSvg = (name) => app.icons?.icon?.(name, '') || '';

const createIconButton = (icon, command, title) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'wysiwyg-btn';
    button.setAttribute('data-command', command);
    button.setAttribute('aria-label', title);
    button.title = title;
    button.innerHTML = iconSvg(icon);
    return button;
};

const createMenuItem = (icon, command, label) => {
    const item = document.createElement('button');
    item.type = 'button';
    item.className = 'wysiwyg-menu-item';
    item.setAttribute('data-command', command);
    item.innerHTML = iconSvg(icon) + '<span>' + label + '</span>';
    return item;
};

const createLinkToolButton = (icon, role, title) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'wysiwyg-link-tool-btn';
    button.setAttribute('data-role', role);
    button.setAttribute('aria-label', title);
    button.title = title;
    button.innerHTML = iconSvg(icon);
    return button;
};

const createHeadingGroup = () => {
    const group = document.createElement('div');
    group.className = 'wysiwyg-group wysiwyg-group-heading';

    const toggle = createIconButton('w-heading', 'toggleHeadingMenu', t('editor.headings'));

    const menu = document.createElement('div');
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
};

const createListGroup = () => {
    const group = document.createElement('div');
    group.className = 'wysiwyg-group wysiwyg-group-list';

    const toggle = createIconButton('w-ul', 'toggleListMenu', t('editor.lists'));

    const menu = document.createElement('div');
    menu.className = 'wysiwyg-menu wysiwyg-menu-list';

    menu.appendChild(createMenuItem('w-ul', 'insertUnorderedList', t('editor.list_bulleted')));
    menu.appendChild(createMenuItem('w-ol', 'insertOrderedList', t('editor.list_numbered')));
    group.appendChild(toggle);
    group.appendChild(menu);
    return group;
};

const createAlignGroup = () => {
    const group = document.createElement('div');
    group.className = 'wysiwyg-group wysiwyg-group-align';

    const toggle = createIconButton('w-align-left', 'toggleAlignMenu', t('editor.alignment'));

    const menu = document.createElement('div');
    menu.className = 'wysiwyg-menu wysiwyg-menu-align';

    menu.appendChild(createMenuItem('w-align-left', 'justifyLeft', t('editor.align_left')));
    menu.appendChild(createMenuItem('w-align-center', 'justifyCenter', t('editor.align_center')));
    menu.appendChild(createMenuItem('w-align-right', 'justifyRight', t('editor.align_right')));
    menu.appendChild(createMenuItem('w-align-justify', 'justifyFull', t('editor.align_justify')));
    group.appendChild(toggle);
    group.appendChild(menu);
    return group;
};

editor.toolbar = {
    createAlignGroup,
    createHeadingGroup,
    createIconButton,
    createLinkToolButton,
    createListGroup,
};
})();
