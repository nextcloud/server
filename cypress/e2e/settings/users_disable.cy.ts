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
import { getUserListRow } from './usersUtils'
import { clearState } from '../../support/commonUtils'

const admin = new User('admin', 'admin')

describe('Settings: Disable and enable users', function() {
	let testUser: User

	beforeEach(function() {
		clearState()
		cy.createRandomUser().then(($user) => {
			testUser = $user
		})
		cy.login(admin)
		// open the User settings
		cy.visit('/settings/users')
	})

	// Not guranteed to run but would be nice to cleanup
	after(() => {
		cy.deleteUser(testUser)
	})

	it('Can disable the user', function() {
		// ensure user is enabled
		cy.enableUser(testUser)

		// see that the user is in the list of active users
		getUserListRow(testUser.userId).within(() => {
			// see that the list of users contains the user testUser
			cy.contains(testUser.userId).should('exist')
			// open the actions menu for the user
			cy.get('[data-cy-user-list-cell-actions] button.action-item__menutoggle').click({ scrollBehavior: 'center' })
		})

		// The "Disable user" action in the actions menu is shown and clicked
		cy.get('.action-item__popper .action').contains('Disable user').should('exist').click()
		// When clicked the section is not shown anymore
		getUserListRow(testUser.userId).should('not.exist')
		// But the disabled user section now exists
		cy.get('#disabled').should('exist')
		// Open disabled users section
		cy.get('#disabled a').click()
		cy.url().should('match', /\/disabled/)
		// The list of disabled users should now contain the user
		getUserListRow(testUser.userId).should('exist')
	})

	it('Can enable the user', function() {
		// ensure user is disabled
		cy.enableUser(testUser, false).reload()

		// Open disabled users section
		cy.get('#disabled a').click()
		cy.url().should('match', /\/disabled/)

		// see that the user is in the list of active users
		getUserListRow(testUser.userId).within(() => {
			// see that the list of disabled users contains the user testUser
			cy.contains(testUser.userId).should('exist')
			// open the actions menu for the user
			cy.get('[data-cy-user-list-cell-actions] button.action-item__menutoggle').click({ scrollBehavior: 'center' })
		})

		// The "Enable user" action in the actions menu is shown and clicked
		cy.get('.action-item__popper .action').contains('Enable user').should('exist').click()
		// When clicked the section is not shown anymore
		cy.get('#disabled').should('not.exist')
		// Make sure it is still gone after the reload reload
		cy.reload().login(admin)
		cy.get('#disabled').should('not.exist')
	})
})
