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

describe('Settings: Create and delete users', function() {
	before(function() {
		cy.login(admin)
	})

	after(() => {
		cy.deleteUser(jdoe)
	})

	it('Can delete a user', function() {
		// ensure user exists
		cy.createUser(jdoe).login(admin)

		// open the User settings
		cy.visit('/settings/users')

		// see that the user is in the list
		cy.get(`tbody.user-list__body tr td[data-test="${jdoe.userId}"]`).parents('tr').within(() => {
			// see that the list of users contains the user jdoe
			cy.contains(jdoe.userId).should('exist')
			// open the actions menu for the user
			cy.get('td.row__cell--actions button.action-item__menutoggle').click()
		})

		// The "Delete user" action in the actions menu is shown and clicked
		cy.get('.action-item__popper .action').contains('Delete user').should('exist').click()
		// And confirmation dialog accepted
		cy.get('.oc-dialog button').contains(`Delete ${jdoe.userId}`).click()
		// deleted clicked the user is not shown anymore
		cy.get(`tbody.user-list__body tr td[data-test="${jdoe.userId}"]`).parents('tr').should('not.be.visible')
	})
})
