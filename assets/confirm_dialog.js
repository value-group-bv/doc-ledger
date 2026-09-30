import * as Turbo from '@hotwired/turbo';

const BUTTON_STYLES = {
    destructive: ['bg-destructive', 'hover:bg-destructive/90', 'text-white'],
    default: ['bg-primary', 'hover:bg-primary/90', 'text-primary-foreground'],
};

/**
 * Replaces the browser's confirm() for forms with data-turbo-confirm by the styled
 * dialog in templates/_confirm_dialog.html.twig. Resolves true only on the confirm button;
 * Cancel, Esc and clicking outside all cancel.
 */
function confirmWithDialog(message, form) {
    const dialog = document.getElementById('confirm-dialog');
    if (!dialog) {
        return Promise.resolve(window.confirm(message));
    }

    const button = dialog.querySelector('[data-confirm-button]');
    const variant = form.dataset.confirmVariant === 'default' ? 'default' : 'destructive';
    button.classList.remove(...BUTTON_STYLES.destructive, ...BUTTON_STYLES.default);
    button.classList.add(...BUTTON_STYLES[variant]);
    button.textContent = form.dataset.confirmLabel || 'Delete';
    dialog.querySelector('[data-confirm-message]').textContent = message;

    const closeOnBackdrop = event => {
        if (event.target === dialog) dialog.close('cancel');
    };
    dialog.addEventListener('click', closeOnBackdrop);

    dialog.returnValue = '';
    dialog.showModal();

    return new Promise(resolve => {
        dialog.addEventListener('close', () => {
            dialog.removeEventListener('click', closeOnBackdrop);
            resolve(dialog.returnValue === 'confirm');
        }, { once: true });
    });
}

Turbo.config.forms.confirm = confirmWithDialog;
