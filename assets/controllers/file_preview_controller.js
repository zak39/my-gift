import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'preview', 'caption'];
    // L'image affichée tant qu'aucun fichier n'est choisi : celle trouvée via
    // OpenGraph à la création, l'image actuelle à l'édition
    static values = { fallbackUrl: String, fallbackCaption: String };

    currentBlobUrl = null;

    show() {
        const file = this.inputTarget.files[0];

        if (!file || !file.type.startsWith('image/')) {
            this.clearLocalFile();
            return;
        }

        this.revokeCurrentBlobUrl();
        this.currentBlobUrl = URL.createObjectURL(file);
        this.applyPreview(this.currentBlobUrl, 'Image sélectionnée.');
    }

    clearLocalFile() {
        this.revokeCurrentBlobUrl();
        this.applyFallback();
    }

    // Callback Stimulus automatique : se déclenche à la connexion ET
    // à chaque fois que le serveur met à jour fallbackUrl via un re-rendu.
    // L'aperçu et la légende portent data-live-ignore : après le premier rendu,
    // seul ce contrôleur les modifie, et un fichier local choisi reste prioritaire
    fallbackUrlValueChanged() {
        if (this.currentBlobUrl) {
            return;
        }
        this.applyFallback();
    }

    applyFallback() {
        if (this.fallbackUrlValue) {
            this.applyPreview(this.fallbackUrlValue, this.fallbackCaptionValue);
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