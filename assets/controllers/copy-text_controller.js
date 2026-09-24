import { Controller } from '@hotwired/stimulus';

/**
 * Copies a fixed text value to the clipboard on click.
 *
 * The label is restored from the value attribute (not a cached copy), so it
 * stays correct when the live component re-renders the row with another entry.
 */
export default class extends Controller {
    static values = { text: String };

    async copy() {
        try {
            await navigator.clipboard.writeText(this.textValue);

            // Visual feedback
            clearTimeout(this.resetTimeout);

            this.element.textContent = '✓ Copied';

            this.resetTimeout = setTimeout(() => {
                this.element.textContent = this.textValue;
            }, 2000);
        } catch (err) {
            console.error('Failed to copy:', err);
            alert('Failed to copy to clipboard');
        }
    }

    disconnect() {
        clearTimeout(this.resetTimeout);
    }
}
