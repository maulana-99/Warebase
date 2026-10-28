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

// Confirmation modal for forms marked with data-confirm (logout, delete, approve, reject...).
const dialog = document.getElementById('confirm-dialog');
let pendingForm = null;

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!form.dataset?.confirm || form.dataset.confirmed === 'true') return;

    event.preventDefault();
    if (!dialog) {
        if (window.confirm(form.dataset.confirm)) submitConfirmed(form);
        return;
    }

    pendingForm = form;
    const danger = form.dataset.confirmTone !== 'neutral';
    dialog.querySelector('#confirm-title').textContent = form.dataset.confirmTitle || 'Apakah Anda yakin?';
    dialog.querySelector('#confirm-message').textContent = form.dataset.confirm;
    const accept = dialog.querySelector('[data-confirm-accept]');
    accept.textContent = form.dataset.confirmButton || 'Ya, lanjutkan';
    accept.classList.toggle('btn-danger', danger);
    dialog.querySelector('[data-confirm-icon]').classList.toggle('hidden', !danger);
    dialog.showModal();
});

function submitConfirmed(form) {
    // The flag only lives for this one submit, so Back/validation can never skip the next confirmation.
    form.dataset.confirmed = 'true';
    form.requestSubmit();
    delete form.dataset.confirmed;
}

dialog?.querySelector('[data-confirm-cancel]').addEventListener('click', () => dialog.close());
dialog?.querySelector('[data-confirm-accept]').addEventListener('click', () => {
    const form = pendingForm;
    dialog.close();
    if (form) submitConfirmed(form);
});
dialog?.addEventListener('close', () => {
    pendingForm = null;
});
// Click on the backdrop closes the modal.
dialog?.addEventListener('click', (event) => {
    if (event.target === dialog) dialog.close();
});

// Busy state: once a form really submits, disable its buttons and show a spinner (prevents double submit).
document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;
    const form = event.target;
    form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
        button.setAttribute('aria-busy', 'true');
        button.disabled = true;
        if (button === event.submitter || !event.submitter) {
            button.insertAdjacentHTML('afterbegin', '<span class="spinner" aria-hidden="true"></span>');
        }
    });
});

// Restore buttons when the page comes back from the back/forward cache.
window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;
    pendingForm = null;
    document.querySelectorAll('form[data-confirmed]').forEach((form) => delete form.dataset.confirmed);
    document.querySelectorAll('button[aria-busy="true"]').forEach((button) => {
        button.removeAttribute('aria-busy');
        button.disabled = false;
        button.querySelector('.spinner')?.remove();
    });
});

// Light/dark theme toggle (initial theme is set inline in <head> to avoid a flash).
function syncThemeButtons() {
    const next = document.documentElement.dataset.theme === 'dark' ? 'terang' : 'gelap';
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-label', `Ganti ke mode ${next}`);
    });
}

document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = theme;
        try {
            localStorage.setItem('theme', theme);
        } catch (e) {
            // Storage blocked (private mode): the theme still applies for this page.
        }
        syncThemeButtons();
    });
});
syncThemeButtons();

// Repeatable item rows (multi-item transactions). New rows come from a <template> with __INDEX__ placeholders.
document.querySelectorAll('[data-items]').forEach((box) => {
    const list = box.querySelector('[data-item-list]');
    const template = box.querySelector('[data-item-template]');
    let next = list.children.length;

    const refresh = () => {
        const single = list.children.length === 1;
        list.querySelectorAll('[data-item-remove]').forEach((button) => (button.disabled = single));
    };

    box.querySelector('[data-item-add]').addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(next++));
        list.insertAdjacentHTML('beforeend', html);
        list.lastElementChild.querySelector('select')?.focus();
        refresh();
    });

    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-item-remove]');
        if (!button || list.children.length === 1) return;
        button.closest('[data-item-row]').remove();
        refresh();
    });

    refresh();
});
