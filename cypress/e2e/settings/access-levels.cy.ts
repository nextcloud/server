/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import { clearState, getNextcloudUserMenu, getNextcloudUserMenuToggle } from '../../support/commonUtils.ts'

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

		cy.findAllByRole('navigation')
			.filter('#app-navigation-vue')
			.as('appNavigation')
			.findByRole('list', { name: 'Personal' })
			.should('be.visible')
			.findByRole('link', { name: /Personal info/i })
			.should('be.visible')
			.and('have.attr', 'aria-current', 'page')

		cy.get('@appNavigation')
			.findByRole('list', { name: 'Administration' })
			.should('not.exist')
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

		cy.findAllByRole('navigation')
			.filter('#app-navigation-vue')
			.as('appNavigation')
			.findByRole('list', { name: 'Personal' })
			.should('be.visible')
			.findByRole('link', { name: /Personal info/i })
			.should('be.visible')
			.and('have.attr', 'aria-current', 'page')

		cy.get('@appNavigation')
			.findByRole('list', { name: 'Administration' })
			.should('be.visible')
			.findByRole('link', { name: /Overview/i })
			.should('be.visible')
	})
})
