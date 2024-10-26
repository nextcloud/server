/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		getNextcloudUserMenu()
			.findByRole('link', { name: /settings/i })
			.click()
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
		getNextcloudUserMenu()
			.findByRole('link', { name: /Personal settings/i })
			.click()
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
