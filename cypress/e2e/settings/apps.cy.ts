/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/cypress'
import { handlePasswordConfirmation } from './usersUtils'

const admin = new User('admin', 'admin')

describe('Settings: App management', { testIsolation: true }, () => {
	beforeEach(() => {
		// disable QA if already enabled
		cy.runOccCommand('app:disable -n testing')
		// enable notification if already disabled
		cy.runOccCommand('app:enable -n updatenotification')

		// I am logged in as the admin
		cy.login(admin)

		// Intercept the apps list request
		cy.intercept('GET', '*/settings/apps/list').as('fetchAppsList')

		// I open the Apps management
		cy.visit('/settings/apps/installed')

		// Wait for the apps list to load
		cy.wait('@fetchAppsList')
	})

	it('Can enable an installed app', () => {
		cy.get('#apps-list').should('exist')
			// Wait for the app list to load
			.contains('tr', 'QA testing', { timeout: 10000 })
			.should('exist')
			// I enable the "QA testing" app
			.contains('button', 'Enable')
			.click({ force: true })

		handlePasswordConfirmation(admin.password)

		// Wait until we see the disable button for the app
		cy.get('#apps-list').should('exist')
			.contains('tr', 'QA testing')
			.should('exist')
			// I see the disable button for the app
			.contains('button', 'Disable', { timeout: 10000 })

		// Change to enabled apps view
		cy.get('#app-category-enabled a').click({ force: true })
		cy.url().should('match', /settings\/apps\/enabled$/)
		// I see that the "QA testing" app has been enabled
		cy.get('#apps-list').contains('tr', 'QA testing')
	})

	it('Can disable an installed app', () => {
		cy.get('#apps-list')
			.should('exist')
			// Wait for the app list to load
			.contains('tr', 'Update notification', { timeout: 10000 })
			.should('exist')
			// I disable the "Update notification" app
			.contains('button', 'Disable')
			.click({ force: true })

		handlePasswordConfirmation(admin.password)

		// Wait until we see the disable button for the app
		cy.get('#apps-list').should('exist')
			.contains('tr', 'Update notification')
			.should('exist')
			// I see the enable button for the app
			.contains('button', 'Enable', { timeout: 10000 })

		// Change to disabled apps view
		cy.get('#app-category-disabled a').click({ force: true })
		cy.url().should('match', /settings\/apps\/disabled$/)
		// I see that the "Update notification" app has been disabled
		cy.get('#apps-list').contains('tr', 'Update notification')
	})

	it('Browse enabled apps', () => {
		// When I open the "Active apps" section
		cy.get('#app-category-enabled a')
			.should('contain', 'Active apps')
			.click({ force: true })
		// Then I see that the current section is "Active apps"
		cy.url().should('match', /settings\/apps\/enabled$/)
		cy.get('#app-category-enabled').find('.active').should('exist')
		// I see that there are only enabled apps
		cy.get('#apps-list')
			.should('exist')
			.find('tr button')
			.each(($action) => {
				cy.wrap($action).should('not.contain', 'Enable')
			})
	})

	it('Browse disabled apps', () => {
		// When I open the "Active apps" section
		cy.get('#app-category-disabled a')
			.should('contain', 'Disabled apps')
			.click({ force: true })
		// Then I see that the current section is "Active apps"
		cy.url().should('match', /settings\/apps\/disabled$/)
		cy.get('#app-category-disabled').find('.active').should('exist')
		// I see that there are only disabled apps
		cy.get('#apps-list')
			.should('exist')
			.find('tr button')
			.each(($action) => {
				cy.wrap($action).should('not.contain', 'Disable')
			})
	})

	it('Browse app bundles', () => {
		// When I open the "App bundles" section
		cy.get('#app-category-your-bundles a')
			.should('contain', 'App bundles')
			.click({ force: true })
		// Then I see that the current section is "App bundles"
		cy.url().should('match', /settings\/apps\/app-bundles$/)
		cy.get('#app-category-your-bundles').find('.active').should('exist')
		// I see the app bundles
		cy.get('#apps-list').contains('tr', 'Enterprise bundle')
		cy.get('#apps-list').contains('tr', 'Education bundle')
		// I see that the "Enterprise bundle" is disabled
		cy.get('#apps-list').contains('tr', 'Enterprise bundle').contains('button', 'Download and enable all')
	})

	it('View app details', () => {
		// When I click on the "QA testing" app
		cy.get('#apps-list').contains('a', 'QA testing').click({ force: true })
		// I see that the app details are shown
		cy.get('#app-sidebar-vue')
			.should('be.visible')
			.find('.app-sidebar-header__info')
			.should('contain', 'QA testing')
		cy.get('#app-sidebar-vue').contains('a', 'View in store').should('exist')
		cy.get('#app-sidebar-vue').find('input[type="button"][value="Enable"]').should('be.visible')
		cy.get('#app-sidebar-vue').find('input[type="button"][value="Remove"]').should('be.visible')
		cy.get('#app-sidebar-vue').contains(/Version \d+\.\d+\.\d+/).should('be.visible')
	})

	/*
	 * TODO: Improve testing with app store as external API
	 * The following scenarios require the files_antivirus and calendar app
	 * being present in the app store with support for the current server version
	 * Ideally we would have either a dummy app store endpoint with some test apps
	 * or even an app store instance running somewhere to properly test this.
	 * This is also a requirement to properly test updates of apps
	 */
	// TODO: View app details for app store apps
	// TODO: Install an app from the app store
	// TODO: Show section from app store
})
