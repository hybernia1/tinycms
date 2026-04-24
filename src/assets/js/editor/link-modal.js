(() => {
const t = window.tinycms?.i18n?.t || (() => '');

const create = () => {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay wysiwyg-link-modal';

    const dialog = document.createElement('div');
    dialog.className = 'modal wysiwyg-link-dialog';

    const title = document.createElement('h3');
    title.className = 'wysiwyg-link-title';
    title.textContent = t('editor.insert_link');

    const input = document.createElement('input');
    input.type = 'url';
    input.placeholder = 'https://';
    input.className = 'wysiwyg-link-input';
    input.setAttribute('data-role', 'link-input');

    const textInput = document.createElement('input');
    textInput.type = 'text';
    textInput.placeholder = t('editor.link_text');
    textInput.className = 'wysiwyg-link-input';
    textInput.setAttribute('data-role', 'link-text-input');

    const options = document.createElement('div');
    options.className = 'wysiwyg-link-options';

    const targetOption = document.createElement('label');
    targetOption.className = 'wysiwyg-link-option';
    const targetInput = document.createElement('input');
    targetInput.type = 'checkbox';
    targetInput.setAttribute('data-role', 'link-target-blank');
    targetOption.appendChild(targetInput);
    targetOption.appendChild(document.createTextNode(' ' + t('editor.open_new_window')));

    const nofollowOption = document.createElement('label');
    nofollowOption.className = 'wysiwyg-link-option';
    const nofollowInput = document.createElement('input');
    nofollowInput.type = 'checkbox';
    nofollowInput.setAttribute('data-role', 'link-nofollow');
    nofollowOption.appendChild(nofollowInput);
    nofollowOption.appendChild(document.createTextNode(' ' + t('editor.add_nofollow')));

    const actions = document.createElement('div');
    actions.className = 'modal-actions wysiwyg-link-actions';

    const cancel = document.createElement('button');
    cancel.type = 'button';
    cancel.className = 'btn btn-light';
    cancel.setAttribute('data-role', 'link-cancel');
    cancel.textContent = t('editor.cancel');

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'btn btn-light';
    remove.setAttribute('data-role', 'link-remove');
    remove.textContent = t('editor.remove_link');

    const confirm = document.createElement('button');
    confirm.type = 'button';
    confirm.className = 'btn btn-primary';
    confirm.setAttribute('data-role', 'link-apply');
    confirm.textContent = t('editor.save');

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
};

window.tinycms = window.tinycms || {};
window.tinycms.editor = window.tinycms.editor || {};
window.tinycms.editor.linkModal = { create };
})();
