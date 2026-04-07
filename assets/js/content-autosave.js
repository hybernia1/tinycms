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

    function i18n(path, fallback) {
        var root = window.tinycmsI18n || {};
        var value = path.split('.').reduce(function (acc, key) {
            if (acc && Object.prototype.hasOwnProperty.call(acc, key)) {
                return acc[key];
            }
            return undefined;
        }, root);
        return typeof value === 'string' && value !== '' ? value : fallback;
    }

    function esc(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    var iconSprite = (function () {
        var iconUse = document.querySelector('svg use[href*="#icon-"]');
        return iconUse ? String(iconUse.getAttribute('href') || '').split('#')[0] : '';
    })();

    function icon(name) {
        if (iconSprite === '') {
            return '';
        }
        return '<svg class="icon" aria-hidden="true" focusable="false"><use href="' + esc(iconSprite) + '#icon-' + esc(name) + '"></use></svg>';
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
        var bypassLeaveWarning = false;
        var pendingNavigation = '';
        var pendingReload = false;
        var appRoot = '';
        var allowSuspiciousOnce = false;
        var guardActive = false;
        var safeBodies = [];
        var historyLimit = 10;
        var lastSafeBody = bodyTextarea ? String(bodyTextarea.value || '') : '';

        if (lastSafeBody !== '') {
            safeBodies.push(lastSafeBody);
        }

        if (autosaveEndpoint.indexOf('/admin/api/v1/content/autosave') >= 0) {
            appRoot = autosaveEndpoint.replace(/\/admin\/api\/v1\/content\/autosave.*$/, '');
        }

        function pushSafeBody(value) {
            var body = String(value || '');
            if (safeBodies.length > 0 && safeBodies[safeBodies.length - 1] === body) {
                return;
            }
            safeBodies.push(body);
            if (safeBodies.length > historyLimit) {
                safeBodies = safeBodies.slice(safeBodies.length - historyLimit);
            }
        }

        function latestSafeBody() {
            if (safeBodies.length === 0) {
                return '';
            }
            return safeBodies[safeBodies.length - 1];
        }

        function suspiciousBodyChange(previousBody, currentBody) {
            var prev = String(previousBody || '');
            var curr = String(currentBody || '');
            if (prev === '' || curr === '') {
                return false;
            }

            var drop = prev.length >= 120 && curr.length <= Math.floor(prev.length * 0.4);
            var largeJump = Math.abs(curr.length - prev.length) >= 500;
            var repeated = /([^\s])\1{9,}/.test(curr);
            var symbols = curr.replace(/[\p{L}\p{N}\s.,:;!?()\-_'"/]/gu, '').length;
            var symbolRatio = curr.length > 0 ? symbols / curr.length : 0;
            var heavyNoise = curr.length >= 120 && symbolRatio > 0.35;

            return drop || (largeJump && repeated) || heavyNoise;
        }

        function removeGuardFlash() {
            var old = document.querySelector('[data-cat-keyboard-guard]');
            if (old) {
                old.remove();
            }
        }

        function showGuardFlash() {
            if (guardActive) {
                return;
            }

            var container = document.querySelector('.admin-content');
            if (!container) {
                return;
            }

            removeGuardFlash();
            var flash = document.createElement('div');
            flash.className = 'flash flash-error';
            flash.setAttribute('data-cat-keyboard-guard', '1');
            flash.innerHTML = '<span class="d-flex align-center gap-2">' + icon('cat') + '<span>' + esc(i18n('content.cat_keyboard_warning', 'Detekována neobvyklá změna.')) + '</span></span>'
                + '<div class="d-flex gap-2">'
                + '<button type="button" class="btn btn-light" data-cat-keyboard-restore="1">' + esc(i18n('content.cat_keyboard_restore', 'Obnovit bezpečnou verzi')) + '</button>'
                + '<button type="button" class="btn btn-light" data-cat-keyboard-continue="1">' + esc(i18n('content.cat_keyboard_continue', 'Pokračovat a uložit')) + '</button>'
                + '</div>';
            container.prepend(flash);
            guardActive = true;
        }

        function restoreSafeBody() {
            if (!bodyTextarea) {
                return;
            }

            var safeBody = latestSafeBody();
            bodyTextarea.value = safeBody;
            bodyTextarea.dispatchEvent(new Event('tinycms:editor-sync-from-textarea', { bubbles: true }));
            bodyTextarea.dispatchEvent(new Event('input', { bubbles: true }));
            removeGuardFlash();
            guardActive = false;
            allowSuspiciousOnce = false;
        }

        function continueSuspiciousSave() {
            allowSuspiciousOnce = true;
            guardActive = false;
            removeGuardFlash();
            runAutosave().catch(function () { return null; });
        }

        document.addEventListener('click', function (event) {
            var restoreButton = event.target.closest('[data-cat-keyboard-restore]');
            if (restoreButton) {
                restoreSafeBody();
                return;
            }

            var continueButton = event.target.closest('[data-cat-keyboard-continue]');
            if (continueButton) {
                continueSuspiciousSave();
            }
        });

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

        function tagsValid() {
            var invalidTagPicker = form.querySelector('[data-tag-picker][data-tag-picker-valid="0"]');
            return invalidTagPicker === null;
        }

        async function runAutosave() {
            if (saving || !autosaveEndpoint) {
                pending = true;
                return;
            }

            var currentBody = bodyTextarea ? String(bodyTextarea.value || '') : '';
            if (!allowSuspiciousOnce && suspiciousBodyChange(lastSafeBody, currentBody)) {
                showGuardFlash();
                pending = false;
                return;
            }

            allowSuspiciousOnce = false;
            saving = true;
            pending = false;
            if (!tagsValid()) {
                saving = false;
                return;
            }

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
                if (bodyTextarea) {
                    lastSafeBody = String(bodyTextarea.value || '');
                    pushSafeBody(lastSafeBody);
                }
                guardActive = false;
                removeGuardFlash();
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
        form.addEventListener('tinycms:tag-picker-change', function (event) {
            if (event.detail && event.detail.valid === false) {
                return;
            }
            if (contentId() <= 0) {
                return;
            }
            scheduleAutosave();
        });
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
