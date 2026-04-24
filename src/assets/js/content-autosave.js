(function () {
    var t = window.tinycms?.i18n?.t || function () { return ''; };
    var postForm = window.tinycms?.api?.http?.postForm;
    var confirmModal = window.tinycms?.ui?.modal?.confirm || function () { return Promise.resolve(false); };
    if (typeof postForm !== 'function') {
        return;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.querySelector('.content-editor-form');
        if (!form) {
            return;
        }

        var autosaveEndpoint = form.dataset.autosaveEndpoint || '';
        var draftInitEndpoint = form.dataset.draftInitEndpoint || '';
        var editUrlBase = form.dataset.editUrlBase || '';
        var idInput = form.querySelector('[data-content-id-hidden]');
        var nameInput = form.querySelector('input[name="name"]');
        var bodyTextarea = form.querySelector('textarea[name="body"]');
        var thumbnailTrigger = document.querySelector('[data-media-library-open]');
        var previewLink = document.querySelector('[data-content-preview-link]');
        var headerDeleteGroup = document.querySelector('[data-content-delete-group]');
        var headerDeleteButton = document.querySelector('[data-content-action-delete]');
        var firstDraftAutosaveDelay = 30000;
        var autosaveDelay = 60000;
        var saveTimer = null;
        var saving = false;
        var pending = false;
        var lastSent = '';
        var bypassLeaveWarning = false;
        var leaveConfirmOpen = false;
        var editLayoutApplied = false;
        var appRoot = '';
        if (autosaveEndpoint.indexOf('/admin/api/v1/content/autosave') >= 0) {
            appRoot = autosaveEndpoint.replace(/\/admin\/api\/v1\/content\/autosave.*$/, '');
        }

        function applyEditLayoutContext() {
            if (editLayoutApplied) {
                return;
            }
            editLayoutApplied = true;
            var editLabel = window.tinycmsI18n?.admin?.edit_content || '';
            if (editLabel === '') {
                return;
            }

            var titleNode = document.querySelector('[data-admin-page-title]');
            if (titleNode) {
                titleNode.textContent = editLabel;
            }

            var titleSuffix = document.title.indexOf(' | ') >= 0 ? document.title.split(' | ').slice(1).join(' | ') : '';
            document.title = titleSuffix !== '' ? editLabel + ' | ' + titleSuffix : editLabel;
        }

        function contentApi(path) {
            var normalized = path.charAt(0) === '/' ? path : '/' + path;
            return appRoot + normalized;
        }

        function slugify(value) {
            var normalized = String(value || '').trim().toLowerCase();
            if (normalized === '') {
                return '';
            }
            if (typeof normalized.normalize === 'function') {
                normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            }
            normalized = normalized.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            return normalized;
        }

        function updatePreviewLink() {
            if (!previewLink) {
                return;
            }

            var id = contentId();
            if (id <= 0) {
                previewLink.hidden = true;
                previewLink.removeAttribute('href');
                return;
            }

            var base = slugify(nameInput ? nameInput.value : '');
            var path = (base !== '' ? base + '-' : '') + id + '?preview=1';
            previewLink.href = contentApi('/' + path);
            previewLink.target = 'tinycms-content-preview-' + id;
            previewLink.hidden = false;
        }

        function contentId() {
            return Number(idInput ? idInput.value : '0');
        }

        function setContentId(id) {
            var value = Number(id || 0);
            if (!idInput || value <= 0) {
                return;
            }

            idInput.value = String(value);
            form.querySelectorAll('input[name="id"]').forEach(function (node) {
                node.value = String(value);
            });
            form.querySelectorAll('input[name="content_id"]').forEach(function (node) {
                node.value = String(value);
            });

            if (bodyTextarea) {
                bodyTextarea.dataset.contentId = String(value);
                bodyTextarea.dataset.mediaLibraryEndpoint = contentApi('/admin/api/v1/content/' + value + '/media');
            }

            if (thumbnailTrigger) {
                thumbnailTrigger.setAttribute('data-media-library-endpoint', contentApi('/admin/api/v1/content/' + value + '/media'));
                thumbnailTrigger.setAttribute('data-media-select-template', contentApi('/admin/api/v1/content/' + value + '/thumbnail/{mediaId}/select'));
                thumbnailTrigger.setAttribute('data-media-delete-template', contentApi('/admin/api/v1/content/' + value + '/media/{mediaId}/delete'));
                thumbnailTrigger.setAttribute('data-media-rename-template', contentApi('/admin/api/v1/content/' + value + '/media/{mediaId}/rename'));
                thumbnailTrigger.setAttribute('data-media-attach-template', contentApi('/admin/api/v1/content/' + value + '/media/{mediaId}/attach'));
                thumbnailTrigger.setAttribute('data-media-detach-endpoint', contentApi('/admin/api/v1/content/' + value + '/thumbnail/detach'));
            }

            var contentDeleteForm = document.getElementById('content-delete-form');
            if (contentDeleteForm) {
                contentDeleteForm.action = contentApi('/admin/api/v1/content/' + value + '/delete');
            }
            if (headerDeleteButton) {
                headerDeleteButton.setAttribute('data-ui-confirm-form', 'content-delete-form');
            }
            if (headerDeleteGroup) {
                headerDeleteGroup.hidden = false;
            }

            if (editUrlBase !== '') {
                form.action = contentApi('/admin/api/v1/content/' + value + '/edit');
                form.setAttribute('data-stay-on-page', '');
                if (window.location.search.indexOf('id=') === -1) {
                    window.history.replaceState({}, '', editUrlBase + value);
                    applyEditLayoutContext();
                }
            }

            updatePreviewLink();
        }

        function serializePayload() {
            var data = new FormData(form);
            if (contentId() > 0) {
                data.set('id', String(contentId()));
            } else {
                data.delete('id');
            }
            return data;
        }

        function signature(data) {
            var parts = [];
            data.forEach(function (value, key) {
                if (key !== '_csrf') {
                    parts.push(key + ':' + String(value));
                }
            });
            return parts.join('|');
        }

        async function ensureDraft() {
            if (contentId() > 0) {
                return contentId();
            }
            if (!draftInitEndpoint) {
                return 0;
            }

            var draftResult = await postForm(draftInitEndpoint, form);
            var response = draftResult.response;
            var normalized = draftResult.data;
            var id = Number(normalized.data?.id || 0);
            if (!response.ok || !normalized.success || id <= 0) {
                return 0;
            }

            setContentId(id);
            return id;
        }

        async function runAutosave() {
            if (saving || !autosaveEndpoint) {
                pending = true;
                return;
            }

            saving = true;
            pending = false;
            var data = serializePayload();
            var currentSignature = signature(data);
            if (currentSignature === lastSent) {
                saving = false;
                return;
            }

            var autosaveResult = await postForm(autosaveEndpoint, data);
            var response = autosaveResult.response;
            var normalized = autosaveResult.data;
            if (response.ok && normalized.success) {
                lastSent = currentSignature;
                var id = Number(normalized.data?.id || 0);
                if (id > 0) {
                    setContentId(id);
                }
            }

            saving = false;
            if (pending) {
                runAutosave().catch(function () { return null; });
            }
        }

        function scheduleAutosave() {
            if (saveTimer) {
                clearTimeout(saveTimer);
            }
            var delay = contentId() > 0 ? autosaveDelay : firstDraftAutosaveDelay;
            saveTimer = window.setTimeout(function () {
                runAutosave().catch(function () { return null; });
            }, delay);
        }

        function hasUnsavedChanges() {
            return signature(serializePayload()) !== lastSent;
        }

        function confirmLeave() {
            if (leaveConfirmOpen) {
                return Promise.resolve(false);
            }
            leaveConfirmOpen = true;
            return confirmModal({ message: t('content.leave_page_confirm') }).finally(function () {
                leaveConfirmOpen = false;
            });
        }

        function shouldGuardLink(link) {
            if (!link) {
                return false;
            }
            var href = String(link.getAttribute('href') || '').trim();
            if (href === '' || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) {
                return false;
            }
            if (link.hasAttribute('download') || (link.getAttribute('target') || '') === '_blank') {
                return false;
            }
            return true;
        }

        lastSent = signature(serializePayload());
        updatePreviewLink();

        form.addEventListener('input', scheduleAutosave);
        form.addEventListener('change', scheduleAutosave);
        if (nameInput) {
            nameInput.addEventListener('input', updatePreviewLink);
        }
        form.addEventListener('submit', function () {
            bypassLeaveWarning = true;
        });
        window.addEventListener('keydown', function (event) {
            if (bypassLeaveWarning || !hasUnsavedChanges()) {
                return;
            }
            var isReload = event.key === 'F5' || ((event.ctrlKey || event.metaKey) && String(event.key || '').toLowerCase() === 'r');
            if (!isReload) {
                return;
            }
            event.preventDefault();
            confirmLeave().then(function (confirmed) {
                if (!confirmed) {
                    return;
                }
                bypassLeaveWarning = true;
                window.location.reload();
            });
        });

        document.addEventListener('click', function (event) {
            if (bypassLeaveWarning || !hasUnsavedChanges()) {
                return;
            }

            var link = event.target.closest('a[href]');
            if (!shouldGuardLink(link)) {
                return;
            }

            event.preventDefault();
            confirmLeave().then(function (confirmed) {
                if (!confirmed) {
                    return;
                }
                bypassLeaveWarning = true;
                window.location.href = String(link.href || '');
            });
        });

        document.addEventListener('tinycms:content-ensure-draft', function () {
            ensureDraft().then(function (id) {
                document.dispatchEvent(new CustomEvent('tinycms:content-draft-ready', { detail: { id: id } }));
            }).catch(function () {
                document.dispatchEvent(new CustomEvent('tinycms:content-draft-ready', { detail: { id: 0 } }));
            });
        });
    });
})();
