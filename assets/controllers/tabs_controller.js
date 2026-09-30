import { Controller } from '@hotwired/stimulus';

/**
 * Switches between the panels of a <twig:Tabs> component.
 *
 * With a `remember` key, the last opened tab is kept in sessionStorage so that pages whose
 * forms redirect back to themselves (like the admin page) reopen on the same tab. The URL hash
 * takes precedence: #tab-name opens that tab, #some-id opens the tab containing that element,
 * so tabs and rows can be linked to directly. When the server marks its
 * choice as `forced` (e.g. while editing a row that lives on a specific tab), that choice wins.
 */
export default class extends Controller {
    static targets = ['trigger', 'tab'];
    static values = { activeTab: String, remember: String, forced: Boolean };

    connect() {
        const ids = this.triggerTargets.map(trigger => trigger.dataset.tabId);
        const hash = window.location.hash.slice(1);

        // A hash can also point at an element inside a tab (e.g. #subcat-12 after saving a row)
        const hashPanel = hash ? document.getElementById(hash)?.closest('[data-slot="tabs-content"]') : null;

        let tab = this.activeTabValue;
        if (!this.forcedValue) {
            if (ids.includes(hash)) {
                tab = hash;
            } else if (hashPanel && this.element.contains(hashPanel)) {
                tab = hashPanel.dataset.tabId;
            } else if (ids.includes(this.stored())) {
                tab = this.stored();
            }
        }

        this.show(ids.includes(tab) ? tab : ids[0]);

        // The browser already tried to scroll to the anchor while its tab was still hidden
        if (hashPanel) {
            document.getElementById(hash).scrollIntoView({ block: 'center' });
        }
    }

    open(event) {
        const tab = event.currentTarget.dataset.tabId;
        this.show(tab);
        this.store(tab);
        history.replaceState(null, '', `#${tab}`);
    }

    show(tab) {
        this.activeTabValue = tab;

        this.triggerTargets.forEach(trigger => {
            const active = trigger.dataset.tabId === tab;
            trigger.dataset.state = active ? 'active' : 'inactive';
            trigger.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        this.tabTargets.forEach(panel => {
            panel.dataset.state = panel.dataset.tabId === tab ? 'active' : 'inactive';
        });
    }

    stored() {
        if (!this.rememberValue) return null;
        try {
            return sessionStorage.getItem(`tabs:${this.rememberValue}`);
        } catch {
            return null;
        }
    }

    store(tab) {
        if (!this.rememberValue) return;
        try {
            sessionStorage.setItem(`tabs:${this.rememberValue}`, tab);
        } catch {
            // Storage can be unavailable (private mode, blocked site data); tabs still work.
        }
    }
}
