(function () {
    var postForm = window.tinycms?.api?.http?.postForm;
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
        var bodyTextarea = form.querySelector('textarea[name="body"]');
        var thumbnailTrigger = document.querySelector('[data-media-library-open]');
        var headerDeleteGroup = document.querySelector('[data-content-delete-group]');
        var headerDeleteButton = document.querySelector('[data-content-action-delete]');
        var firstDraftAutosaveDelay = 30000;
        var autosaveDelay = 60000;
        var saveTimer = null;
        var saving = false;
        var pending = false;
        var lastSent = '';
        var bypassLeaveWarning = false;
        var pendingNavigation = '';
        var pendingReload = false;
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
            }

            var attachForm = document.querySelector('[data-media-library-attach-form]');
            if (attachForm) {
                attachForm.action = contentApi('/admin/api/v1/content/' + value + '/media/0/attach');
                attachForm.setAttribute('data-action-template', contentApi('/admin/api/v1/content/' + value + '/media/{mediaId}/attach'));
            }

            var deleteForm = document.getElementById('media-library-delete-form');
            if (deleteForm) {
                deleteForm.action = contentApi('/admin/api/v1/content/' + value + '/media/0/delete');
                deleteForm.setAttribute('data-action-template', contentApi('/admin/api/v1/content/' + value + '/media/{mediaId}/delete'));
            }

            var renameForm = document.querySelector('[data-media-library-rename-form]');
            if (renameForm) {
                renameForm.action = contentApi('/admin/api/v1/content/' + value + '/media/0/rename');
                renameForm.setAttribute('data-action-template', contentApi('/admin/api/v1/content/' + value + '/media/{mediaId}/rename'));
            }

            var detachForm = document.querySelector('[data-media-library-detach-form]');
            if (detachForm) {
                detachForm.action = contentApi('/admin/api/v1/content/' + value + '/thumbnail/detach');
            }

            var uploadForm = document.querySelector('[data-media-library-upload-form]');
            if (uploadForm) {
                uploadForm.action = contentApi('/admin/api/v1/content/' + value + '/media/upload');
            }

            var selectForm = document.querySelector('[data-media-library-select-form]');
            if (selectForm) {
                selectForm.action = contentApi('/admin/api/v1/content/' + value + '/thumbnail/0/select');
                selectForm.setAttribute('data-action-template', contentApi('/admin/api/v1/content/' + value + '/thumbnail/{mediaId}/select'));
            }

            var contentDeleteForm = document.getElementById('content-delete-form');
            if (contentDeleteForm) {
                contentDeleteForm.action = contentApi('/admin/api/v1/content/' + value + '/delete');
            }
            if (headerDeleteButton) {
                headerDeleteButton.setAttribute('data-modal-target', '#content-delete-modal');
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

        function leaveModal() {
            return document.querySelector('[data-content-leave-modal]');
        }

        function closeLeaveModal() {
            var modal = leaveModal();
            if (modal) {
                modal.classList.remove('open');
            }
            pendingNavigation = '';
            pendingReload = false;
        }

        function openLeaveModal() {
            var modal = leaveModal();
            if (!modal) {
                return;
            }
            modal.classList.add('open');
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

        form.addEventListener('input', scheduleAutosave);
        form.addEventListener('change', scheduleAutosave);
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
            pendingNavigation = '';
            pendingReload = true;
            openLeaveModal();
        });

        document.addEventListener('click', function (event) {
            var cancelLeave = event.target.closest('[data-content-leave-cancel]');
            if (cancelLeave) {
                event.preventDefault();
                closeLeaveModal();
                return;
            }

            var confirmLeave = event.target.closest('[data-content-leave-confirm]');
            if (confirmLeave) {
                event.preventDefault();
                bypassLeaveWarning = true;
                if (pendingReload) {
                    window.location.reload();
                    return;
                }
                if (pendingNavigation !== '') {
                    window.location.href = pendingNavigation;
                    return;
                }
                closeLeaveModal();
                return;
            }

            if (bypassLeaveWarning || !hasUnsavedChanges()) {
                return;
            }

            var link = event.target.closest('a[href]');
            if (!shouldGuardLink(link)) {
                return;
            }

            event.preventDefault();
            pendingNavigation = String(link.href || '');
            pendingReload = false;
            openLeaveModal();
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
