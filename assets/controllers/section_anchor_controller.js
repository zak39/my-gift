// assets/controllers/section_anchor_controller.js
import { Controller } from '@hotwired/stimulus';

/*
 * Unfolds a Molecule:Section when the URL points at it (#section-12) as the page renders:
 * a reload, or a link from elsewhere. The server cannot do it, a fragment never reaches it.
 *
 * A click on a Molecule:SectionNav entry renders nothing (app.js leaves in-page anchors to
 * the browser): the entry's onclick unfolds the section.
 */
export default class extends Controller {
    static targets = ['toggle'];

    connect() {
        if (window.location.hash === `#${this.element.id}`) {
            this.toggleTarget.checked = true;
        }
    }
}
