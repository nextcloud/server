/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
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
			oldMounted()
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