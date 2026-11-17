import { Controller } from '@hotwired/stimulus';

/*
 * Molecule:SectionNav's links. Following one unfolds the section it leads to and, on a phone,
 * folds the summary back, as a click or the focus outside of it, or Escape, do. While the page scrolls, the entry of the section
 * being read is marked: on a long list, the menu tells whose gifts scroll by, the user's own
 * or a crew's.
 *
 * Each link leads to a block of the page (`#section-…`, `#crew-…`). An IntersectionObserver
 * watches them against a thin line a quarter down the screen: the blocks crossing it are
 * the ones in view, and current() picks among them.
 */

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['link', 'toggle'];

    connect() {
        /** @type {Map<HTMLElement, HTMLAnchorElement>} each block, and the link leading to it */
        this.links = new Map();
        for (const link of this.linkTargets) {
            const block = document.getElementById(link.hash.slice(1));
            if (block) {
                this.links.set(block, link);
            }
        }

        /** @type {Set<HTMLElement>} the blocks crossing the line */
        this.inView = new Set();
        this.active = null;

        this.observer = new IntersectionObserver(
            (entries) => this.update(entries),
            { rootMargin: '-25% 0px -74% 0px' },
        );
        this.links.forEach((link, block) => this.observer.observe(block));
    }

    disconnect() {
        this.observer.disconnect();
    }

    /**
     * Turbo keeps the page as it is left, to show it again on Back, before the controller
     * disconnects: leave it as a fresh one starts, no entry marked, the summary folded.
     */
    reset() {
        this.mark(this.active, false);
        this.active = null;
        this.fold();
    }

    /**
     * Folds the summary once a click or the focus lands outside of it, so that it does not
     * cover what the user goes on to (RGAA 10.7: the focus stays visible).
     */
    outside({ target }) {
        if (!this.element.contains(target)) {
            this.fold();
        }
    }

    /** Escape folds the summary, the focus back on its toggle. */
    escape() {
        if (this.toggleTarget.checked) {
            this.fold();
            this.toggleTarget.focus();
        }
    }

    /** On a wide screen the menu stays open whatever its checkbox says (lg:collapse-open). */
    fold() {
        this.toggleTarget.checked = false;
    }

    /**
     * Unfolds the section a link leads to (Molecule:Section's checkbox; a crew's block has
     * none of its own), and folds the summary back so that it does not cover it.
     */
    follow({ currentTarget }) {
        const block = document.getElementById(currentTarget.hash.slice(1));
        const sectionToggle = block?.querySelector(':scope > .collapse > input[type="checkbox"]');
        if (sectionToggle) {
            sectionToggle.checked = true;
        }

        this.fold();
    }

    update(entries) {
        for (const entry of entries) {
            if (entry.isIntersecting) {
                this.inView.add(entry.target);
            } else {
                this.inView.delete(entry.target);
            }
        }

        const active = this.current();
        if (active === this.active) {
            return;
        }

        this.mark(this.active, false);
        this.mark(active, true);
        this.active = active;
    }

    mark(block, active) {
        const link = this.links.get(block);
        if (!link) {
            return;
        }

        link.classList.toggle('menu-active', active);
        // The highlight in words, for screen readers (RGAA 3.1: not by colour alone).
        link.ariaCurrent = active ? 'location' : null;
    }

    /**
     * The block whose link to mark, among this.inView, or null for none. A crew's block holds
     * its sections, so both cross the line at once: the deepest one wins, the one holding no
     * other block in view. Sections never overlap, so at most one matches, whatever the order
     * the observer reported them in.
     *
     * @returns {HTMLElement|null}
     */
    current() {
        const blocks = [...this.inView];

        return blocks.find((block) => !blocks.some((other) => other !== block && block.contains(other))) ?? null;
    }
}
