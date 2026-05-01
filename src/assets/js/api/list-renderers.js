(() => {
    const app = window.tinycms = window.tinycms || {};
    const api = app.api = app.api || {};
    const t = app.i18n?.t || (() => '');
    const esc = app.support?.esc || ((value) => String(value || ''));
    const icon = app.icons?.icon || (() => '');

    const contentRowHtml = (item, { editBase }) => {
        const status = String(item.status || 'draft');
        const isTrash = status === 'trash';
        const isPublished = status === 'published';
        const isPlanned = item.is_planned === true;
        const statusIcon = isPlanned ? 'calendar' : (status === 'published' ? 'success' : (status === 'draft' ? 'concept' : 'warning'));
        const toggleLabel = isPublished ? t('content.switch_to_draft') : t('content.publish');
        const canEdit = item.can_edit === true;
        const canDelete = item.can_delete === true;
        const canRestore = item.can_restore === true;

        return `
            <tr>
                <td>
                    <span class="d-flex align-center gap-2">
                        ${statusIcon !== '' ? icon(statusIcon) : ''}
                        ${canEdit
        ? `<a class="admin-list-truncate" href="${esc(editBase)}${Number(item.id || 0)}" title="${esc(item.name)}">${esc(item.name)}</a>`
        : `<span class="admin-list-truncate" title="${esc(item.name)}">${esc(item.name)}</span>`}
                    </span>
                    <div class="text-muted small">${esc(item.created_label || item.created)}</div>
                </td>
                <td class="mobile-hide">${esc(item.author_name || '-')}</td>
                <td class="table-col-actions">
                    ${canEdit && !isTrash ? `
                    <button class="btn btn-light btn-icon" type="button" data-content-toggle="${Number(item.id || 0)}" data-content-mode="${isPublished ? 'draft' : 'publish'}" aria-label="${esc(toggleLabel)}" title="${esc(toggleLabel)}">
                        ${icon(isPublished ? 'hide' : 'show')}
                        <span class="sr-only">${esc(toggleLabel)}</span>
                    </button>
                    ` : ''}
                    ${canRestore ? `
                    <button class="btn btn-light btn-icon" type="button" data-content-restore="${Number(item.id || 0)}" aria-label="${esc(t('content.restore'))}" title="${esc(t('content.restore'))}">
                        ${icon('restore')}
                        <span class="sr-only">${esc(t('content.restore'))}</span>
                    </button>
                    ` : ''}
                    ${canDelete ? `
                    <button class="btn btn-light btn-icon" type="button" data-content-delete-open="${Number(item.id || 0)}" data-content-delete-mode="${canRestore ? 'hard' : 'soft'}" aria-label="${esc(t('common.delete'))}" title="${esc(t('common.delete'))}">
                        ${icon('delete')}
                        <span class="sr-only">${esc(t('common.delete'))}</span>
                    </button>
                    ` : ''}
                </td>
            </tr>
        `;
    };

    const termsRowHtml = (item, { editBase }) => {
        const id = Number(item.id || 0);

        return `
            <tr>
                <td>
                    <a href="${esc(editBase)}${id}">${esc(item.name)}</a>
                    <div class="text-muted small">${esc(item.created_label || item.created)}</div>
                </td>
                <td class="table-col-actions">
                    <button class="btn btn-light btn-icon" type="button" data-terms-delete-open="${id}" aria-label="${esc(t('terms.delete'))}" title="${esc(t('terms.delete'))}">
                        ${icon('delete')}
                        <span class="sr-only">${esc(t('terms.delete'))}</span>
                    </button>
                </td>
            </tr>
        `;
    };

    const commentsRowHtml = (item, { editBase, context }) => {
        const id = Number(item.id || 0);
        const contentId = Number(item.content || 0);
        const repliesCount = Number(item.replies_count || 0);
        const status = String(item.status || 'draft');
        const isTrash = status === 'trash';
        const isPublished = status === 'published';
        const statusIcon = status === 'published' ? 'success' : (status === 'draft' ? 'concept' : 'warning');
        const canEdit = item.can_edit === true;
        const canDelete = item.can_delete === true;
        const canRestore = item.can_restore === true;
        const toggleLabel = isPublished ? t('comments.switch_to_draft') : t('comments.publish');
        const author = String(item.author_name || item.author_email || t('common.no_author') || '-');
        const body = String(item.body || t('comments.empty_body') || '');
        const contentEditBase = String(context.contentEditBase || '');

        return `
            <tr>
                <td>
                    <span class="d-flex align-center gap-2">
                        ${icon(statusIcon)}
                        ${canEdit
        ? `<a class="admin-list-truncate" href="${esc(editBase)}${id}" title="${esc(body)}">${esc(body)}</a>`
        : `<span class="admin-list-truncate" title="${esc(body)}">${esc(body)}</span>`}
                    </span>
                    <div class="text-muted small">
                        ${contentId > 0 && contentEditBase !== '' ? `<a href="${esc(contentEditBase)}${contentId}">${esc(item.content_name || '#' + contentId)}</a>` : '<span>-</span>'}
                    </div>
                    <div class="text-muted small">${esc(item.created_label || item.created)} - ${esc(t('comments.statuses.' + status) || status)} - ${Number.isFinite(repliesCount) ? repliesCount : 0} ${esc(t('comments.replies'))}</div>
                </td>
                <td class="mobile-hide">${esc(author)}</td>
                <td class="table-col-actions">
                    ${canEdit && !isTrash ? `
                    <button class="btn btn-light btn-icon" type="button" data-comments-toggle="${id}" data-comments-mode="${isPublished ? 'draft' : 'publish'}" aria-label="${esc(toggleLabel)}" title="${esc(toggleLabel)}">
                        ${icon(isPublished ? 'hide' : 'show')}
                        <span class="sr-only">${esc(toggleLabel)}</span>
                    </button>
                    ` : ''}
                    ${canRestore ? `
                    <button class="btn btn-light btn-icon" type="button" data-comments-restore="${id}" aria-label="${esc(t('comments.restore'))}" title="${esc(t('comments.restore'))}">
                        ${icon('restore')}
                        <span class="sr-only">${esc(t('comments.restore'))}</span>
                    </button>
                    ` : ''}
                    ${canDelete ? `
                    <button class="btn btn-light btn-icon" type="button" data-comments-delete-open="${id}" data-comments-delete-mode="${canRestore ? 'hard' : 'soft'}" aria-label="${esc(t('comments.delete'))}" title="${esc(t('comments.delete'))}">
                        ${icon('delete')}
                        <span class="sr-only">${esc(t('comments.delete'))}</span>
                    </button>
                    ` : ''}
                </td>
            </tr>
        `;
    };

    const mediaRowHtml = (item, { editBase }) => {
        const id = Number(item.id || 0);
        const img = String(item.preview_path || '');
        const canEdit = item.can_edit === true;
        const canDelete = item.can_delete === true;

        return `
            <tr>
                <td>
                    <div class="d-flex align-center gap-2">
                        ${img !== ''
        ? `<div class="media-list-thumb"><img src="${esc(img)}" alt="${esc(item.name)}"></div>`
        : '<div class="media-list-thumb media-list-thumb-empty"></div>'}
                        <div>
                            ${canEdit ? `<a class="admin-list-truncate" href="${esc(editBase)}${id}" title="${esc(item.name)}">${esc(item.name)}</a>` : `<span class="admin-list-truncate" title="${esc(item.name)}">${esc(item.name)}</span>`}
                            <div class="text-muted small admin-list-truncate" title="${esc(item.path)}">${esc(item.path)}</div>
                            <div class="text-muted small">${esc(item.created_label || item.created)}</div>
                        </div>
                    </div>
                </td>
                <td class="mobile-hide">${esc(item.author_name || '-')}</td>
                <td class="table-col-actions">
                    ${canDelete ? `
                    <button class="btn btn-light btn-icon" type="button" data-media-delete-open="${id}" aria-label="${esc(t('media.delete'))}" title="${esc(t('media.delete'))}">
                        ${icon('delete')}
                        <span class="sr-only">${esc(t('media.delete'))}</span>
                    </button>
                    ` : ''}
                </td>
            </tr>
        `;
    };

    const usersRowHtml = (item, { editBase }) => {
        const id = Number(item.id || 0);
        const isSuspended = item.is_suspended === true;
        const isAdmin = item.is_admin === true;
        const statusIcon = isSuspended ? 'suspended' : (isAdmin ? 'admin' : 'users');
        const toggleLabel = isSuspended ? t('users.unsuspend') : t('users.suspend');

        return `
            <tr>
                <td>
                    <span class="d-flex align-center gap-2">
                        ${icon(statusIcon)}
                        <a href="${esc(editBase)}${id}">${esc(item.name)}</a>
                    </span>
                    <div class="text-muted small">${esc(item.email)}</div>
                </td>
                <td class="table-col-actions">
                    ${isAdmin ? '' : `
                    <button class="btn btn-light btn-icon" type="button" data-users-toggle="${id}" data-users-mode="${isSuspended ? 'unsuspend' : 'suspend'}" aria-label="${esc(toggleLabel)}" title="${esc(toggleLabel)}">
                        ${icon(isSuspended ? 'show' : 'hide')}
                        <span class="sr-only">${esc(toggleLabel)}</span>
                    </button>
                    <button class="btn btn-light btn-icon" type="button" data-users-delete-open="${id}" aria-label="${esc(t('users.delete'))}" title="${esc(t('users.delete'))}">
                        ${icon('delete')}
                        <span class="sr-only">${esc(t('users.delete'))}</span>
                    </button>`}
                </td>
            </tr>
        `;
    };

    api.listRenderers = {
        contentRowHtml,
        commentsRowHtml,
        mediaRowHtml,
        termsRowHtml,
        usersRowHtml,
    };
})();
