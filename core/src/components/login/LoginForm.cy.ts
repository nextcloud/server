/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import LoginForm from './LoginForm.vue'

describe('core: LoginForm', { testIsolation: true }, () => {
	beforeEach(() => {
		// Mock the required global state
		cy.window().then(($window) => {
			$window.OC = {
				theme: {
					name: 'J\'s cloud',
				},
				requestToken: 'request-token',
			}
		})
	})

	/**
	 * Ensure that characters like ' are not double HTML escaped.
	 * This was a bug in https://github.com/nextcloud/server/issues/34990
	 */
	it('does not double escape special characters in product name', () => {
		cy.mount(LoginForm, {
			propsData: {
				username: 'test-user',
			},
		})

		cy.get('h2').contains('J\'s cloud')
	})

	it('fills username from props into form', () => {
		cy.mount(LoginForm, {
			propsData: {
				username: 'test-user',
			},
		})

		cy.get('input[name="user"]')
			.should('exist')
			.and('have.attr', 'id', 'user')

		cy.get('input[name="user"]')
			.should('have.value', 'test-user')
	})

	it('clears password after timeout', () => {
		// mock timeout of 5 seconds
		cy.window().then(($window) => {
			const state = $window.document.createElement('input')
			state.type = 'hidden'
			state.id = 'initial-state-core-loginTimeout'
			state.value = btoa(JSON.stringify(5))
			$window.document.body.appendChild(state)
		})

		// mount forms
		cy.mount(LoginForm)

		cy.get('input[name="password"]')
			.should('exist')
			.type('MyPassword')

		cy.get('input[name="password"]')
			.should('have.value', 'MyPassword')

		// Wait for timeout
		// eslint-disable-next-line cypress/no-unnecessary-waiting
		cy.wait(5100)

		cy.get('input[name="password"]')
			.should('have.value', '')
	})
})
