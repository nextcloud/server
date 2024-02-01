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

describe('Settings: Ensure only administrator can see the administration settings section', { testIsolation: true }, () => {
	beforeEach(() => {
		clearState()
	})

	it('Regular users cannot see admin-level items on the Settings page', () => {
		// Given I am logged in
		cy.createRandomUser().then(($user) => {
			cy.login($user)
			cy.visit('/')
		})

		// I open the settings menu
		getNextcloudUserMenuToggle().click()
		// I navigate to the settings panel
		getNextcloudUserMenu().find('#settings a').click()
		cy.url().should('match', /\/settings\/user$/)

		cy.get('#app-navigation').should('be.visible').within(() => {
			// I see the personal section is NOT shown
			cy.get('#app-navigation-caption-personal').should('not.exist')
			// I see the admin section is NOT shown
			cy.get('#app-navigation-caption-administration').should('not.exist')

			// I see that the "Personal info" entry in the settings panel is shown
			cy.get('[data-section-id="personal-info"]').should('exist').and('be.visible')
		})
	})

	it('Admin users can see admin-level items on the Settings page', () => {
		// Given I am logged in
		cy.login(admin)
		cy.visit('/')

		// I open the settings menu
		getNextcloudUserMenuToggle().click()
		// I navigate to the settings panel
		getNextcloudUserMenu().find('#settings a').click()
		cy.url().should('match', /\/settings\/user$/)

		cy.get('#app-navigation').should('be.visible').within(() => {
			// I see the personal section is shown
			cy.get('#app-navigation-caption-personal').should('be.visible')
			// I see the admin section is shown
			cy.get('#app-navigation-caption-administration').should('be.visible')

			// I see that the "Personal info" entry in the settings panel is shown
			cy.get('[data-section-id="personal-info"]').should('exist').and('be.visible')
		})
	})
})
