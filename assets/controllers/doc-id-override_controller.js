import { Controller } from '@hotwired/stimulus';

// Reference code: numeric (001) for signed projects, alphabetic (AAA / PRO) otherwise.
const REF_PATTERN = /^(\d{3}|[A-Z]{3})$/;
// Revision: final release (00, 01…) or interim version (0A, 1F…).
const REV_PATTERN = /^\d[0-9A-Z]$/;

/**
 * Builds the document ID for a ledger button, applying any active override
 * from the enclosing doc-id-override controller.
 *
 * A main category override only applies to entries that list it in
 * data-doc-id-mains (their default plus alternates); it also swaps in that
 * category's reference code placeholder unless a reference code is typed.
 */
export function buildDocId(button, { withRevision }) {
    const scope = button.closest('[data-controller~="doc-id-override"]');
    const main = scope?.dataset.docIdOverrideMainValue;
    const bumped = main && main !== button.dataset.docIdMain
        && button.dataset.docIdMains.split(' ').includes(main);

    const head = bumped ? `${button.dataset.docIdSubsidiary}${main}` : button.dataset.docIdHead;
    const placeholderRef = bumped ? JSON.parse(scope.dataset.docIdOverrideRefsValue)[main] : button.dataset.docIdRef;
    const ref = scope?.dataset.docIdOverrideRefValue || placeholderRef;
    const rev = scope?.dataset.docIdOverrideRevValue || button.dataset.docIdRev;

    const base = `${head}-${ref}-${button.dataset.docIdTail}`;
    return withRevision ? `${base}-${rev}` : base;
}

/**
 * Temporarily overrides the main category, reference code and revision shown
 * in (and copied from) the ledger's document IDs. Nothing is persisted.
 *
 * Only text content is touched: the live component re-applies externally
 * changed attributes after a re-render, which would leak stale values into
 * rows that now show a different entry.
 */
export default class extends Controller {
    static targets = ['main', 'ref', 'rev', 'clear'];
    static values = { main: String, ref: String, rev: String, refs: Object };

    connect() {
        // Re-apply after the LedgerTable live component re-renders rows.
        this.observer = new MutationObserver(() => this.render());
        this.observer.observe(this.element, { childList: true, characterData: true, subtree: true });
    }

    disconnect() {
        this.observer.disconnect();
    }

    update() {
        this.mainValue = this.mainTarget.value;
        this.refValue = this.read(this.refTarget, REF_PATTERN);
        this.revValue = this.read(this.revTarget, REV_PATTERN);
        this.clearTarget.hidden = !this.mainTarget.value && !this.refTarget.value && !this.revTarget.value;
    }

    clear() {
        this.mainTarget.value = '';
        this.refTarget.value = '';
        this.revTarget.value = '';
        this.update();
        this.refTarget.focus();
    }

    mainValueChanged() {
        this.render();
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
