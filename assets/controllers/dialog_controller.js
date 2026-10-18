// assets/controllers/dialog_controller.js
import { Controller } from '@hotwired/stimulus';

/*
 * Opens a <dialog> rendered elsewhere on the page, found by its id: a missing dialog
 * does nothing. A button in a drawer closes it first, or it would stay open behind the
 * dialog and after it.
 *
 * A closed menu hides the button that opened the dialog, so the browser cannot give it
 * the focus back: on close, the focus goes to the drawer's toggle, and the dialog:closed
 * event lets a dropdown controller give it to its trigger.
 */
export default class extends Controller {
    static values = {
        id: String
    }

    open() {
        const dialog = document.getElementById(this.idValue);

        if (!dialog) {
            return;
        }

        const drawerToggle = this.element.closest('.drawer')?.querySelector(':scope > .drawer-toggle');

        if (drawerToggle) {
            drawerToggle.checked = false;
        }

        dialog.showModal();
        dialog.addEventListener('close', () => this.#restoreFocus(drawerToggle), { once: true });
    }

    #restoreFocus(drawerToggle) {
        drawerToggle?.focus();
        this.dispatch('closed');
    }
}
