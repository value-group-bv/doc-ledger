import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button'];

    connect() {
        this.buttonTargets.forEach(button => {
            button.dataset.originalText = button.textContent;
            button.dataset.originalStyle = button.style.cssText;
            button.addEventListener('click', this.copyToClipboard.bind(this));
        });
    }

    async copyToClipboard(event) {
        const button = event.currentTarget;
        const docNumber = button.getAttribute('data-copy-doc-number');

        try {
            await navigator.clipboard.writeText(docNumber);

            // Visual feedback
            clearTimeout(button._copyResetTimeout);

            button.textContent = '✓ Copied';
            button.style.opacity = '1';

            button._copyResetTimeout = setTimeout(() => {
                button.textContent = button.dataset.originalText;
                button.style.cssText = button.dataset.originalStyle;
            }, 2000);
        } catch (err) {
            console.error('Failed to copy:', err);
            alert('Failed to copy to clipboard');
        }
    }
}
