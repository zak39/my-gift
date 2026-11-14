import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

/*
 * Turbo 8 fetches and renders the page again for every link, an anchor on the current page
 * included: slow, and whatever the user unfolded folds back. A link that only changes the
 * fragment is left to the browser, which scrolls at once: the skip link, the Sommaire of a
 * list, the tables of contents of the FAQ and the legal pages.
 */
document.addEventListener('turbo:click', (event) => {
    const url = new URL(event.detail.url);

    if (url.hash && url.pathname + url.search === window.location.pathname + window.location.search) {
        event.preventDefault();
    }
});

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
