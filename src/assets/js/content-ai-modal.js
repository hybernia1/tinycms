(() => {
    const t = window.tinycms?.i18n?.t || (() => '');
    const postForm = window.tinycms?.api?.http?.postForm;
    const pushFlash = window.tinycms?.api?.pushFlash || (() => {});
    if (typeof postForm !== 'function') {
        return;
    }

    const form = document.querySelector('#content-editor-form');
    if (!form) {
        return;
    }

    const nameField = form.querySelector('[name="name"]');
    const excerptField = form.querySelector('[name="excerpt"]');
    const bodyField = form.querySelector('[name="body"]');
    const csrfInput = form.querySelector('[name="_csrf"]');
    if (!nameField || !excerptField || !bodyField || !csrfInput) {
        return;
    }

    const stripHtml = (value) => String(value || '')
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/gi, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    const firstWords = (value, limit = 300) => {
        const words = stripHtml(value).split(' ').filter(Boolean);
        return words.slice(0, Math.max(1, limit)).join(' ');
    };

    const applyBody = (value) => {
        bodyField.value = value;
        bodyField.dispatchEvent(new Event('tinycms:editor-sync-from-textarea', { bubbles: true }));
        bodyField.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const requestGenerate = async (endpoint, target, instruction, source) => {
        const data = new FormData();
        data.set('_csrf', String(csrfInput.value || ''));
        data.set('target', target);
        data.set('instruction', instruction);
        data.set('source', source);
        data.set('excerpt', String(excerptField.value || ''));
        data.set('body', String(bodyField.value || ''));

        return postForm(endpoint, data).catch(() => ({ response: null, data: { message: '' } }));
    };

    const handleActionButton = (button) => {
        button.addEventListener('click', async () => {
            const endpoint = String(button.getAttribute('data-content-ai-endpoint') || '').trim();
            const target = String(button.getAttribute('data-content-ai-action') || '').trim();
            if (!endpoint || (target !== 'name' && target !== 'excerpt')) {
                return;
            }

            const source = firstWords(bodyField.value, 300);
            if (source === '') {
                pushFlash('warning', t('content.ai_empty_source'));
                return;
            }

            const commandPath = target === 'name' ? 'ai.commands.title' : 'ai.commands.excerpt';
            const instructionTemplate = t(commandPath);
            const instruction = instructionTemplate.replace('%s', source);
            if (!instruction) {
                return;
            }

            button.disabled = true;
            const { response, data: result } = await requestGenerate(endpoint, target, instruction, source);
            button.disabled = false;

            if (!response || !response.ok || result?.success !== true) {
                pushFlash('error', String(result?.message || t('content.ai_failed')));
                return;
            }

            const generatedText = stripHtml(String(result?.data?.text || ''));
            if (!generatedText) {
                pushFlash('warning', t('content.ai_failed'));
                return;
            }

            if (target === 'name') {
                nameField.value = generatedText;
                nameField.dispatchEvent(new Event('input', { bubbles: true }));
            } else {
                excerptField.value = generatedText;
                excerptField.dispatchEvent(new Event('input', { bubbles: true }));
            }

            pushFlash('success', t('content.ai_generated'));
        });
    };

    form.querySelectorAll('[data-content-ai-action]').forEach(handleActionButton);

    const modal = document.querySelector('#content-ai-modal');
    const instructionInput = modal ? modal.querySelector('[data-content-ai-instruction]') : null;
    const generateButton = modal ? modal.querySelector('[data-content-ai-generate]') : null;
    if (!modal || !instructionInput || !generateButton) {
        return;
    }

    generateButton.addEventListener('click', async () => {
        const endpoint = String(generateButton.getAttribute('data-endpoint') || '').trim();
        const instruction = String(instructionInput.value || '').trim();
        if (!endpoint || instruction === '') {
            return;
        }

        generateButton.disabled = true;
        const { response, data: result } = await requestGenerate(endpoint, 'body', instruction, String(bodyField.value || ''));
        generateButton.disabled = false;

        if (!response || !response.ok || result?.success !== true) {
            pushFlash('error', String(result?.message || t('content.ai_failed')));
            return;
        }

        const generatedText = String(result?.data?.text || '').trim();
        if (generatedText === '') {
            pushFlash('warning', t('content.ai_failed'));
            return;
        }

        applyBody(generatedText);
        modal.classList.remove('open');
        instructionInput.value = '';
        pushFlash('success', t('content.ai_generated'));
    });
})();
