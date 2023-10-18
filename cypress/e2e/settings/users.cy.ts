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
/// <reference types="cypress-if" />
import { User } from '@nextcloud/cypress'

const admin = new User('admin', 'admin')
const jdoe = new User('jdoe', 'jdoe')
const john = new User('john', '123456')

describe('Settings: Create and delete users', function() {
	before(function() {
		cy.login(admin)
		// open the User settings
		cy.visit('/settings/users')
	})

	beforeEach(function() {
		cy.login(admin)
		cy.listUsers().then((users) => {
			cy.login(admin)
			if ((users as string[]).includes('john')) {
				// ensure created user is deleted
				cy.deleteUser(john).login(admin)
				// ensure deleted user is not present
				cy.reload().login(admin)
			}
		})
	})

	it('Can create a user', function() {
		// open the New user modal
		cy.get('button#new-user-button').click()

		cy.get('form[data-test="form"]').within(() => {
			// see that the username is ""
			cy.get('input[data-test="username"]').should('exist').and('have.value', '')
			// set the username to john
			cy.get('input[data-test="username"]').type('john')
			// see that the username is john
			cy.get('input[data-test="username"]').should('have.value', 'john')
			// see that the password is ""
			cy.get('input[type="password"]').should('exist').and('have.value', '')
			// set the password to 123456
			cy.get('input[type="password"]').type('123456')
			// see that the password is 123456
			cy.get('input[type="password"]').should('have.value', '123456')
			// submit the new user form
			cy.get('button[type="submit"]').click()
		})

		// Make sure no confirmation modal is shown
		handlePasswordConfirmation(admin.password)

		// see that the created user is in the list
		cy.get('tbody.user-list__body tr[data-test="john"]').within(() => {
			// see that the list of users contains the user john
			cy.contains('john').should('exist')
		})
	})

	it('Can create a user with additional field data', function() {
		// open the New user modal
		cy.get('button#new-user-button').click()

		cy.get('form[data-test="form"]').within(() => {
			// set the username
			cy.get('input[data-test="username"]').should('exist').and('have.value', '')
			cy.get('input[data-test="username"]').type('john')
			cy.get('input[data-test="username"]').should('have.value', 'john')
			// set the display name
			cy.get('input[data-test="displayName"]').should('exist').and('have.value', '')
			cy.get('input[data-test="displayName"]').type('John Smith')
			cy.get('input[data-test="displayName"]').should('have.value', 'John Smith')
			// set the email
			cy.get('input[data-test="email"]').should('exist').and('have.value', '')
			cy.get('input[data-test="email"]').type('john@example.org')
			cy.get('input[data-test="email"]').should('have.value', 'john@example.org')
			// set the password
			cy.get('input[type="password"]').should('exist').and('have.value', '')
			cy.get('input[type="password"]').type('123456')
			cy.get('input[type="password"]').should('have.value', '123456')
			// submit the new user form
			cy.get('button[type="submit"]').click()
		})

		// Make sure no confirmation modal is shown
		handlePasswordConfirmation(admin.password)

		// see that the created user is in the list
		cy.get('tbody.user-list__body tr[data-test="john"]').within(() => {
			// see that the list of users contains the user john
			cy.contains('john').should('exist')
		})
	})

	it('Can delete a user', function() {
		// create user
		cy.createUser(jdoe).login(admin)
		// ensure created user is present
		cy.reload().login(admin)

		// see that the user is in the list
		cy.get(`tbody.user-list__body tr[data-test="${jdoe.userId}"]`).within(() => {
			// see that the list of users contains the user jdoe
			cy.contains(jdoe.userId).should('exist')
			// open the actions menu for the user
			cy.get('td.row__cell--actions button.action-item__menutoggle').click()
		})

		// The "Delete user" action in the actions menu is shown and clicked
		cy.get('.action-item__popper .action').contains('Delete user').should('exist').click()
		// And confirmation dialog accepted
		cy.get('.oc-dialog button').contains(`Delete ${jdoe.userId}`).click()

		// Make sure no confirmation modal is shown
		handlePasswordConfirmation(admin.password)

		// deleted clicked the user is not shown anymore
		cy.get(`tbody.user-list__body tr[data-test="${jdoe.userId}"]`).should('not.exist')
	})
})
