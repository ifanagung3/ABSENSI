import './bootstrap';
import Alpine from 'alpinejs';
import { attendanceCamera } from './attendance-camera';

window.Alpine = Alpine;
Alpine.data('attendanceCamera', attendanceCamera);
Alpine.start();

const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

let activeDialog = null;
let dialogReturnFocus = null;

const visibleDialogs = () => Array.from(
    document.querySelectorAll('[role="dialog"][aria-modal="true"]')
).filter((dialog) => dialog.getClientRects().length > 0);

const syncDialogFocus = () => {
    const dialogs = visibleDialogs();
    const nextDialog = dialogs.at(-1) ?? null;

    if (nextDialog === activeDialog) return;

    if (nextDialog) {
        if (!activeDialog) dialogReturnFocus = document.activeElement;
        activeDialog = nextDialog;

        window.requestAnimationFrame(() => {
            const target = activeDialog?.querySelector('[autofocus], input:not([type="hidden"]), select, textarea, button, a[href]');
            (target ?? activeDialog)?.focus({ preventScroll: true });
        });
        return;
    }

    activeDialog = null;
    if (dialogReturnFocus instanceof HTMLElement && document.contains(dialogReturnFocus)) {
        dialogReturnFocus.focus({ preventScroll: true });
    }
    dialogReturnFocus = null;
};

document.addEventListener('keydown', (event) => {
    const dialog = visibleDialogs().at(-1);
    if (!dialog) return;

    if (event.key === 'Escape') {
        const closeButton = dialog.querySelector('[data-modal-close]');
        if (closeButton) {
            event.preventDefault();
            closeButton.click();
        }
        return;
    }

    if (event.key !== 'Tab') return;

    const focusable = Array.from(dialog.querySelectorAll(focusableSelector))
        .filter((element) => element.getClientRects().length > 0);

    if (focusable.length === 0) {
        event.preventDefault();
        dialog.focus();
        return;
    }

    const first = focusable[0];
    const last = focusable.at(-1);

    if (event.shiftKey && (document.activeElement === first || !dialog.contains(document.activeElement))) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
});

new MutationObserver(syncDialogFocus).observe(document.body, {
    attributes: true,
    attributeFilter: ['style'],
    childList: true,
    subtree: true,
});

syncDialogFocus();

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || form.dataset.noLoading === 'true') {
        return;
    }

    if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
    }

    form.dataset.submitting = 'true';
    form.setAttribute('aria-busy', 'true');

    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((submitter) => {
        submitter.disabled = true;
        submitter.classList.add('is-submitting');
    });
});
