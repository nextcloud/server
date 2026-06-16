/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import 'cypress-axe'
import './commands.ts'
// Remove with Node 22
// Ensure that we can use `Promise.withResolvers` - works in browser but on Node we need Node 22+
import 'core-js/actual/promise/with-resolvers.js'

// Fix ResizeObserver loop limit exceeded happening in Cypress only
// @see https://github.com/cypress-io/cypress/issues/20341
Cypress.on('uncaught:exception', (err) => !err.message.includes('ResizeObserver loop limit exceeded'))
Cypress.on('uncaught:exception', (err) => !err.message.includes('ResizeObserver loop completed with undelivered notifications'))

// Defer ResizeObserver callbacks one frame to break floating UI sync loops
// that otherwise tank the renderer and trigger Electron's unresponsive kill.
Cypress.on('window:before:load', (win) => {
	const Original = win.ResizeObserver
	win.ResizeObserver = class extends Original {
		constructor(callback: ResizeObserverCallback) {
			super((entries, observer) => {
				win.requestAnimationFrame(() => callback(entries, observer))
			})
		}
	}
})
