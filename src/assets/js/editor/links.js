(() => {
const app = window.tinycms = window.tinycms || {};
const editor = app.editor = app.editor || {};
const t = app.i18n?.t || (() => '');

const create = (options) => {
    let linkRange = null;
    let activeLink = null;
    let linkPasteSeq = 0;

    const linkModal = editor.linkModal.create();
    const linkTools = document.createElement('div');
    linkTools.className = 'wysiwyg-link-tools';
    linkTools.setAttribute('contenteditable', 'false');
    linkTools.appendChild(options.createLinkToolButton('w-link-edit', 'link-inline-edit', t('editor.edit_link')));
    linkTools.appendChild(options.createLinkToolButton('w-link-unlink', 'link-inline-remove', t('editor.unlink')));

    const nodes = () => ({
        input: linkModal.querySelector('[data-role="link-input"]'),
        textInput: linkModal.querySelector('[data-role="link-text-input"]'),
        targetBlank: linkModal.querySelector('[data-role="link-target-blank"]'),
        noFollow: linkModal.querySelector('[data-role="link-nofollow"]'),
        apply: linkModal.querySelector('[data-role="link-apply"]')
    });

    const unwrap = (linkNode) => {
        if (!linkNode || !linkNode.parentNode) {
            return;
        }
        const parent = linkNode.parentNode;
        while (linkNode.firstChild) {
            parent.insertBefore(linkNode.firstChild, linkNode);
        }
        parent.removeChild(linkNode);
    };

    const applyOptions = (linkNode, targetBlank, noFollow) => {
        if (targetBlank) {
            linkNode.setAttribute('target', '_blank');
        } else {
            linkNode.removeAttribute('target');
        }

        const relTokens = [];
        if (targetBlank) {
            relTokens.push('noopener');
            relTokens.push('noreferrer');
        }
        if (noFollow) {
            relTokens.push('nofollow');
        }
        if (relTokens.length) {
            linkNode.setAttribute('rel', relTokens.join(' '));
            return;
        }
        linkNode.removeAttribute('rel');
    };

    const updateApplyState = () => {
        const fields = nodes();
        if (!fields.apply) {
            return;
        }
        const inputUrl = fields.input ? fields.input.value.trim() : '';
        const existingUrl = activeLink && options.editor.contains(activeLink) ? String(activeLink.getAttribute('href') || '').trim() : '';
        const textValue = fields.textInput ? fields.textInput.value.trim() : '';
        fields.apply.disabled = inputUrl === '' && existingUrl === '' && textValue === '';
    };

    const resetFields = () => {
        const fields = nodes();
        if (fields.input) {
            fields.input.value = '';
        }
        if (fields.textInput) {
            fields.textInput.value = '';
        }
        if (fields.targetBlank) {
            fields.targetBlank.checked = false;
        }
        if (fields.noFollow) {
            fields.noFollow.checked = false;
        }
        updateApplyState();
    };

    const closeModal = () => {
        options.modalUi.close(linkModal);
    };

    const isModalOpen = () => linkModal.classList.contains('open');

    const hideTools = () => {
        linkTools.classList.remove('is-visible');
        linkTools.style.top = '';
        linkTools.style.left = '';
    };

    const showTools = (linkNode) => {
        if (!linkNode || !options.editor.contains(linkNode) || options.htmlMode()) {
            hideTools();
            return;
        }
        const linkRect = linkNode.getBoundingClientRect();
        const editorRect = options.editor.getBoundingClientRect();
        linkTools.style.left = (options.editor.offsetLeft + linkRect.left - editorRect.left + options.editor.scrollLeft) + 'px';
        linkTools.style.top = (options.editor.offsetTop + linkRect.bottom - editorRect.top + options.editor.scrollTop + 6) + 'px';
        linkTools.classList.add('is-visible');
    };

    const openModal = () => {
        const fields = nodes();
        const relValues = (activeLink ? (activeLink.getAttribute('rel') || '') : '').split(/\s+/).filter(Boolean);
        const selectedText = linkRange && !linkRange.collapsed ? linkRange.toString().replace(/\s+/g, ' ').trim() : '';

        options.modalUi.open(linkModal);
        options.wrapper.classList.remove('is-list-open');

        if (fields.input) {
            fields.input.value = activeLink ? (activeLink.getAttribute('href') || '') : '';
            fields.input.focus();
            fields.input.select();
        }
        if (fields.textInput) {
            fields.textInput.value = activeLink ? (activeLink.textContent || '').trim() : selectedText;
        }
        if (fields.targetBlank) {
            fields.targetBlank.checked = !!(activeLink && activeLink.getAttribute('target') === '_blank');
        }
        if (fields.noFollow) {
            fields.noFollow.checked = relValues.indexOf('nofollow') !== -1;
        }
        updateApplyState();
    };

    const closeAll = () => {
        closeModal();
        hideTools();
        activeLink = null;
    };

    const rememberCurrent = () => {
        if (options.isSelectionInside(options.editor)) {
            linkRange = options.rememberSelection();
            activeLink = options.getCurrentLink(options.editor);
        }
    };

    const togglePanel = () => {
        if (isModalOpen()) {
            options.closeMenus();
        } else {
            openModal();
        }
    };

    const linkFromEvent = (event) => {
        const target = options.eventElement(event);
        if (!target || target.closest('.wysiwyg-link-tools')) {
            return null;
        }
        const linkNode = target.closest('a');
        return linkNode && options.editor.contains(linkNode) ? linkNode : null;
    };

    const navigate = (event) => {
        const linkNode = linkFromEvent(event);
        if (!linkNode) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        activeLink = linkNode;
        linkRange = null;
        showTools(linkNode);
    };

    const pasteLinkHtml = (url, id) => '<a href="' + options.escapeHtml(url) + '" data-paste-link-id="' + options.escapeHtml(id) + '">' + options.escapeHtml(url) + '</a>';

    const refreshPastedTitle = (linkId, url) => {
        const endpoint = (options.textarea.dataset.linkTitleEndpoint || '').trim();
        if (!endpoint || typeof options.requestJson !== 'function') {
            return;
        }
        options.requestJson(endpoint + '?url=' + encodeURIComponent(url), {
            credentials: 'same-origin',
            headers: {'Accept': 'application/json'}
        }).then((result) => {
            if (!result || !result.response || !result.response.ok) {
                return null;
            }
            return result.data;
        }).then((payload) => {
            const title = payload && payload.success && payload.data ? String(payload.data.title || '').trim() : '';
            if (!title) {
                return;
            }
            const linkNode = options.editor.querySelector('a[data-paste-link-id="' + linkId + '"]');
            if (!linkNode) {
                return;
            }
            linkNode.textContent = title;
            linkNode.removeAttribute('data-paste-link-id');
            options.sync(options.textarea, options.editor);
        }).catch(() => {});
    };

    const insertPastedLink = (url) => {
        options.ensureSelectionInsideEditor();
        linkPasteSeq += 1;
        const linkId = 'paste-link-' + options.editorId + '-' + linkPasteSeq;
        document.execCommand('insertHTML', false, pasteLinkHtml(url, linkId));
        options.persistEditorState(true);
        refreshPastedTitle(linkId, url);
    };

    const handleModalClick = (event) => {
        const fields = nodes();

        if (event.target.closest('[data-role="link-remove"]')) {
            if (activeLink && options.editor.contains(activeLink)) {
                unwrap(activeLink);
            } else if (linkRange) {
                options.restoreSelection(linkRange, options.editor);
                document.execCommand('unlink', false, null);
            }
            options.persistEditorState(true);
            resetFields();
            activeLink = null;
            options.closeMenus();
            return;
        }

        const apply = event.target.closest('[data-role="link-apply"]');
        if (apply) {
            let url = options.normalizeLinkUrl(fields.input ? fields.input.value : '');
            if (!url && activeLink && options.editor.contains(activeLink)) {
                url = options.normalizeLinkUrl(activeLink.getAttribute('href') || '');
            }
            const textValue = fields.textInput ? fields.textInput.value.trim() : '';
            const withTargetBlank = !!(fields.targetBlank && fields.targetBlank.checked);
            const withNoFollow = !!(fields.noFollow && fields.noFollow.checked);
            if (url) {
                let linkNode = null;
                if (activeLink && options.editor.contains(activeLink) && (!linkRange || linkRange.collapsed)) {
                    activeLink.setAttribute('href', url);
                    linkNode = activeLink;
                } else {
                    options.restoreSelection(linkRange, options.editor);
                    document.execCommand('defaultParagraphSeparator', false, 'p');
                    document.execCommand('createLink', false, url);
                    linkNode = options.getCurrentLink(options.editor);
                }
                if (linkNode && options.editor.contains(linkNode)) {
                    applyOptions(linkNode, withTargetBlank, withNoFollow);
                    if (textValue) {
                        linkNode.textContent = textValue;
                    }
                }
                options.persistEditorState(true);
                resetFields();
                activeLink = null;
            }
            options.closeMenus();
            return;
        }

        if (event.target.closest('[data-role="link-cancel"]')) {
            resetFields();
            activeLink = null;
            options.closeMenus();
        }
    };

    const handleModalInput = (event) => {
        if (event.target && event.target.matches('[data-role="link-input"], [data-role="link-text-input"]')) {
            updateApplyState();
        }
    };

    const handleModalKeydown = (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            resetFields();
            activeLink = null;
            options.closeMenus();
            return;
        }
        if (event.key !== 'Enter' || event.shiftKey || event.ctrlKey || event.altKey || event.metaKey) {
            return;
        }
        event.preventDefault();
        nodes().apply?.click();
    };

    const handleToolsClick = (event) => {
        const editButton = event.target.closest('[data-role="link-inline-edit"]');
        if (editButton) {
            if (activeLink && options.editor.contains(activeLink)) {
                openModal();
            }
            return;
        }
        const removeButton = event.target.closest('[data-role="link-inline-remove"]');
        if (!removeButton || !activeLink || !options.editor.contains(activeLink)) {
            return;
        }
        unwrap(activeLink);
        options.persistEditorState(true);
        hideTools();
        activeLink = null;
    };

    const syncTools = () => {
        if (activeLink && linkTools.classList.contains('is-visible')) {
            showTools(activeLink);
        }
    };

    return {
        linkModal,
        linkTools,
        closeAll,
        handleModalClick,
        handleModalInput,
        handleModalKeydown,
        handleToolsClick,
        hideTools,
        insertPastedLink,
        isModalOpen,
        navigate,
        rememberCurrent,
        syncTools,
        togglePanel,
        updateApplyState,
    };
};

editor.links = { create };
})();
