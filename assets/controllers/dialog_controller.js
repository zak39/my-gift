import { Controller } from '@hotwired/stimulus';

/*
 * Opens a <dialog> rendered elsewhere on the page, found by its id: a missing dialog
 * does nothing.
 *
 * A closed menu hides the button that opened the dialog, so the browser cannot give it
 * the focus back. The menu takes care of it: dialog:open, sent before the dialog shows,
 * lets a drawer controller shut its drawer (or it would stay open behind the dialog and
 * after it), and dialog:closed lets a drawer or dropdown controller give the focus to its
 * toggle or trigger.
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

        this.dispatch('open');
        dialog.showModal();
        dialog.addEventListener('close', () => this.dispatch('closed'), { once: true });
    }
}
