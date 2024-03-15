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
import { clearState, getNextcloudHeader } from '../../support/commonUtils'

// eslint-disable-next-line n/no-extraneous-import
import randomString from 'crypto-random-string'

const admin = new User('admin', 'admin')

const getContactsMenu = () => getNextcloudHeader().find('#header-menu-contactsmenu')
const getContactsMenuToggle = () => getNextcloudHeader().find('#contactsmenu .header-menu__trigger')
const getContactsSearch = () => getContactsMenu().find('#contactsmenu__menu__search')

describe('Header: Contacts menu', { testIsolation: true }, () => {
	let user: User

	beforeEach(() => {
		// clear user and group state
		clearState()
		// ensure the contacts menu is not restricted
		cy.runOccCommand('config:app:set --value no core shareapi_restrict_user_enumeration_to_group')
		// create a new user for testing the contacts
		cy.createRandomUser().then(($user) => {
			user = $user
		})

		// Given I am logged in as the admin
		cy.login(admin)
		cy.visit('/')
	})

	it('Other users are seen in the contacts menu', () => {
		// When I open the Contacts menu
		getContactsMenuToggle().click()
		// I see that the Contacts menu is shown
		getContactsMenu().should('exist')
		// I see that the contact user in the Contacts menu is shown
		getContactsMenu().contains('li.contact', user.userId).should('be.visible')
		// I see that the contact "admin" in the Contacts menu is not shown
		getContactsMenu().contains('li.contact', admin.userId).should('not.exist')
	})

	it('Just added users are seen in the contacts menu', () => {
		// I create a new user
		const newUserName = randomString(7)
		// we can not use createRandomUser as it will invalidate the session
		cy.runOccCommand(`user:add --password-from-env '${newUserName}'`, { env: { OC_PASS: '1234567' } })
		// I open the Contacts menu
		getContactsMenuToggle().click()
		// I see that the Contacts menu is shown
		getContactsMenu().should('exist')
		// I see that the contact user in the Contacts menu is shown
		getContactsMenu().contains('li.contact', user.userId).should('be.visible')
		// I see that the contact of the new user in the Contacts menu is shown
		getContactsMenu().contains('li.contact', newUserName).should('be.visible')
		// I see that the contact "admin" in the Contacts menu is not shown
		getContactsMenu().contains('li.contact', admin.userId).should('not.exist')
	})

	it('Search for other users in the contacts menu', () => {
		cy.createRandomUser().then((otherUser) => {
			// Given I am logged in as the admin
			cy.login(admin)
			cy.visit('/')

			// I open the Contacts menu
			getContactsMenuToggle().click()
			// I see that the Contacts menu is shown
			getContactsMenu().should('exist')
			// I see that the contact user in the Contacts menu is shown
			getContactsMenu().contains('li.contact', user.userId).should('be.visible')
			// I see that the contact of the new user in the Contacts menu is shown
			getContactsMenu().contains('li.contact', otherUser.userId).should('be.visible')

			// I see that the Contacts menu search input is shown
			getContactsSearch().should('exist')
			// I search for the otherUser
			getContactsSearch().type(otherUser.userId)
			// I see that the contact otherUser in the Contacts menu is shown
			getContactsMenu().contains('li.contact', otherUser.userId).should('be.visible')
			// I see that the contact user in the Contacts menu is not shown
			getContactsMenu().contains('li.contact', user.userId).should('not.exist')
			// I see that the contact "admin" in the Contacts menu is not shown
			getContactsMenu().contains('li.contact', admin.userId).should('not.exist')
		})
	})

	it('Search for unknown users in the contacts menu', () => {
		// I open the Contacts menu
		getContactsMenuToggle().click()
		// I see that the Contacts menu is shown
		getContactsMenu().should('exist')
		// I see that the contact user in the Contacts menu is shown
		getContactsMenu().contains('li.contact', user.userId).should('be.visible')

		// I see that the Contacts menu search input is shown
		getContactsSearch().should('exist')
		// I search for an unknown user
		getContactsSearch().type('surely-unknown-user')
		// I see that the no results message in the Contacts menu is shown
		getContactsMenu().find('ul li').should('have.length', 0)
		// I see that the contact user in the Contacts menu is not shown
		getContactsMenu().contains('li.contact', user.userId).should('not.exist')
		// I see that the contact "admin" in the Contacts menu is not shown
		getContactsMenu().contains('li.contact', admin.userId).should('not.exist')
	})

	it('Users from other groups are not seen in the contacts menu when autocompletion is restricted within the same group', () => {
		// I enable restricting username autocompletion to groups
		cy.runOccCommand('config:app:set --value yes core shareapi_restrict_user_enumeration_to_group')
		// I open the Contacts menu
		getContactsMenuToggle().click()
		// I see that the Contacts menu is shown
		getContactsMenu().should('exist')
		// I see that the contact user in the Contacts menu is not shown
		getContactsMenu().contains('li.contact', user.userId).should('not.exist')
		// I see that the contact "admin" in the Contacts menu is not shown
		getContactsMenu().contains('li.contact', admin.userId).should('not.exist')

		// I close the Contacts menu
		getContactsMenuToggle().click()
		// I disable restricting username autocompletion to groups
		cy.runOccCommand('config:app:set --value no core shareapi_restrict_user_enumeration_to_group')
		// I open the Contacts menu
		getContactsMenuToggle().click()
		// I see that the Contacts menu is shown
		getContactsMenu().should('exist')
		// I see that the contact user in the Contacts menu is shown
		getContactsMenu().contains('li.contact', user.userId).should('be.visible')
		// I see that the contact "admin" in the Contacts menu is not shown
		getContactsMenu().contains('li.contact', admin.userId).should('not.exist')
	})
})
