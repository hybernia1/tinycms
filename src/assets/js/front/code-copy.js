(() => {
    const copyText = async (text) => {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        textarea.remove();
    };

    document.querySelectorAll('pre.code-block').forEach((block) => {
        if (block.querySelector('.code-copy-button')) {
            return;
        }

        const code = block.querySelector('code');
        if (!code) {
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'code-copy-button';
        button.textContent = 'Copy';
        button.setAttribute('aria-label', 'Copy');

        button.addEventListener('click', async () => {
            try {
                await copyText(code.textContent || '');
                button.textContent = 'Copied';
                window.setTimeout(() => {
                    button.textContent = 'Copy';
                }, 1400);
            } catch (error) {
                button.textContent = 'Copy';
            }
        });

        block.appendChild(button);
    });
})();
