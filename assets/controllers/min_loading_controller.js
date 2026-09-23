import { Controller } from '@hotwired/stimulus'
import { getComponent } from '@symfony/ux-live-component'

/*
 * Keeps `data-searching` on this element for at least `duration` ms per request, so
 * placeholders styled with `group-data-searching:` stay long enough to be seen.
 * `data-loading` cannot do this: its `delay` only postpones, and it drops the change
 * entirely when the response is faster than the delay.
 */
export default class extends Controller {
    static values = { duration: { type: Number, default: 500 } }

    async initialize() {
        this.component = await getComponent(this.element.closest('[data-controller~="live"]'))
        this.onStart = () => this.start()
        this.onFinish = () => this.finish()
        this.component.on('loading.state:started', this.onStart)
        this.component.on('render:finished', this.onFinish)
    }

    disconnect() {
        clearTimeout(this.timeout)
        this.component?.off('loading.state:started', this.onStart)
        this.component?.off('render:finished', this.onFinish)
    }

    start() {
        clearTimeout(this.timeout)
        this.startedAt = Date.now()
        this.element.setAttribute('data-searching', '')
    }

    finish() {
        const remaining = this.durationValue - (Date.now() - this.startedAt)
        this.timeout = setTimeout(() => this.element.removeAttribute('data-searching'), Math.max(0, remaining))
    }
}
