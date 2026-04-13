(() => {
    const postForm = window.tinycms?.api?.http?.postForm;
    const pushFlash = window.tinycms?.api?.pushFlash || (() => {});
    if (typeof postForm !== 'function') {
        return;
    }

    const form = document.querySelector('#content-editor-form');
    const modal = document.querySelector('#content-ai-modal');
    if (!form || !modal) {
        return;
    }

    const openButtons = document.querySelectorAll('[data-content-ai-open]');
    const instructionInput = modal.querySelector('[data-content-ai-instruction]');
    const generateButton = modal.querySelector('[data-content-ai-generate]');
    const targetLabel = modal.querySelector('[data-content-ai-modal-target-label]');
    const excerptField = form.querySelector('[name="excerpt"]');
    const bodyField = form.querySelector('[name="body"]');
    const csrfInput = form.querySelector('[name="_csrf"]');

    if (!openButtons.length || !instructionInput || !generateButton || !targetLabel || !excerptField || !bodyField || !csrfInput) {
        return;
    }

    const labels = {
        excerpt: String(window.tinycms?.i18n?.t?.('content.ai_target_excerpt') || 'Excerpt'),
        body: String(window.tinycms?.i18n?.t?.('content.ai_target_body') || 'Content'),
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const target = String(button.getAttribute('data-content-ai-target') || 'excerpt').trim();
            generateButton.setAttribute('data-target', target === 'body' ? 'body' : 'excerpt');
            targetLabel.textContent = target === 'body' ? labels.body : labels.excerpt;
        });
    });

    const applyBody = (value) => {
        bodyField.value = value;
        bodyField.dispatchEvent(new Event('tinycms:editor-sync-from-textarea', { bubbles: true }));
        bodyField.dispatchEvent(new Event('input', { bubbles: true }));
    };

    generateButton.addEventListener('click', async () => {
        const endpoint = String(generateButton.getAttribute('data-endpoint') || '').trim();
        const target = String(generateButton.getAttribute('data-target') || 'excerpt').trim();
        const instruction = String(instructionInput.value || '').trim();

        if (!endpoint || instruction === '') {
            return;
        }

        generateButton.disabled = true;

        const data = new FormData();
        data.set('_csrf', String(csrfInput.value || ''));
        data.set('instruction', instruction);
        data.set('target', target === 'body' ? 'body' : 'excerpt');
        data.set('excerpt', String(excerptField.value || ''));
        data.set('body', String(bodyField.value || ''));

        const { response, data: result } = await postForm(endpoint, data).catch(() => ({ response: null, data: { message: '' } }));
        generateButton.disabled = false;

        if (!response || !response.ok || result?.success !== true) {
            pushFlash('error', String(result?.message || 'AI request failed.'));
            return;
        }

        const generatedText = String(result?.data?.text || '').trim();
        if (generatedText === '') {
            pushFlash('warning', 'AI response is empty.');
            return;
        }

        if (target === 'body') {
            applyBody(generatedText);
        } else {
            excerptField.value = generatedText;
            excerptField.dispatchEvent(new Event('input', { bubbles: true }));
        }

        modal.classList.remove('open');
        instructionInput.value = '';
        pushFlash('success', 'AI content generated.');
    });
})();
