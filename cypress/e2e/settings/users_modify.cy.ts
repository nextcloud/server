/**
 * @copyright Copyright (c) 2023 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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

import { User } from '@nextcloud/cypress'
import { getUserListRow, handlePasswordConfirmation } from './usersUtils'

const admin = new User('admin', 'admin')
const jdoe = new User('jdoe', 'jdoe')

describe('Settings: Change user properties', function() {
	before(function() {
		cy.createUser(jdoe)
		cy.login(admin)
		// open the User settings
		cy.visit('/settings/users')
	})

	beforeEach(function() {
		// reset to read-only mode: try to find the edit button and click it if set to editing
		getUserListRow(jdoe.userId)
			.find('[data-test-id="cell-actions"]')
			// replace with following (more error resilent) with nextcloud-vue 8
			// find('[data-test-id="button-toggleEdit"][data-test="true"]')
			.find('button[aria-label="Done"]')
			.if()
			.click({ force: true })
	})

	after(() => {
		cy.deleteUser(jdoe)
	})

	it('Can change the display name', function() {
		// see that the list of users contains the user jdoe
		getUserListRow(jdoe.userId).should('exist')
			// toggle the edit mode for the user jdoe
			.find('[data-test-id="cell-actions"]')
			.find('button[aria-label="Edit"]')
			// replace with following (more error resilent) with nextcloud-vue 8
			// find('[data-test-id="button-toggleEdit"]')
			.click({ force: true })

		getUserListRow(jdoe.userId).within(() => {
			// set the display name
			cy.get('input[data-test-id="input-displayName"]').should('exist').and('have.value', 'jdoe')
			cy.get('input[data-test-id="input-displayName"]').clear()
			cy.get('input[data-test-id="input-displayName"]').type('John Doe')
			cy.get('input[data-test-id="input-displayName"]').should('have.value', 'John Doe')
			cy.get('input[data-test-id="input-displayName"] ~ button').click()

			// Make sure no confirmation modal is shown
			handlePasswordConfirmation(admin.password)

			// see that the display name cell is done loading
			cy.get('[data-test-id="input-displayName"]').should('have.attr', 'data-test-loading', 'true')
			cy.waitUntil(() => cy.get('[data-test-id="input-displayName"]').should('have.attr', 'data-test-loading', 'false'), { timeout: 10000 })
		})
		// Success message is shown
		cy.get('.toastify.toast-success').contains(/Display.+name.+was.+successfully.+changed/i).should('exist')
	})

	it('Can change the password', function() {
		// see that the list of users contains the user jdoe
		getUserListRow(jdoe.userId).should('exist')
			// toggle the edit mode for the user jdoe
			.find('[data-test-id="cell-actions"]')
			.find('button[aria-label="Edit"]')
			// replace with following (more error resilent) with nextcloud-vue 8
			// find('[data-test-id="button-toggleEdit"]')
			.click({ force: true })

		getUserListRow(jdoe.userId).within(() => {
			// see that the password of user0 is ""
			cy.get('input[type="password"]').should('exist').and('have.value', '')
			// set the password for user0 to 123456
			cy.get('input[type="password"]').type('123456')
			// When I set the password for user0 to 123456
			cy.get('input[type="password"]').should('have.value', '123456')
			cy.get('input[type="password"] ~ button').click()

			// Make sure no confirmation modal is shown
			handlePasswordConfirmation(admin.password)

			// see that the password cell for user user0 is done loading
			cy.get('[data-test-id="input-password"]').should('have.attr', 'data-test-loading', 'true')
			cy.waitUntil(() => cy.get('[data-test-id="input-password"]').should('have.attr', 'data-test-loading', 'false'), { timeout: 10000 })
			// password input is emptied on change
			cy.get('[data-test-id="input-password"]').should('have.value', '')
		})
		// Success message is shown
		cy.get('.toastify.toast-success').contains(/Password.+successfully.+changed/i).should('exist')
	})
})
