(function () {
    function normalizePayload(payload) {
        if (payload && Object.prototype.hasOwnProperty.call(payload, 'ok')) {
            return {
                success: payload.ok === true,
                data: payload.data || {},
            };
        }

        return {
            success: payload?.success === true,
            data: payload?.data || {},
        };
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
        var saveTimer = null;
        var saving = false;
        var pending = false;
        var lastSent = '';
        var appRoot = '';
        if (autosaveEndpoint.indexOf('/admin/api/v1/content/autosave') >= 0) {
            appRoot = autosaveEndpoint.replace(/\/admin\/api\/v1\/content\/autosave.*$/, '');
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

            if (editUrlBase !== '') {
                form.action = editUrlBase + value;
                if (window.location.search.indexOf('id=') === -1) {
                    window.history.replaceState({}, '', editUrlBase + value);
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

            var response = await fetch(draftInitEndpoint, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
            });
            var normalized = normalizePayload(await response.json().catch(function () { return {}; }));
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

            var response = await fetch(autosaveEndpoint, {
                method: 'POST',
                body: data,
                headers: { Accept: 'application/json' },
            });
            var normalized = normalizePayload(await response.json().catch(function () { return {}; }));
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
            saveTimer = window.setTimeout(function () {
                runAutosave().catch(function () { return null; });
            }, 1200);
        }

        form.addEventListener('input', scheduleAutosave);
        form.addEventListener('change', scheduleAutosave);

        document.addEventListener('tinycms:content-ensure-draft', function () {
            ensureDraft().then(function (id) {
                document.dispatchEvent(new CustomEvent('tinycms:content-draft-ready', { detail: { id: id } }));
            }).catch(function () {
                document.dispatchEvent(new CustomEvent('tinycms:content-draft-ready', { detail: { id: 0 } }));
            });
        });
    });
})();
