// Mobile navigation drawer.
const drawer = document.querySelector('[data-drawer]');
const opener = document.querySelector('[data-drawer-open]');
const shell = document.querySelector('[data-app-shell]');

function setDrawer(open) {
    if (!drawer || !opener) return;
    drawer.classList.toggle('hidden', !open);
    opener.setAttribute('aria-expanded', String(open));
    // Keep keyboard and screen-reader focus inside the open drawer.
    shell?.toggleAttribute('inert', open);
    document.body.classList.toggle('overflow-hidden', open);
    if (open) drawer.querySelector('[data-drawer-close].icon-btn')?.focus();
    else opener.focus();
}

opener?.addEventListener('click', () => setDrawer(true));
drawer?.querySelectorAll('[data-drawer-close]').forEach((el) => el.addEventListener('click', () => setDrawer(false)));
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && drawer && !drawer.classList.contains('hidden')) setDrawer(false);
});

// Ask for confirmation before submitting forms marked with data-confirm.
document.addEventListener('submit', (event) => {
    const message = event.target.dataset?.confirm;
    if (message && !window.confirm(message)) event.preventDefault();
});
