class IsImpersonating extends HTMLElement {
	#debug = false

	constructor() {
		super();
	}

	setInnerHTML(impersonating) {
		this.#debug && console.log('is-impersonating.setInnerHTML', impersonating);

		if (!impersonating || impersonating === false) {
			this.innerHTML = '';
			return;
		}

		this.innerHTML = `
            <style>
                is-impersonating > div {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    gap: 0.5rem;
                    padding: 0.5rem;
                    background-color: var(--color-yellow-300);
                }
            </style>
			<div>
				<svg aria-hidden="true" data-type="impersonate" class="k-icon" style="opacity: .6"><use xlink:href="#icon-impersonate"></use></svg>
				<span>${impersonating}</span>
				<button class="k-button" data-has-icon="false" data-has-text="true" data-variant="filled" data-theme="warning" data-size="xs">Stop impersonating</button>
			</div>
		`;
		this.querySelector('button').addEventListener('click', async () => await this.stopImpersonating());
	}

	async update(email = null) {
		try {
			const impersonating = (email !== null)
				? email
				: (await window.panel?.api?.get('impersonate/status'))?.impersonating

			this.#debug && console.log('is-impersonating.update', impersonating);

			this.setInnerHTML(impersonating)
		} catch (error) {
			this.#debug && console.error('is-impersonating.update', error);
			this.setInnerHTML(false);
		}
	}

	async stopImpersonating() {
		await window.panel?.api?.post('impersonate/stop');
		window.location.reload();
	}

	async connectedCallback() {
		window.panel.events.on('popstate', async () => await this.update());
		window.panel.events.on('impersonate', () => window.location.reload());

		await this.update();
	}
}
window.customElements.define('is-impersonating', IsImpersonating);

window.panel.plugin('adamkiss/kirby-impersonate', {
    components: {
        'impersonation-emitter': {
            mixins: ['dialog'],
            template: '<div></div>',
            props: {
                event: String,
                payload: Object,
            },
            mounted() {
                this.$panel.events.emit('impersonate', this.payload)
                this.close()
            },
        }
    },
	created() {
		document.body.prepend(new IsImpersonating());
	},
	icons: {
		'impersonate': '<path fill="currentColor" d="M7.8 18q-1.275 0-2.437-.45t-2.088-1.325q-1.2-1.125-1.737-2.662T1 10.375q0-1.95.95-3.162T4.725 6q.35 0 .663.063t.637.187L12 8.475l5.975-2.225q.325-.125.638-.187T19.275 6Q21.1 6 22.05 7.213t.95 3.162q0 1.65-.537 3.188t-1.738 2.662q-.925.875-2.087 1.325T16.2 18q-1.65 0-2.8-.75l-1.15-.75h-.5l-1.15.75Q9.45 18 7.8 18m.925-4q.725 0 1.15-.337t.425-.913q0-.975-1.3-1.862T6.275 10q-.725 0-1.15.338t-.425.912q0 .975 1.3 1.863T8.725 14m6.55 0Q16.7 14 18 13.112t1.3-1.862q0-.6-.413-.925T17.726 10Q16.3 10 15 10.888t-1.3 1.862q0 .575.413.913t1.162.337"/>'
	}
})