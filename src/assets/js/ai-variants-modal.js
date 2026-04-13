(() => {
    const t = window.tinycms?.i18n?.t || (() => '');
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

    const nameField = form.querySelector('[name="name"]');
    const excerptField = form.querySelector('[name="excerpt"]');
    const bodyField = form.querySelector('[name="body"]');
    const termsField = form.querySelector('[data-tag-picker-value]');
    const tagPicker = form.querySelector('[data-tag-picker]');
    const csrfInput = form.querySelector('[name="_csrf"]');
    const variantsBox = modal.querySelector('[data-content-ai-variants]');
    const regenerateButton = modal.querySelector('[data-content-ai-regenerate]');
    const bodyTools = modal.querySelector('[data-content-ai-body-tools]');
    const bodyInstruction = modal.querySelector('[data-content-ai-body-instruction]');
    const bodySubmit = modal.querySelector('[data-content-ai-body-submit]');
    const openButtons = form.querySelectorAll('[data-content-ai-open]');

    if (!nameField || !excerptField || !bodyField || !csrfInput || !variantsBox || !regenerateButton || !openButtons.length || !bodyTools || !bodyInstruction || !bodySubmit) {
        return;
    }

    const stripHtml = (value) => String(value || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    const firstWords = (value, limit = 300) => stripHtml(value).split(' ').filter(Boolean).slice(0, limit).join(' ');
    const parseTags = (value) => String(value || '').split(',').map((tag) => tag.trim()).filter(Boolean);
    let bodyEndpoint = '';

    const renderVariants = (target, items) => {
        variantsBox.innerHTML = items.map((item, index) => `
            <button type="button" class="btn btn-light d-block mb-2" data-content-ai-apply="${index}">${window.tinycms.api.esc(item)}</button>
        `).join('');

        variantsBox.querySelectorAll('[data-content-ai-apply]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const value = items[Number(btn.getAttribute('data-content-ai-apply') || '-1')] || '';
                if (!value) {
                    return;
                }

                if (target === 'name') {
                    nameField.value = value;
                    nameField.dispatchEvent(new Event('input', { bubbles: true }));
                } else if (target === 'excerpt') {
                    excerptField.value = value;
                    excerptField.dispatchEvent(new Event('input', { bubbles: true }));
                } else if (target === 'terms' && termsField) {
                    const current = parseTags(termsField.value);
                    const map = {};
                    current.forEach((tag) => { map[tag.toLowerCase()] = tag; });
                    if (!Object.prototype.hasOwnProperty.call(map, value.toLowerCase())) {
                        current.push(value);
                    }
                    termsField.value = current.join(', ');
                    if (tagPicker) {
                        tagPicker.dispatchEvent(new CustomEvent('tinycms:tag-picker-set', {
                            bubbles: true,
                            detail: { tags: current },
                        }));
                    }
                    pushFlash('success', t('content.ai_generated'));
                    return;
                }

                modal.classList.remove('open');
                pushFlash('success', t('content.ai_generated'));
            });
        });
    };

    const requestVariants = async (target, endpoint) => {
        const source = firstWords(bodyField.value, 300);
        if (!source) {
            pushFlash('warning', t('content.ai_empty_source'));
            return;
        }

        const map = {
            name: 'ai.commands.title',
            excerpt: 'ai.commands.excerpt',
            terms: 'ai.commands.terms',
        };
        const instruction = t(map[target] || '').replace('%s', source);
        if (!instruction) {
            return;
        }

        regenerateButton.disabled = true;
        const data = new FormData();
        data.set('_csrf', String(csrfInput.value || ''));
        data.set('target', target);
        data.set('instruction', instruction);
        data.set('source', source);
        data.set('count', target === 'terms' ? '10' : '3');

        const { response, data: result } = await postForm(endpoint, data).catch(() => ({ response: null, data: { message: '' } }));
        regenerateButton.disabled = false;

        if (!response || !response.ok || result?.success !== true) {
            pushFlash('error', String(result?.message || t('content.ai_failed')));
            return;
        }

        const variants = Array.isArray(result?.data?.variants)
            ? result.data.variants.map((item) => stripHtml(String(item || ''))).filter(Boolean).slice(0, target === 'terms' ? 10 : 3)
            : [];
        if (!variants.length) {
            pushFlash('warning', t('content.ai_failed'));
            return;
        }

        renderVariants(target, variants);
    };

    const replaceEditorBody = (html) => {
        bodyField.value = html;
        bodyField.dispatchEvent(new Event('tinycms:editor-sync-from-textarea', { bubbles: true }));
        bodyField.dispatchEvent(new Event('input', { bubbles: true }));
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const target = String(button.getAttribute('data-content-ai-target') || '').trim();
            const endpoint = String(button.getAttribute('data-content-ai-endpoint') || '').trim();
            if (!target || !endpoint) {
                return;
            }

            if (target === 'body') {
                bodyEndpoint = endpoint;

                bodyTools.hidden = false;
                bodySubmit.hidden = false;
                regenerateButton.hidden = true;
                variantsBox.hidden = true;
                bodyInstruction.value = '';
                modal.classList.add('open');
                return;
            }

            bodyTools.hidden = true;
            bodySubmit.hidden = true;
            regenerateButton.hidden = false;
            variantsBox.hidden = false;
            regenerateButton.setAttribute('data-target', target);
            regenerateButton.setAttribute('data-endpoint', endpoint);
            variantsBox.innerHTML = `<small class="text-muted">${window.tinycms.api.esc(t('common.loading'))}</small>`;
            requestVariants(target, endpoint);
        });
    });

    regenerateButton.addEventListener('click', () => {
        const target = String(regenerateButton.getAttribute('data-target') || '').trim();
        const endpoint = String(regenerateButton.getAttribute('data-endpoint') || '').trim();
        if (!target || !endpoint) {
            return;
        }
        requestVariants(target, endpoint);
    });

    bodySubmit.addEventListener('click', async () => {
        const instruction = String(bodyInstruction.value || '').trim();
        const source = String(bodyField.value || '').trim();
        if (!instruction || !source || !bodyEndpoint) {
            pushFlash('warning', t('content.ai_empty_source'));
            return;
        }

        bodySubmit.disabled = true;
        const data = new FormData();
        data.set('_csrf', String(csrfInput.value || ''));
        data.set('target', 'body');
        data.set('instruction', instruction);
        data.set('source', source);
        data.set('count', '1');

        const { response, data: result } = await postForm(bodyEndpoint, data).catch(() => ({ response: null, data: { message: '' } }));
        bodySubmit.disabled = false;

        if (!response || !response.ok || result?.success !== true) {
            pushFlash('error', String(result?.message || t('content.ai_failed')));
            return;
        }

        const html = String(result?.data?.text || '').trim();
        if (!html) {
            pushFlash('warning', t('content.ai_failed'));
            return;
        }
        replaceEditorBody(html);

        modal.classList.remove('open');
        bodyInstruction.value = '';
        pushFlash('success', t('content.ai_generated'));
    });
})();
