import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'preview', 'caption'];
    static values = { openGraphUrl: String };

    currentBlobUrl = null;

    show() {
        const file = this.inputTarget.files[0];

        if (!file || !file.type.startsWith('image/')) {
            this.clearLocalFile();
            return;
        }

        this.revokeCurrentBlobUrl();
        this.currentBlobUrl = URL.createObjectURL(file);
        this.previewTarget.setAttribute('data-live-ignore', '');
        this.applyPreview(this.currentBlobUrl, 'Image sélectionnée.');
    }

    clearLocalFile() {
        this.revokeCurrentBlobUrl();
        this.previewTarget.removeAttribute('data-live-ignore');
        this.applyFromOpenGraph();
    }

    // Callback Stimulus automatique : se déclenche à la connexion ET
    // à chaque fois que le serveur met à jour openGraphUrl via un re-rendu
    openGraphUrlValueChanged() {
        if (this.previewTarget.hasAttribute('data-live-ignore')) {
            return;
        }
        this.applyFromOpenGraph();
    }

    applyFromOpenGraph() {
        if (this.openGraphUrlValue) {
            this.applyPreview(this.openGraphUrlValue, 'Image trouvée automatiquement.');
        } else {
            this.previewTarget.removeAttribute('src');
            this.previewTarget.classList.add('hidden');
            if (this.hasCaptionTarget) this.captionTarget.textContent = '';
        }
    }

    applyPreview(src, caption) {
        this.previewTarget.src = src;
        this.previewTarget.classList.remove('hidden');
        if (this.hasCaptionTarget) this.captionTarget.textContent = caption;
    }

    revokeCurrentBlobUrl() {
        if (this.currentBlobUrl) {
            URL.revokeObjectURL(this.currentBlobUrl);
            this.currentBlobUrl = null;
        }
    }

    disconnect() {
        this.revokeCurrentBlobUrl();
    }
}