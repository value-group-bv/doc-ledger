import { Controller } from '@hotwired/stimulus';

// Reference code: numeric (001) for signed projects, alphabetic (AAA / PRO) otherwise.
const REF_PATTERN = /^(\d{3}|[A-Z]{3})$/;
// Revision: final release (00, 01…) or interim version (0A, 1F…).
const REV_PATTERN = /^\d[0-9A-Z]$/;

/**
 * Builds the document ID for a ledger button, applying any active override
 * from the enclosing doc-id-override controller.
 */
export function buildDocId(button, { withRevision }) {
    const scope = button.closest('[data-controller~="doc-id-override"]');
    const ref = scope?.dataset.docIdOverrideRefValue || button.dataset.docIdRef;
    const rev = scope?.dataset.docIdOverrideRevValue || button.dataset.docIdRev;

    const base = `${button.dataset.docIdHead}-${ref}-${button.dataset.docIdTail}`;
    return withRevision ? `${base}-${rev}` : base;
}

/**
 * Temporarily overrides the reference code and revision shown in (and copied
 * from) the ledger's document IDs. Nothing is persisted.
 *
 * Only text content is touched: the live component re-applies externally
 * changed attributes after a re-render, which would leak stale values into
 * rows that now show a different entry.
 */
export default class extends Controller {
    static targets = ['ref', 'rev', 'clear'];
    static values = { ref: String, rev: String };

    connect() {
        // Re-apply after the LedgerTable live component re-renders rows.
        this.observer = new MutationObserver(() => this.render());
        this.observer.observe(this.element, { childList: true, characterData: true, subtree: true });
    }

    disconnect() {
        this.observer.disconnect();
    }

    update() {
        this.refValue = this.read(this.refTarget, REF_PATTERN);
        this.revValue = this.read(this.revTarget, REV_PATTERN);
        this.clearTarget.hidden = !this.refTarget.value && !this.revTarget.value;
    }

    clear() {
        this.refTarget.value = '';
        this.revTarget.value = '';
        this.update();
        this.refTarget.focus();
    }

    refValueChanged() {
        this.render();
    }

    revValueChanged() {
        this.render();
    }

    read(input, pattern) {
        input.value = input.value.toUpperCase();
        const valid = pattern.test(input.value);
        input.setAttribute('aria-invalid', input.value !== '' && !valid ? 'true' : 'false');
        return valid ? input.value : '';
    }

    render() {
        this.element.querySelectorAll('[data-doc-id-head]').forEach(button => {
            if (button.docIdCopied) return;

            const text = buildDocId(button, { withRevision: false });
            // Guard keeps the MutationObserver from looping on our own writes.
            if (button.textContent.trim() !== text) {
                button.textContent = text;
            }
        });
    }
}
