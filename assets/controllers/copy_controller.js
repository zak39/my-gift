// assets/controllers/copy_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        text: String
    }

    static targets = ['icon', 'label']

    connect() {
        this.previousIcon = this.iconTarget.innerHTML;
        this.previousLabel = this.labelTarget.textContent;
    }

    copy(event) {
        navigator.clipboard.writeText(this.textValue);

        const icon = this.iconTarget
        
        this.iconTarget.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 7L10 17l-5-5"/></svg>';
        this.labelTarget.textContent = 'Copié';

        setTimeout(() => {
            this.labelTarget.textContent = this.previousLabel;
            this.iconTarget.innerHTML = this.previousIcon;
        }, 2000);
    }
}
