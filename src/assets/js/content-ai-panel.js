(() => {
    const postForm = window.tinycms?.api?.http?.postForm;
    const pushFlash = window.tinycms?.api?.pushFlash || (() => {});
    if (typeof postForm !== 'function') {
        return;
    }

    const panel = document.querySelector('[data-content-ai-panel]');
    const form = document.querySelector('#content-editor-form');
    if (!panel || !form) {
        return;
    }

    const endpoint = String(panel.getAttribute('data-endpoint') || '').trim();
    const instructionInput = panel.querySelector('[data-content-ai-instruction]');
    const targetInput = panel.querySelector('[data-content-ai-target]');
    const button = panel.querySelector('[data-content-ai-generate]');
    const excerptField = form.querySelector('[name="excerpt"]');
    const bodyField = form.querySelector('[name="body"]');
    const csrfInput = form.querySelector('[name="_csrf"]');

    if (!endpoint || !instructionInput || !targetInput || !button || !excerptField || !bodyField || !csrfInput) {
        return;
    }

    const applyBody = (value) => {
        bodyField.value = value;
        bodyField.dispatchEvent(new Event('tinycms:editor-sync-from-textarea', { bubbles: true }));
        bodyField.dispatchEvent(new Event('input', { bubbles: true }));
    };

    button.addEventListener('click', async () => {
        const instruction = String(instructionInput.value || '').trim();
        const target = String(targetInput.value || '').trim();
        if (!instruction) {
            return;
        }

        button.disabled = true;
        const data = new FormData();
        data.set('_csrf', String(csrfInput.value || ''));
        data.set('instruction', instruction);
        data.set('target', target);
        data.set('excerpt', String(excerptField.value || ''));
        data.set('body', String(bodyField.value || ''));

        const { response, data: result } = await postForm(endpoint, data).catch(() => ({ response: null, data: { message: '' } }));
        button.disabled = false;

        if (!response || !response.ok || result?.success !== true) {
            pushFlash('error', String(result?.message || 'AI request failed.'));
            return;
        }

        const generatedText = String(result?.data?.text || '').trim();
        if (!generatedText) {
            pushFlash('warning', 'AI response is empty.');
            return;
        }

        if (target === 'excerpt') {
            excerptField.value = generatedText;
            excerptField.dispatchEvent(new Event('input', { bubbles: true }));
        } else {
            applyBody(generatedText);
        }

        pushFlash('success', 'AI content generated.');
    });
})();
