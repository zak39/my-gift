import { Controller } from '@hotwired/stimulus';

/*
 * The navbar's theme switcher (Molecule:Navbar:ThemeSwitcher). A choice applies at once on
 * <html data-theme> and is kept in a cookie, which the server reads to render the next pages
 * (App\Services\ThemeCookie): no request, and no flash on the next load.
 *
 * Each radio carries its daisyUI theme ("emerald", "dark") in data-daisy-theme, empty for
 * "Système": <html> then carries no data-theme, and daisyUI follows prefers-color-scheme.
 */
export default class extends Controller {
    static targets = ['choice'];
    static values = { cookie: String, fallback: String };

    // The cookie is the one state all tabs share. <html>, which Turbo never swaps, may lag
    // behind it: a theme picked in another tab, a page restored from Turbo's cache. The other
    // tabs open meanwhile hear of a choice at once, through a channel named after the cookie.
    // Where the browser has no BroadcastChannel (Safari before 15.4), there is no channel: a
    // tab then catches up with the cookie at its next page.
    connect() {
        this.#show(this.#stored());

        if ('BroadcastChannel' in window) {
            this.channel = new BroadcastChannel(this.cookieValue);
            this.channel.onmessage = ({ data }) => this.#show(data);
        }
    }

    disconnect() {
        this.channel?.close();
    }

    choose({ target }) {
        this.#remember(target.value);
        this.channel?.postMessage(target.value);
        this.#fade(() => this.#show(target.value));
    }

    // theme: "light", "dark" or "system" (App\Enum\Theme). One the switcher does not offer, or
    // none, falls back as on the server.
    #show(theme) {
        const choice = this.choiceTargets.find((radio) => radio.value === theme)
            ?? this.choiceTargets.find((radio) => radio.value === this.fallbackValue);

        choice.checked = true;
        this.#apply(choice.dataset.daisyTheme);
    }

    // A cross-fade of the whole page (View Transitions API, timed in app.css) where the
    // browser has it and the visitor did not ask for less motion; otherwise a plain switch.
    #fade(change) {
        if (!document.startViewTransition || matchMedia('(prefers-reduced-motion: reduce)').matches) {
            change();

            return;
        }

        document.startViewTransition(change);
    }

    // Taken off rather than emptied: daisyUI's dark mode matches :root:not([data-theme]),
    // which data-theme="" would not.
    #apply(daisyTheme) {
        if (daisyTheme) {
            document.documentElement.dataset.theme = daisyTheme;
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    }

    #stored() {
        const prefix = `${this.cookieValue}=`;

        return document.cookie.split('; ').find((cookie) => cookie.startsWith(prefix))?.slice(prefix.length);
    }

    // Stored for a year, on every path: the server reads it on whichever page comes next.
    #remember(theme) {
        const secure = 'https:' === location.protocol ? '; Secure' : '';

        document.cookie = `${this.cookieValue}=${theme}; path=/; max-age=31536000; SameSite=Lax${secure}`;
    }
}
