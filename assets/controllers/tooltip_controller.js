// assets/controllers/tooltip_controller.js
import { Controller } from '@hotwired/stimulus';

const MARGIN = 8; // px kept between the tooltip and the viewport's edges
const GAP = 6; // px between the tooltip and what it describes
const HIDE_DELAY = 100; // ms: time to move the pointer from the trigger onto the tooltip

/*
 * A tooltip for what the controller sits on (RGAA 10.11 and 10.13). It replaces daisyUI's CSS
 * tooltip, which widened the page at 320px and could not be dismissed:
 *
 * - it exists only while it shows, and it is fixed and kept inside the viewport: it never
 *   takes room in the layout, so it cannot make the page scroll sideways;
 * - Escape hides it without moving the focus or the pointer;
 * - it stays while the pointer is on it (hoverable) and while the keyboard focus stays on its
 *   trigger, until the user leaves or dismisses it (persistent);
 * - it follows its trigger when the page scrolls, and is removed before Turbo caches the page
 *   (a snapshot would bring it back with no controller to remove it).
 *
 * It repeats the accessible name of its trigger for the eyes, so screen readers skip it
 * (aria-hidden). Touch screens, which have no hover, get none: a tap would leave it open.
 * The text may be changed while it shows (copy_controller does): it is rewritten in place.
 *
 * A line break in the text (\n) is kept, to list several names.
 */
export default class extends Controller {
    static values = {
        text: String,
    }

    #onKeydown = (event) => event.key === 'Escape' && this.#hide();
    #onScroll = () => this.#place();

    connect() {
        this.listeners = new AbortController();
        const signal = this.listeners.signal;

        this.element.addEventListener('pointerenter', (event) => event.pointerType !== 'touch' && this.#show(), { signal });
        this.element.addEventListener('pointerleave', (event) => event.pointerType !== 'touch' && this.#hideSoon(), { signal });
        this.element.addEventListener('focusin', (event) => event.target.matches(':focus-visible') && this.#show(), { signal });
        // While focusout runs, the focus has not moved yet: relatedTarget tells where it goes.
        this.element.addEventListener('focusout', (event) => !this.element.contains(event.relatedTarget) && this.#hide(), { signal });
        document.addEventListener('turbo:before-cache', () => this.#hide(), { signal });
    }

    disconnect() {
        this.listeners.abort();
        this.#hide();
    }

    textValueChanged() {
        if (this.tooltip) {
            this.tooltip.textContent = this.textValue;
            this.#place();
        }
    }

    #show() {
        clearTimeout(this.hideTimer);

        if (this.tooltip || !this.textValue) {
            return;
        }

        this.tooltip = document.createElement('div');
        this.tooltip.setAttribute('aria-hidden', 'true');
        this.tooltip.className = 'fixed z-50 w-max rounded-field bg-neutral px-2 py-1 text-xs text-neutral-content whitespace-pre-line shadow-md';
        this.tooltip.style.maxWidth = `${window.innerWidth - 2 * MARGIN}px`;
        this.tooltip.textContent = this.textValue;
        this.tooltip.addEventListener('pointerenter', () => clearTimeout(this.hideTimer));
        this.tooltip.addEventListener('pointerleave', () => this.#hideSoon());
        document.body.append(this.tooltip);

        // Fixed in the viewport: a scroll of the page would leave it behind its trigger.
        document.addEventListener('keydown', this.#onKeydown);
        window.addEventListener('scroll', this.#onScroll, { passive: true });

        this.#place();
    }

    // The pointer leaving does not hide what the keyboard focus still holds open.
    #hideSoon() {
        clearTimeout(this.hideTimer);

        if (this.element.contains(document.activeElement) && document.activeElement.matches(':focus-visible')) {
            return;
        }

        this.hideTimer = setTimeout(() => this.#hide(), HIDE_DELAY);
    }

    #hide() {
        clearTimeout(this.hideTimer);
        document.removeEventListener('keydown', this.#onKeydown);
        window.removeEventListener('scroll', this.#onScroll);
        this.tooltip?.remove();
        this.tooltip = null;
    }

    // Above the trigger, below when there is no room, and never past the viewport's sides.
    #place() {
        const trigger = this.element.getBoundingClientRect();
        const { width, height } = this.tooltip.getBoundingClientRect();
        const left = trigger.left + (trigger.width - width) / 2;
        const above = trigger.top - GAP - height;

        this.tooltip.style.left = `${Math.max(MARGIN, Math.min(left, window.innerWidth - width - MARGIN))}px`;
        this.tooltip.style.top = `${above >= MARGIN ? above : trigger.bottom + GAP}px`;
    }
}
