// assets/controllers/flash_announcer_controller.js
import { Controller } from '@hotwired/stimulus';

/*
 * The page's status region (role="status", in base.html.twig), which reads the success
 * messages a page comes with (Alert, data-flash-announce) (RGAA 7.5).
 *
 * A live region is read when its content changes, not when it comes in already filled, and
 * a Turbo visit swaps the whole <body>: a role="status" in a page's flash would never be
 * read. This region is data-turbo-permanent, the same element from page to page, so
 * filling it is a change.
 *
 * It reads the page on each Turbo visit (turbo:load) and when it connects, the first load:
 * whichever comes first, a message read once is marked so as not to be read again, nor
 * when the back button shows the page from Turbo's cache.
 */
export default class extends Controller {
    connect() {
        this.listeners = new AbortController();
        const signal = this.listeners.signal;

        document.addEventListener('turbo:load', () => this.#announce(), { signal });
        // A new page clears the last one's message: the next one is a change, and an old one is not read again.
        document.addEventListener('turbo:visit', () => this.element.textContent = '', { signal });
        this.#announce();
    }

    disconnect() {
        this.listeners.abort();
    }

    #announce() {
        const flashes = [...document.querySelectorAll('[data-flash-announce]')];

        if (flashes.length === 0) {
            return;
        }

        flashes.forEach((flash) => flash.removeAttribute('data-flash-announce'));
        const message = flashes.map((flash) => flash.textContent.trim()).join(' ');

        // Once the screen reader is done with the new page's change.
        setTimeout(() => this.element.textContent = message, 100);
    }
}
