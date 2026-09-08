import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['anonymous', 'pseudo']


    connect() {
        this.update();
    }

    update() {
        this.pseudoTarget.disabled = !this.anonymousTarget.checked;
    }
}
