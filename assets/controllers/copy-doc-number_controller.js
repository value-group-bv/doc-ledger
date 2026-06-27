import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button'];

    connect() {
        this.buttonTargets.forEach(button => {
            button.addEventListener('click', this.copyToClipboard.bind(this));
        });
    }

    async copyToClipboard(event) {
        const button = event.currentTarget;
        const docNumber = button.getAttribute('data-copy-doc-number');

        try {
            await navigator.clipboard.writeText(docNumber);

            // Visual feedback
            const originalText = button.textContent;
            const originalStyle = button.style.cssText;

            button.textContent = '✓ Copied';
            button.style.opacity = '1';

            setTimeout(() => {
                button.textContent = originalText;
                button.style.cssText = originalStyle;
            }, 2000);
        } catch (err) {
            console.error('Failed to copy:', err);
            alert('Failed to copy to clipboard');
        }
    }
}
