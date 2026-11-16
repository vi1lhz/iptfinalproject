// LootVault – small progressive enhancements (the site works without JavaScript).
document.addEventListener('DOMContentLoaded', () => {
    // Dropdown menus (currency + user menu)
    document.querySelectorAll('[data-dropdown]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const parent = button.closest('.dropdown');
            document.querySelectorAll('.dropdown.open').forEach((d) => d !== parent && d.classList.remove('open'));
            parent.classList.toggle('open');
        });
    });
    document.addEventListener('click', () => document.querySelectorAll('.dropdown.open').forEach((d) => d.classList.remove('open')));

    // External images (FreeToGame thumbnails) fall back to a placeholder if they can't load
    document.querySelectorAll('img[data-fallback]').forEach((img) => {
        const swap = () => { if (img.src !== img.dataset.fallback) img.src = img.dataset.fallback; };
        img.addEventListener('error', swap);
        if (img.complete && img.naturalWidth === 0) swap();
    });

    // Dismiss flash messages (and auto-hide after 6 s)
    document.querySelectorAll('[data-flash]').forEach((flash) => {
        flash.querySelector('[data-dismiss]')?.addEventListener('click', () => flash.remove());
        setTimeout(() => flash.remove(), 6000);
    });

    // Auto-submit quantity / status changes
    document.querySelectorAll('[data-autosubmit]').forEach((field) => {
        field.addEventListener('change', () => field.form.submit());
    });

    // Confirmation before destructive actions
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    // Click a delivered code to copy it
    document.querySelectorAll('[data-copy]').forEach((box) => {
        box.title = 'Click to copy';
        box.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(box.innerText);
                box.classList.add('copied');
                setTimeout(() => box.classList.remove('copied'), 1500);
            } catch (e) { /* clipboard not available */ }
        });
    });

    // Seller form: show the "code" or "file" delivery field
    const radios = document.querySelectorAll('[data-toggle-delivery]');
    const updateDelivery = () => {
        const checked = document.querySelector('[data-toggle-delivery]:checked');
        if (!checked) return;
        document.querySelectorAll('[data-delivery]').forEach((el) => {
            el.style.display = el.dataset.delivery === checked.value ? '' : 'none';
        });
    };
    radios.forEach((r) => r.addEventListener('change', updateDelivery));
    updateDelivery();
});
