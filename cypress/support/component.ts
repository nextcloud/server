/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import 'cypress-axe'

// styles
import '../../apps/theming/css/default.css'
import '../../core/css/server.css'

/* eslint-disable */
import { mount } from '@cypress/vue2'

// Example use:
// cy.mount(MyComponent)
Cypress.Commands.add('mount', (component, optionsOrProps) => {
	let instance = null
	const oldMounted = component?.mounted || false

	// Override the mounted method to expose
	// the component instance to cypress
	component.mounted = function() {
		// eslint-disable-next-line
		instance = this
		if (oldMounted) {
			oldMounted.call(instance)
		}
	}

	// Expose the component with cy.get('@component')
	return mount(component, optionsOrProps).then(() => {
		return cy.wrap(instance).as('component')
	})
})

Cypress.Commands.add('mockInitialState', (app: string, key: string, value: any) => {
	cy.document().then(($document) => {
		const input = $document.createElement('input')
		input.setAttribute('type', 'hidden')
		input.setAttribute('id', `initial-state-${app}-${key}`)
		input.setAttribute('value', btoa(JSON.stringify(value)))
		$document.body.appendChild(input)
	})
})

Cypress.Commands.add('unmockInitialState', (app?: string, key?: string) => {
	cy.document().then(($document) => {
		$document.querySelectorAll('body > input[type="hidden"]' + (app ? `[id="initial-state-${app}-${key}"]` : ''))
			.forEach((node) => $document.body.removeChild(node))
	})
})