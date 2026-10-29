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
    resetBusy(document);
});

function resetBusy(root) {
    root.querySelectorAll('button[aria-busy="true"]').forEach((button) => {
        button.removeAttribute('aria-busy');
        button.disabled = false;
        button.querySelector('.spinner')?.remove();
    });
}

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
function initItemRows(root) {
    root.querySelectorAll('[data-items]').forEach(initItemBox);
}

function initItemBox(box) {
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
}

initItemRows(document);

// Create/edit forms in a modal. A link with data-form-modal fetches its page and shows that page's <form>.
// The form is posted with fetch: validation errors (422) appear in the modal, any redirect reloads the page,
// so flash messages and server-side rules stay exactly as on the full form page.
const formModal = document.getElementById('form-modal');
const formModalBody = formModal?.querySelector('[data-form-modal-body]');
const formModalError = formModal?.querySelector('[data-form-modal-error]');

function showModalError(message) {
    formModalError.textContent = message;
    formModalError.classList.toggle('hidden', !message);
}

document.addEventListener('click', async (event) => {
    const link = event.target.closest('a[data-form-modal]');
    if (!link || !formModal || event.ctrlKey || event.metaKey || event.shiftKey) return;
    event.preventDefault();

    try {
        const response = await fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        // A redirect means the page was not served (e.g. session expired, sent to login): use the full page instead.
        if (!response.ok || response.redirected) throw new Error();
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const form = page.querySelector('main form');
        if (!form) throw new Error();

        form.classList.remove('card', 'max-w-3xl', 'max-w-4xl');
        formModal.querySelector('#form-modal-title').textContent = page.querySelector('h1')?.textContent.trim() ?? '';
        showModalError('');
        formModalBody.replaceChildren(document.adoptNode(form));
        initItemRows(formModalBody);
        formModal.showModal();
        formModalBody.querySelector('[autofocus]')?.focus();
    } catch {
        window.location.href = link.href; // Fall back to the full page.
    }
});

formModal?.addEventListener('click', (event) => {
    // "Batal" links inside the form and the close button just close the modal.
    if (event.target.closest('[data-form-modal-close], [data-form-modal-body] a.btn-secondary')) {
        event.preventDefault();
        formModal.close();
    }
});

formModal?.addEventListener('close', () => formModalBody.replaceChildren());

function fieldName(key) {
    const [first, ...rest] = key.split('.');
    return first + rest.map((part) => `[${part}]`).join('');
}

function showFieldErrors(form, errors) {
    form.querySelectorAll('[data-modal-field-error]').forEach((el) => el.remove());
    form.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));

    const unplaced = [];
    Object.entries(errors).forEach(([key, messages]) => {
        const field = form.querySelector(`[name="${CSS.escape(fieldName(key))}"]`);
        if (!field) return unplaced.push(messages[0]);
        const target = (field.type === 'radio' && field.closest('fieldset')) || field;
        field.setAttribute('aria-invalid', 'true');
        const message = document.createElement('p');
        message.className = 'mt-1.5 text-xs text-danger';
        message.dataset.modalFieldError = '';
        message.textContent = messages[0];
        target.insertAdjacentElement(target === field ? 'afterend' : 'beforeend', message);
    });

    showModalError(unplaced.join(' '));
    form.querySelector('[aria-invalid="true"]')?.focus();
}

// Registered after the confirm and busy handlers: it runs only for a submit nobody cancelled.
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (event.defaultPrevented || !formModal?.contains(form)) return;
    event.preventDefault();

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            redirect: 'manual',
        });

        // Success or a server-side refusal both answer with a redirect; reloading shows its flash message.
        if (response.type === 'opaqueredirect') return window.location.reload();

        resetBusy(form);
        if (response.status === 422) return showFieldErrors(form, (await response.json()).errors ?? {});
        showModalError(
            [401, 419].includes(response.status)
                ? 'Sesi Anda sudah berakhir. Muat ulang halaman lalu coba lagi.'
                : response.status === 403
                  ? 'Anda tidak memiliki akses untuk tindakan ini.'
                  : 'Terjadi kesalahan. Silakan coba lagi.',
        );
    } catch {
        resetBusy(form);
        showModalError('Tidak dapat terhubung ke server. Periksa koneksi lalu coba lagi.');
    }
});

// Phone layout for .table-stack tables: give each cell its column name so CSS can show it as a label.
// Explicit ARIA roles keep the table readable by screen readers after CSS turns rows into blocks.
function initTables(root) {
    root.querySelectorAll('table.table-stack').forEach(initTable);
}

function initTable(table) {
    table.setAttribute('role', 'table');
    table.querySelectorAll('thead, tbody, tfoot').forEach((group) => group.setAttribute('role', 'rowgroup'));
    table.querySelectorAll('tr').forEach((row) => row.setAttribute('role', 'row'));
    table.querySelectorAll('thead th').forEach((th) => th.setAttribute('role', 'columnheader'));
    table.querySelectorAll('tbody th, tfoot th').forEach((th) => th.setAttribute('role', 'rowheader'));
    table.querySelectorAll('td').forEach((td) => td.setAttribute('role', 'cell'));

    // A header that only holds screen-reader text (e.g. the "Detail" button column) gets no visible label.
    const labels = [...table.querySelectorAll('thead th')].map((th) =>
        th.textContent.trim() === th.querySelector('.sr-only')?.textContent.trim() ? '' : th.textContent.trim(),
    );
    table.querySelectorAll('tbody tr, tfoot tr').forEach((row) => {
        [...row.cells].forEach((cell, index) => {
            if (cell.colSpan === 1) cell.dataset.label = labels[index] ?? '';
        });
    });
}

initTables(document);

// Filters, search and pagination swap only the page content instead of reloading the whole page,
// so CSS, fonts and the layout stay put. Anything unexpected falls back to a normal navigation.
const main = document.getElementById('main');
let swapController = null;
let currentUrl = window.location.pathname + window.location.search;

async function swapMain(url, { push = true, keepFocus = false } = {}) {
    swapController?.abort();
    const controller = (swapController = new AbortController());
    const focused = keepFocus && document.activeElement?.id ? document.activeElement : null;
    const caret = focused?.selectionStart ?? null;
    main.setAttribute('aria-busy', 'true');

    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal,
        });
        // A redirect (e.g. session expired) or an error page is better shown as a full page.
        if (!response.ok || response.redirected) throw new Error();
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const next = page.getElementById('main');
        if (!next) throw new Error();

        main.replaceChildren(...[...next.childNodes].map((node) => document.adoptNode(node)));
        document.title = page.title;
        if (push) history.pushState({ swap: true }, '', url);
        currentUrl = window.location.pathname + window.location.search;
        initTables(main);
        initItemRows(main);

        const again = focused && document.getElementById(focused.id);
        if (again) {
            // Keep what the user typed while the request was running; the pending debounce submits it.
            if ('value' in again) again.value = focused.value;
            again.focus();
            if (caret !== null) again.setSelectionRange?.(caret, caret);
        }
    } catch (error) {
        if (error.name !== 'AbortError') window.location.href = url;
    } finally {
        // A newer swap that aborted this one still owns the busy state.
        if (swapController === controller) main.removeAttribute('aria-busy');
    }
}

function formUrl(form) {
    const url = new URL(form.action || window.location.href);
    // Empty fields are left out so the URL only shows filters that are in use.
    url.search = new URLSearchParams([...new FormData(form)].filter(([, value]) => value !== '')).toString();
    return url.href;
}

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented || !main?.contains(form) || form.method.toLowerCase() !== 'get') return;
    event.preventDefault();
    swapMain(formUrl(form), { keepFocus: true });
});

main?.addEventListener('click', (event) => {
    const link = event.target.closest('a[rel="prev"], a[rel="next"]');
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey) return;
    event.preventDefault();
    swapMain(link.href);
    main.focus({ preventScroll: true });
    main.scrollIntoView({ block: 'start' });
});

// Typing in a search box filters after a short pause, without pressing Enter.
let searchTimer;
main?.addEventListener('input', (event) => {
    const input = event.target;
    if (input.type !== 'search' || !input.form || input.form.method.toLowerCase() !== 'get') return;
    clearTimeout(searchTimer);
    // Look the input up again when the timer fires: a swap in between replaces the element.
    searchTimer = setTimeout(() => (document.getElementById(input.id) ?? input).form?.requestSubmit(), 350);
});

// Back/forward through swapped pages reloads the content for that URL.
window.addEventListener('popstate', () => {
    // A change of only the #fragment needs no new content.
    if (!main || window.location.pathname + window.location.search === currentUrl) return;
    swapMain(window.location.href, { push: false });
});
