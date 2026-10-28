import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button', 'favoriteIcon', 'notFavoriteIcon'];

    static values = {
        id: Number,
        isFavorite: Boolean,
        token: String,
    };

    addFavorite() {

        const url = this.isFavoriteValue
            ? `/gift/${this.idValue}/favorite/remove`
            : `/gift/${this.idValue}/favorite`;

        fetch(url, {
            method: 'POST',
            body: new URLSearchParams({ _token: this.tokenValue }),
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur serveur');
                }

                return response.json();
            })
            .then(() => {
                this.isFavoriteValue = !this.isFavoriteValue;
                this.updateIcon();
            })
            .catch(error => console.error(error));
    }

    updateIcon() {
        this.favoriteIconTarget.classList.toggle(
            'hidden',
            !this.isFavoriteValue
        )
       
        this.notFavoriteIconTarget.classList.toggle(
            'hidden',
            this.isFavoriteValue
        )

        this.buttonTarget.setAttribute('aria-pressed', String(this.isFavoriteValue));
        this.buttonTarget.setAttribute(
            'aria-label',
            this.isFavoriteValue
                ? 'Retirer ce cadeau de mes favoris'
                : 'Ajouter ce cadeau à mes favoris'
        );

        if (this.isFavoriteValue) {
            this.playPopAnimation(this.favoriteIconTarget);
        }
    }
    
    playPopAnimation(target) {
        const animClass = '[animation:favorite-pop_0.4s_ease-out]';
        target.classList.remove(animClass);
        void target.offsetWidth;
        target.classList.add(animClass);
    }
}