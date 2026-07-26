import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['field']

    reset() {
        const field = this.fieldTarget;
        field.value = '';
        field.dispatchEvent(new Event('input', { bubbles: true }));
    }
}