/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import '@testing-library/cypress/add-commands'
import 'cypress-axe'

// styles
import '../../apps/theming/css/default.css'
import '../../core/css/server.css'

/* eslint-disable */
import { mount } from '@cypress/vue2'

Cypress.Commands.add('mount', (component, options = {}) => {
	// Setup options object
	options.extensions = options.extensions || {}
	options.extensions.plugins = options.extensions.plugins || []
	options.extensions.components = options.extensions.components || {}

	return mount(component, options)
})

Cypress.Commands.add('mockInitialState', (app: string, key: string, value: unknown) => {
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
