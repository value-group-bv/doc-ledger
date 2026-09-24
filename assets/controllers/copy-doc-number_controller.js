import { Controller } from '@hotwired/stimulus';
import { buildDocId } from './doc-id-override_controller.js';

export default class extends Controller {
    async copy() {
        const button = this.element;
        const docId = buildDocId(button, { withRevision: true });

        try {
            await navigator.clipboard.writeText(docId);

            // Visual feedback
            clearTimeout(this.resetTimeout);

            button.docIdCopied = true;
            button.textContent = '✓ Copied';
            button.style.opacity = '1';

            this.resetTimeout = setTimeout(() => {
                button.docIdCopied = false;
                button.textContent = buildDocId(button, { withRevision: false });
                button.style.removeProperty('opacity');
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
