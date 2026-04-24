(() => {
const t = window.tinycms?.i18n?.t || (() => '');
const icon = window.tinycms?.icons?.icon || (() => '');
const esc = window.tinycms?.api?.esc || ((value) => String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;'));

const currentCsrf = () => document.querySelector('input[name="_csrf"]')?.value || '';

const uploadAction = (openTrigger) => {
    const endpoint = openTrigger?.getAttribute('data-media-library-endpoint') || '';
    return endpoint.replace(/\/media(?:\?.*)?$/, '/media/upload');
};

const createModal = (openTrigger) => {
    if (!openTrigger) {
        return null;
    }

    const uploadId = 'content-thumbnail-upload';
    const uploadTypes = openTrigger.getAttribute('data-media-upload-types-label') || '';
    const allowedTypes = uploadTypes !== ''
        ? t('common.allowed_upload_types').replace('%s', uploadTypes)
        : '';
    const node = document.createElement('div');
    node.className = 'media-library-modal modal-overlay';
    node.setAttribute('data-media-library-modal', '');
    node.setAttribute('data-media-library-per-page', openTrigger.getAttribute('data-media-library-per-page') || '10');
    node.innerHTML = `
        <div class="media-library-modal-dialog modal">
            <div class="media-library-modal-header">
                <strong>${esc(t('admin.menu.media'))}</strong>
                <button class="btn btn-light btn-icon" type="button" data-ui-modal-close aria-label="${esc(t('common.close'))}">
                    ${icon('cancel')}
                </button>
            </div>
            <div class="media-library-modal-layout">
                <div class="media-library-detail">
                    <div class="media-library-detail-preview" data-media-library-detail-preview></div>
                    <div class="media-library-detail-meta">
                        <div>
                            <label>${esc(t('common.name'))}</label>
                            <div class="d-flex gap-2">
                                <input type="text" value="" data-media-library-detail-name-input>
                                <button class="btn btn-light" type="button" data-media-library-rename disabled>${esc(t('common.save'))}</button>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted" data-media-library-status></small>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="button" data-media-library-choose disabled>${esc(t('content.choose'))}</button>
                        <button class="btn btn-danger" type="button" data-media-library-delete-open disabled>${esc(t('common.delete'))}</button>
                    </div>
                </div>
                <div class="media-library-list">
                    <form class="media-library-search" data-media-library-search>
                        <div class="search-field field-with-icon">
                            <input class="search-input" type="search" name="q" placeholder="${esc(t('content.search_image'))}">
                            <span class="field-overlay field-overlay-end field-icon field-icon-soft" aria-hidden="true">${icon('search')}</span>
                        </div>
                    </form>
                    <form class="media-library-upload" method="post" enctype="multipart/form-data" action="${esc(uploadAction(openTrigger))}" data-media-library-upload-form>
                        <input type="hidden" name="_csrf" value="${esc(currentCsrf())}">
                        <input type="hidden" name="content_id" value="">
                        <div class="custom-upload-field" data-media-library-upload-field>
                            <label class="btn btn-light custom-upload-button" for="${uploadId}">
                                <span class="custom-upload-main-icon" data-custom-upload-icon>${icon('upload')}</span>
                                <span class="custom-upload-label" data-custom-upload-label data-default-label="${esc(t('common.upload_add_files'))}">${esc(t('common.upload_add_files'))}</span>
                                <span class="custom-upload-spinner" data-custom-upload-spinner aria-hidden="true">${icon('loader')}</span>
                            </label>
                            <input id="${uploadId}" type="file" name="thumbnail" accept="${esc(openTrigger.getAttribute('data-media-upload-accept') || '')}" required>
                        </div>
                        ${allowedTypes !== '' ? `<small class="text-muted d-block mt-2">${esc(allowedTypes)}</small>` : ''}
                    </form>
                    <div class="media-library-grid" data-media-library-grid></div>
                    <div class="pagination pagination-centered">
                        <a class="pagination-link disabled" href="#" data-media-library-prev aria-disabled="true" tabindex="-1">${icon('prev')}<span>${esc(t('common.previous'))}</span></a>
                        <a class="pagination-link disabled" href="#" data-media-library-next aria-disabled="true" tabindex="-1"><span>${esc(t('common.next'))}</span>${icon('next')}</a>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(node);
    return node;
};

window.tinycms = window.tinycms || {};
window.tinycms.mediaLibrary = window.tinycms.mediaLibrary || {};
window.tinycms.mediaLibrary.template = {
    createModal,
    currentCsrf,
};
})();
