(function () {
    function normalizeHtml(html) {
        return html === '<br>' ? '' : html;
    }

    function extractYoutubeVideoId(value) {
        var raw = String(value || '').trim();
        if (!raw) {
            return null;
        }
        if (/^[a-zA-Z0-9_-]{11}$/.test(raw)) {
            return raw;
        }

        var normalized = raw;
        if (!/^[a-zA-Z][a-zA-Z\d+\-.]*:\/\//.test(normalized)) {
            normalized = 'https://' + normalized.replace(/^\/+/, '');
        }

        var parsed = null;
        try {
            parsed = new URL(normalized);
        } catch (error) {
            return null;
        }

        var host = parsed.hostname.toLowerCase().replace(/^www\./, '').replace(/^m\./, '');
        var id = '';
        if (host === 'youtu.be') {
            id = (parsed.pathname.split('/')[1] || '').trim();
        } else if (host === 'youtube.com' || host === 'youtube-nocookie.com') {
            if (parsed.pathname === '/watch') {
                id = (parsed.searchParams.get('v') || '').trim();
            } else {
                var pathMatch = parsed.pathname.match(/^\/(embed|shorts|live)\/([^/?#]+)/);
                id = pathMatch ? pathMatch[2].trim() : '';
            }
        }

        return /^[a-zA-Z0-9_-]{11}$/.test(id) ? id : null;
    }

    function normalizeLinkUrl(url) {
        var value = String(url || '').trim();
        if (!value) {
            return '';
        }
        if (/^(mailto:|tel:|https?:\/\/|\/|#)/i.test(value)) {
            return value;
        }
        return 'https://' + value.replace(/^\/+/, '');
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function extractPastedUrl(value) {
        var raw = String(value || '').trim();
        if (!raw || /\s/.test(raw)) {
            return '';
        }
        var normalized = normalizeLinkUrl(raw);
        if (!/^https?:\/\//i.test(normalized)) {
            return '';
        }
        try {
            new URL(normalized);
        } catch (error) {
            return '';
        }
        return normalized;
    }

    function findPastedImageFile(event) {
        var clipboard = event.clipboardData;
        if (!clipboard || !clipboard.items) {
            return null;
        }
        for (var i = 0; i < clipboard.items.length; i += 1) {
            var item = clipboard.items[i];
            if (item && item.kind === 'file' && /^image\//i.test(item.type || '')) {
                return item.getAsFile();
            }
        }
        return null;
    }

    window.tinycmsEditorUtils = {
        normalizeHtml: normalizeHtml,
        extractYoutubeVideoId: extractYoutubeVideoId,
        normalizeLinkUrl: normalizeLinkUrl,
        escapeHtml: escapeHtml,
        extractPastedUrl: extractPastedUrl,
        findPastedImageFile: findPastedImageFile,
    };
})();
