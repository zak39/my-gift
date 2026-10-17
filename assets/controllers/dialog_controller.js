// assets/controllers/dialog_controller.js
import { Controller } from '@hotwired/stimulus';

/*
 * Opens a <dialog> rendered elsewhere on the page, found by its id: a missing dialog
 * does nothing. A button in a drawer closes it first, or it would stay open behind the
 * dialog and after it.
 */
export default class extends Controller {
    static values = {
        id: String
    }

    open() {
        const drawerToggle = this.element.closest('.drawer')?.querySelector(':scope > .drawer-toggle');

        if (drawerToggle) {
            drawerToggle.checked = false;
        }

        document.getElementById(this.idValue)?.showModal();
    }
}
