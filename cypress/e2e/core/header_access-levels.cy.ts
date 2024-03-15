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
import { clearState, getNextcloudUserMenu, getNextcloudUserMenuToggle } from '../../support/commonUtils'

const admin = new User('admin', 'admin')

describe('Header: Ensure regular users do not have admin settings in the Settings menu', { testIsolation: true }, () => {
	beforeEach(() => {
		clearState()
	})

	it('Regular users can see basic items in the Settings menu', () => {
		// Given I am logged in
		cy.createRandomUser().then(($user) => {
			cy.login($user)
			cy.visit('/')
		})
		// I open the settings menu
		getNextcloudUserMenuToggle().click()

		getNextcloudUserMenu().find('ul').within(($el) => {
			// I see the settings menu is open
			cy.wrap($el).should('be.visible')

			// I see that the Settings menu has only 6 items
			cy.get('li').should('have.length', 6)
			// I see that the "View profile" item in the Settings menu is shown
			cy.contains('li', 'View profile').should('be.visible')
			// I see that the "Set status" item in the Settings menu is shown
			cy.contains('li', 'Set status').should('be.visible')
			// I see that the "Appearance and accessibility" item in the Settings menu is shown
			cy.contains('li', 'Appearance and accessibility').should('be.visible')
			// I see that the "Settings" item in the Settings menu is shown
			cy.contains('li', 'Settings').should('be.visible')
			// I see that the "Help" item in the Settings menu is shown
			cy.contains('li', 'Help').should('be.visible')
			// I see that the "Log out" item in the Settings menu is shown
			cy.contains('li', 'Log out').should('be.visible')
		})
	})

	it('Regular users cannot see admin-level items in the Settings menu', () => {
		// Given I am logged in
		cy.createRandomUser().then(($user) => {
			cy.login($user)
			cy.visit('/')
		})
		// I open the settings menu
		getNextcloudUserMenuToggle().click()

		getNextcloudUserMenu().find('ul').within(($el) => {
			// I see the settings menu is open
			cy.wrap($el).should('be.visible')

			// I see that the "Users" item in the Settings menu is NOT shown
			cy.contains('li', 'Users').should('not.exist')
			// I see that the "Administration settings" item in the Settings menu is NOT shown
			cy.contains('li', 'Administration settings').should('not.exist')
			cy.get('#admin_settings').should('not.exist')
		})
	})

	it('Admin users can see admin-level items in the Settings menu', () => {
		// Given I am logged in
		cy.login(admin)
		cy.visit('/')

		// I open the settings menu
		getNextcloudUserMenuToggle().click()

		getNextcloudUserMenu().find('ul').within(($el) => {
			// I see the settings menu is open
			cy.wrap($el).should('be.visible')

			// I see that the Settings menu has only 9 items
			cy.get('li').should('have.length', 9)
			// I see that the "Set status" item in the Settings menu is shown
			cy.contains('li', 'View profile').should('be.visible')
			// I see that the "Set status" item in the Settings menu is shown
			cy.contains('li', 'Set status').should('be.visible')
			// I see that the "Appearance and accessibility" item in the Settings menu is shown
			cy.contains('li', 'Appearance and accessibility').should('be.visible')
			// I see that the "Personal Settings" item in the Settings menu is shown
			cy.contains('li', 'Personal settings').should('be.visible')
			// I see that the "Administration settings" item in the Settings menu is shown
			cy.contains('li', 'Administration settings').should('be.visible')
			// I see that the "Apps" item in the Settings menu is shown
			cy.contains('li', 'Apps').should('be.visible')
			// I see that the "Users" item in the Settings menu is shown
			cy.contains('li', 'Users').should('be.visible')
			// I see that the "Help" item in the Settings menu is shown
			cy.contains('li', 'Help').should('be.visible')
			// I see that the "Log out" item in the Settings menu is shown
			cy.contains('li', 'Log out').should('be.visible')
		})
	})
})
