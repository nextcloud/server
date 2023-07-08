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

const admin = new User('admin', 'admin')
const jdoe = new User('jdoe', 'jdoe')

describe('Settings: Change user properties', function() {
	before(function() {
		cy.createUser(jdoe)
		cy.login(admin)
	})

	after(() => {
		cy.deleteUser(jdoe)
	})

	it('Can change the password', function() {
		// open the User settings
		cy.visit('/settings/users')

		cy.get(`tbody.user-list__body tr td[data-test="${jdoe.userId}"]`).parents('tr').within(() => {
			// see that the list of users contains the user jdoe
			cy.contains(jdoe.userId).should('exist')
			// toggle the edit mode for the user jdoe
			cy.get('td.row__cell--actions .action-items > button:first-of-type').click()
		})

		cy.get(`tbody.user-list__body tr td[data-test="${jdoe.userId}"]`).parents('tr').within(() => {
			// see that the password of user0 is ""
			cy.get('input[type="password"]').should('exist').and('have.value', '')
			// set the password for user0 to 123456
			cy.get('input[type="password"]').type('123456')
			// When I set the password for user0 to 123456
			cy.get('input[type="password"]').should('have.value', '123456')
			cy.get('input[type="password"] ~ button').click()

			// Ignore failure if modal is not shown
			cy.once('fail', (error) => {
				expect(error.name).to.equal('AssertionError')
				expect(error).to.have.property('node', '.modal-container')
			})
			// Make sure no confirmation modal is shown
			cy.root().closest('body').find('.modal-container').then(($modal) => {
				if ($modal.length > 0) {
					cy.wrap($modal).find('input[type="password"]').type(admin.password)
					cy.wrap($modal).find('button').contains('Confirm').click()
				}
			})

			// see that the password cell for user user0 is done loading
			cy.get('.user-row-text-field.icon-loading-small').should('exist')
			cy.waitUntil(() => cy.get('.user-row-text-field.icon-loading-small').should('not.exist'), { timeout: 10000 })
			// password input is emptied on change
			cy.get('input[type="password"]').should('have.value', '')
		})
		// Success message is shown
		cy.get('.toastify.toast-success').contains(/Password.+successfully.+changed/i).should('exist')
	})
})
