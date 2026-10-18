// assets/controllers/dropdown_controller.js
import { Controller } from '@hotwired/stimulus';

/*
 * Follows a daisyUI dropdown, which opens while the focus is inside it (:focus-within):
 * the trigger's aria-expanded tells a screen reader whether the menu shows.
 *
 * On Escape, or when a dialog opened from an entry closes (dialog:closed), the focus goes
 * back to the trigger with the menu kept shut (daisyUI's dropdown-close): focusing the
 * trigger alone would reopen the menu. It opens again when the trigger is clicked or
 * pressed, or the focus comes back from elsewhere on the page; not when the window gets
 * the focus back, which would reopen a menu the user shut.
 *
 * The listeners are the controller's own, not data-action: a dropdown missing one would
 * lose the focus again. A template only declares the controller and its trigger target.
 */
export default class extends Controller {
    static targets = ['trigger'];

    connect() {
        this.listeners = new AbortController();
        const signal = this.listeners.signal;

        this.element.addEventListener('focusin', (event) => this.#enter(event), { signal });
        // While focusout runs, the focus has not moved yet: relatedTarget tells where it goes.
        this.element.addEventListener('focusout', (event) => this.#sync(this.element.contains(event.relatedTarget)), { signal });
        this.element.addEventListener('dialog:closed', () => this.#close(), { signal });
        this.element.addEventListener('keydown', (event) => this.#press(event), { signal });
        // On the dropdown rather than the trigger, which a re-render may replace.
        this.element.addEventListener('click', (event) => this.#click(event), { signal });
        this.#sync();
    }

    disconnect() {
        this.listeners.abort();
    }

    #enter({ relatedTarget }) {
        if (relatedTarget && !this.element.contains(relatedTarget)) {
            this.element.classList.remove('dropdown-close');
        }

        this.#sync();
    }

    #click({ target }) {
        if (this.triggerTarget.contains(target)) {
            this.#open();
        }
    }

    #press(event) {
        if (event.key === 'Escape') {
            this.#close();

            return;
        }

        if (event.target !== this.triggerTarget || (event.key !== 'Enter' && event.key !== ' ')) {
            return;
        }

        // Space would scroll the page.
        event.preventDefault();

        if (this.#isClosed()) {
            this.#open();
        } else {
            this.#close();
        }
    }

    #open() {
        this.element.classList.remove('dropdown-close');
        this.#sync();
    }

    // Focus first: an entry hidden while it still has the focus would drop it to <body>.
    #close() {
        this.triggerTarget.focus();
        this.element.classList.add('dropdown-close');
        this.#sync();
    }

    #sync(focusInside = this.element.matches(':focus-within')) {
        this.triggerTarget.setAttribute('aria-expanded', String(focusInside && !this.#isClosed()));
    }

    #isClosed() {
        return this.element.classList.contains('dropdown-close');
    }
}
