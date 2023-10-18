/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
import { handlePasswordConfirmation } from './usersUtils'

const admin = new User('admin', 'admin')

describe('Settings: Create and delete groups', () => {
	before(() => {
		cy.login(admin)
		// open the User settings
		cy.visit('/settings/users')
	})

	it('Can create a group', () => {
		// open the Create group menu
		cy.get('button[aria-label="Create group"]').click()

		cy.get('.action-item__popper ul[role="menu"]').within(() => {
			// see that the group name is ""
			cy.get('input[placeholder="Group name"]').should('exist').and('have.value', '')
			// set the group name to foo
			cy.get('input[placeholder="Group name"]').type('foo')
			// see that the group name is foo
			cy.get('input[placeholder="Group name"]').should('have.value', 'foo')
			// submit the group name
			cy.get('input[placeholder="Group name"] ~ button').click()
		})

		// Make sure no confirmation modal is shown
		handlePasswordConfirmation(admin.password)

		// see that the created group is in the list
		cy.get('ul.app-navigation__list').within(() => {
			// see that the list of groups contains the group foo
			cy.contains('foo').should('exist')
		})
	})

	it('Can delete a group', () => {
		// see that the group is in the list
		cy.get('ul.app-navigation__list').within(() => {
			// see that the list of groups contains the group foo
			cy.contains('foo').should('exist')
			// open the actions menu for the group
			cy.contains('li', 'foo').within(() => {
				cy.get('button.action-item__menutoggle').click()
			})
		})

		// The "Remove group" action in the actions menu is shown and clicked
		cy.get('.action-item__popper button').contains('Remove group').should('exist').click()
		// And confirmation dialog accepted
		cy.get('.modal-container button').contains('Confirm').click()

		// Make sure no confirmation modal is shown
		cy.get('body').contains('.modal-container', 'Confirm your password')
			.if('visible')
			.then(($modal) => {
				cy.wrap($modal).find('input[type="password"]').type(admin.password)
				cy.wrap($modal).find('button').contains('Confirm').click()
			})

		// deleted group is not shown anymore
		cy.get('ul.app-navigation__list').within(() => {
			// see that the list of groups does not contain the group foo
			cy.contains('foo').should('not.exist')
		})
	})
})
