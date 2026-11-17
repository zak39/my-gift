// assets/controllers/drawer_controller.js
import { Controller } from '@hotwired/stimulus';

/*
 * Keyboard for a daisyUI drawer, whose side panel shows while its toggle (a checkbox) is
 * checked. Escape shuts it, the focus going back to the toggle. The focus leaving it shuts it
 * too: left open, the panel and its overlay would cover the page while the user tabs on
 * behind them, their focus out of sight (RGAA 10.7, 12.8).
 *
 * A dialog opened from the panel (dialog controller) shuts it first (dialog:open), or it
 * would stay open behind the dialog and after it, then gives the focus to the toggle once
 * closed (dialog:closed): the shut panel hides the entry that opened it, so the browser
 * cannot give it the focus back. The toggle is outlined by daisyUI, its own name kept.
 *
 * The listeners are the controller's own, not data-action, as in the dropdown controller:
 * a template only declares the controller and its toggle target.
 */
export default class extends Controller {
    static targets = ['toggle'];

    connect() {
        this.listeners = new AbortController();
        const signal = this.listeners.signal;

        this.element.addEventListener('keydown', (event) => this.#press(event), { signal });
        this.element.addEventListener('focusout', (event) => this.#leave(event), { signal });
        this.element.addEventListener('dialog:open', () => this.#close(), { signal });
        this.element.addEventListener('dialog:closed', () => this.toggleTarget.focus(), { signal });
    }

    disconnect() {
        this.listeners.abort();
    }

    #press(event) {
        if (event.key !== 'Escape' || !this.toggleTarget.checked) {
            return;
        }

        this.#close();
        this.toggleTarget.focus();
    }

    // While focusout runs, the focus has not moved yet: relatedTarget tells where it goes.
    #leave({ relatedTarget }) {
        if (relatedTarget && !this.element.contains(relatedTarget)) {
            this.#close();
        }
    }

    #close() {
        this.toggleTarget.checked = false;
    }
}
