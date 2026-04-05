(function () {
    var body = document.body;
    if (!body) {
        return;
    }

    function setState(button, active) {
        body.classList.toggle('admin-focus-mode', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        var label = button.querySelector('[data-content-focus-label]');
        if (label) {
            label.textContent = active ? 'Ukončit nerušené psaní' : 'Nerušené psaní';
        }
    }

    function init(button) {
        setState(button, false);

        button.addEventListener('click', function () {
            setState(button, !body.classList.contains('admin-focus-mode'));
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && body.classList.contains('admin-focus-mode')) {
                setState(button, false);
            }
        });
    }

    var focusToggle = document.querySelector('[data-content-focus-toggle]');
    if (focusToggle) {
        init(focusToggle);
    }
}());
