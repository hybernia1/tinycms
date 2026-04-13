(function () {
    function iconSpritePath() {
        var iconUse = document.querySelector('svg use[href*="#icon-"]');
        if (!iconUse) {
            return '/src/assets/svg/icons.svg';
        }
        return String(iconUse.getAttribute('href') || '').split('#')[0] || '/src/assets/svg/icons.svg';
    }

    function createOverlay() {
        var scope = document.querySelector('.admin-content');
        var overlay = document.createElement('div');
        overlay.className = 'admin-global-loader';
        if (!scope) {
            overlay.classList.add('is-fixed');
        }
        overlay.innerHTML = '<svg class="icon" aria-hidden="true"><use href="' + iconSpritePath() + '#icon-loader"></use></svg>';
        (scope || document.body).appendChild(overlay);
        return overlay;
    }

    var overlay = null;
    var pageCounter = 0;
    var pageLeaveStarted = false;
    var pageRequestId = 0;
    function ensureOverlay() {
        if (!overlay) {
            overlay = createOverlay();
        }
        return overlay;
    }

    function setTargetLoading(target, active) {
        if (!target || !target.classList) {
            return;
        }
        target.classList.toggle('is-loading', active);
    }

    function startPage() {
        pageRequestId += 1;
        var requestId = pageRequestId;
        pageCounter += 1;
        ensureOverlay().classList.add('is-visible');
        window.setTimeout(function () {
            if (requestId !== pageRequestId || pageLeaveStarted) {
                return;
            }
            pageCounter = 0;
            ensureOverlay().classList.remove('is-visible');
        }, 900);
    }

    function stopPage() {
        pageCounter = Math.max(0, pageCounter - 1);
        if (pageCounter === 0) {
            ensureOverlay().classList.remove('is-visible');
        }
    }

    function shouldHandleLink(link) {
        if (!link || link.target === '_blank' || link.hasAttribute('download') || link.hasAttribute('data-no-loader')) {
            return false;
        }
        var href = String(link.getAttribute('href') || '').trim();
        if (!href || href.charAt(0) === '#' || /^mailto:|^tel:|^javascript:/i.test(href)) {
            return false;
        }
        return link.origin === window.location.origin;
    }

    window.addEventListener('pagehide', function () {
        pageLeaveStarted = true;
    });

    window.addEventListener('pageshow', function () {
        pageLeaveStarted = false;
        pageCounter = 0;
        if (overlay) {
            overlay.classList.remove('is-visible');
        }
    });

    document.addEventListener('click', function (event) {
        if (event.defaultPrevented) {
            return;
        }
        var link = event.target.closest('a[href]');
        if (!shouldHandleLink(link)) {
            return;
        }
        startPage();
    });

    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented) {
            return;
        }
        var form = event.target;
        if (!form || form.hasAttribute('data-no-loader')) {
            return;
        }
        startPage();
    });

    window.tinycmsLoader = {
        set: setTargetLoading,
        startPage: startPage,
        stopPage: stopPage
    };
})();
