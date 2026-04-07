(function () {
    function iconSpritePath() {
        var iconUse = document.querySelector('svg use[href*="#icon-"]');
        if (!iconUse) {
            return '/assets/icons.svg';
        }
        return String(iconUse.getAttribute('href') || '').split('#')[0] || '/assets/icons.svg';
    }

    function createOverlay() {
        var overlay = document.createElement('div');
        overlay.className = 'admin-global-loader';
        overlay.innerHTML = '<svg class="icon" aria-hidden="true"><use href="' + iconSpritePath() + '#icon-loader"></use></svg>';
        document.body.appendChild(overlay);
        return overlay;
    }

    var overlay = null;
    var pageCounter = 0;
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
        pageCounter += 1;
        ensureOverlay().classList.add('is-visible');
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

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!shouldHandleLink(link)) {
            return;
        }
        startPage();
    });

    document.addEventListener('submit', function (event) {
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
