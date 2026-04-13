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
    const openButtons = form.querySelectorAll('[data-content-ai-open]');

    if (!nameField || !excerptField || !bodyField || !csrfInput || !variantsBox || !regenerateButton || !openButtons.length) {
        return;
    }

    const stripHtml = (value) => String(value || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    const firstWords = (value, limit = 300) => stripHtml(value).split(' ').filter(Boolean).slice(0, limit).join(' ');

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
                    termsField.value = value;
                    if (tagPicker) {
                        tagPicker.dispatchEvent(new CustomEvent('tinycms:tag-picker-set', {
                            bubbles: true,
                            detail: {
                                tags: value.split(',').map((tag) => tag.trim()).filter(Boolean),
                            },
                        }));
                    }
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
        data.set('count', '3');

        const { response, data: result } = await postForm(endpoint, data).catch(() => ({ response: null, data: { message: '' } }));
        regenerateButton.disabled = false;

        if (!response || !response.ok || result?.success !== true) {
            pushFlash('error', String(result?.message || t('content.ai_failed')));
            return;
        }

        const variants = Array.isArray(result?.data?.variants)
            ? result.data.variants.map((item) => stripHtml(String(item || ''))).filter(Boolean).slice(0, 3)
            : [];
        if (!variants.length) {
            pushFlash('warning', t('content.ai_failed'));
            return;
        }

        renderVariants(target, variants);
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const target = String(button.getAttribute('data-content-ai-target') || '').trim();
            const endpoint = String(button.getAttribute('data-content-ai-endpoint') || '').trim();
            if (!target || !endpoint) {
                return;
            }

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
})();
