// assets/controllers/copy_controller.js
import { Controller } from '@hotwired/stimulus';

// What the button and its status message (RGAA 7.5) say once the copy ended, and for how
// long (ms): a failure stays longer, there is more to read and something to do about it.
const OUTCOMES = {
    copied: { label: 'Copié', message: 'Lien copié', delay: 2000 },
    failed: { label: 'Non copié', message: "Le lien n'a pas pu être copié", delay: 4000 },
};

// The status is emptied, then written a moment later: a live region reads a change only,
// so a second copy saying the same as the first would be silent.
const ANNOUNCE_DELAY = 100;

/**
 * Copies a link (Molecule:ShareLinkButton) and says whether it worked: icon and label for
 * the eyes, tooltip too when the button is an icon only, status message for screen readers.
 */
export default class extends Controller {
    static values = {
        text: String,
        label: String,
    }

    static targets = ['icon', 'label', 'status']

    // The label comes from a value, not from the DOM, and the button starts idle: a page
    // left while it said "Copié" comes back from the Turbo cache in that state.
    connect() {
        this.reset();
    }

    disconnect() {
        this.cancelTimers();
    }

    async copy() {
        try {
            await navigator.clipboard.writeText(this.textValue);
            this.show('copied');
        } catch {
            this.show('failed');
        }
    }

    show(state) {
        const { label, message, delay } = OUTCOMES[state];

        this.cancelTimers();
        this.display(state, label, '');
        this.announceTimer = setTimeout(() => this.display(state, label, message), ANNOUNCE_DELAY);
        this.resetTimer = setTimeout(() => this.reset(), delay);
    }

    reset() {
        this.cancelTimers();
        this.display('idle', this.labelValue, '');
    }

    cancelTimers() {
        clearTimeout(this.announceTimer);
        clearTimeout(this.resetTimer);
    }

    // The tooltip of an icon-only button says the message once there is one, else the label.
    display(state, label, status) {
        this.iconTargets.forEach((icon) => icon.hidden = icon.dataset.state !== state);
        this.labelTarget.textContent = label;
        this.statusTarget.textContent = status;
        if ('tip' in this.element.dataset) {
            this.element.dataset.tip = status || label;
        }
    }
}
