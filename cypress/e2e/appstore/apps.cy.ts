/*
 * SPDX-FileCopyrightText: 2023-2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import { handlePasswordConfirmation } from '../core-utils.ts'

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
		cy.intercept('GET', '/ocs/v2.php/apps/appstore/api/v1/apps').as('fetchAppsList')

		// I open the Apps management
		cy.visit('/settings/apps/installed')

		// Wait for the apps list to load
		cy.wait('@fetchAppsList')
	})

	it('Can enable an installed app', () => {
		cy.intercept('POST', '/ocs/v2.php/apps/appstore/api/v1/apps/enable').as('enableApp')

		cy.findByRole('table').should('exist')
			// Wait for the app list to load
			.contains('tr', 'QA testing', { timeout: 10000 })
			.should('exist')
			.findByRole('button', { name: 'Enable' })
			// I enable the "QA testing" app
			.click({ force: true })

		handlePasswordConfirmation(admin.password)

		cy.wait('@enableApp')

		// Wait until we see the disable button for the app
		cy.findByRole('table').should('exist')
			.contains('tr', 'QA testing')
			.should('exist')
			// I see the disable button for the app
			.findByRole('button', { name: 'Disable' })
			.should('be.visible')

		// Change to enabled apps view
		cy.findByRole('navigation', { name: 'Appstore categories' })
			.within(() => {
				cy.findByRole('link', { name: 'Active apps' })
					.should('be.visible')
					.click({ force: true })
			})

		cy.url().should('match', /settings\/apps\/enabled$/)
		// I see that the "QA testing" app has been enabled
		cy.findByRole('table')
			.contains('tr', 'QA testing')
	})

	it('Can disable an installed app', () => {
		cy.intercept('POST', '/ocs/v2.php/apps/appstore/api/v1/apps/disable').as('disableApp')

		cy.findByRole('table')
			.should('exist')
			// Wait for the app list to load
			.contains('tr', 'Update notification', { timeout: 10000 })
			.should('exist')
			// I disable the "Update notification" app
			.findByRole('button', { name: 'Disable' })
			.click({ force: true })

		handlePasswordConfirmation(admin.password)
		cy.wait('@disableApp')

		// Wait until we see the disable button for the app
		cy.findByRole('table').should('exist')
			.contains('tr', 'Update notification')
			.should('exist')
			// I see the enable button for the app
			.findByRole('button', { name: 'Enable' })
			.should('exist')

		// Change to disabled apps view
		cy.findByRole('navigation', { name: 'Appstore categories' })
			.within(() => {
				cy.findByRole('link', { name: 'Disabled apps' }).click({ force: true })
			})
		cy.url().should('match', /settings\/apps\/disabled$/)

		// I see that the "Update notification" app has been disabled
		cy.findByRole('table')
			.contains('tr', 'Update notification')
	})

	it('Browse enabled apps', () => {
		// When I open the "Active apps" section
		cy.findByRole('navigation', { name: 'Appstore categories' })
			.within(() => {
				cy.findByRole('link', { name: 'Active apps' })
					.should('be.visible')
					.click({ force: true })
			})

		// Then I see that the current section is "Active apps"
		cy.url().should('match', /settings\/apps\/enabled$/)
		cy.findByRole('navigation', { name: 'Appstore categories' })
			.within(() => {
				cy.findByRole('link', { name: 'Active apps', current: 'page' })
					.should('be.visible')
			})

		// I see that there are only enabled apps
		cy.findByRole('table')
			.should('exist')
			.find('tr button')
			.each(($action) => {
				cy.wrap($action).should('not.contain', 'Enable')
			})
	})

	it('Browse disabled apps', () => {
		// When I open the "Active Disabled" section
		cy.findByRole('navigation', { name: 'Appstore categories' })
			.within(() => {
				cy.findByRole('link', { name: 'Disabled apps' })
					.as('disabledAppsLink')
					.should('be.visible')
					.and('not.have.attr', 'aria-current')
				cy.get('@disabledAppsLink')
					.click({ force: true })
			})

		// Then I see that the current section is "Disabled apps"
		cy.url().should('match', /settings\/apps\/disabled$/)
		cy.findByRole('navigation', { name: 'Appstore categories' })
			.within(() => {
				cy.findByRole('link', { name: 'Disabled apps', current: 'page' })
					.should('be.visible')
			})

		// I see that there are only disabled apps
		cy.findByRole('table')
			.should('exist')
			.find('tr button')
			.each(($action) => {
				cy.wrap($action).should('not.contain', 'Disable')
			})
	})

	it('Browse app bundles', () => {
		// When I open the "App bundles" section
		cy.findByRole('navigation', { name: 'Appstore categories' })
			.within(() => {
				cy.findByRole('link', { name: 'App bundles' })
					.as('appBundlesLink')
					.should('be.visible')
					.and('not.have.attr', 'aria-current')
				cy.get('@appBundlesLink')
					.click({ force: true })
			})

		// Then I see that the current section is "App bundles"
		cy.url().should('match', /settings\/apps\/bundles$/)
		cy.findByRole('navigation', { name: 'Appstore categories' })
			.within(() => {
				cy.findByRole('link', { name: 'App bundles', current: 'page' })
					.should('be.visible')
			})

		// I see the app bundles
		cy.findByRole('heading', { name: 'Enterprise bundle' })
			.should('be.visible')
		cy.findByRole('heading', { name: 'Education bundle' })
			.should('be.visible')
	})

	it('View app details', () => {
		// When I click on the "QA testing" app
		cy.findByRole('table')
			.contains('a', 'QA testing')
			.click({ force: true })
		// I see that the app details are shown
		cy.get('#app-sidebar-vue')
			.should('be.visible')
			.find('.app-sidebar-header__info')
			.should('contain', 'QA testing')
		cy.get('#app-sidebar-vue').contains('a', 'View in store').should('exist')
		cy.get('#app-sidebar-vue')
			.findByRole('button', { name: 'Enable' })
			.should('be.visible')
		cy.get('#app-sidebar-vue')
			.findByRole('button', { name: 'Remove' })
			.should('be.visible')
		cy.get('#app-sidebar-vue').contains(/Version \d+\.\d+\.\d+/).should('be.visible')
	})

	it('Limit app usage to group', () => {
		// When I open the "Active apps" section
		cy.findByRole('navigation', { name: 'Appstore categories' })
			.within(() => {
				cy.findByRole('link', { name: 'Active apps' })
					.should('be.visible')
					.click({ force: true })
			})

		// Then I see that the current section is "Active apps"
		cy.url().should('match', /settings\/apps\/enabled$/)

		// Then I select the app
		cy.findByRole('table')
			.should('exist')
			.contains('tr a', 'Dashboard', { timeout: 10000 })
			.click()

		// Then I enable "limit app to group"
		cy.findByRole('button', { name: 'Limit to groups' })
			.click()

		// Then I select a group
		cy.findByRole('dialog')
			.should('be.visible')
			.within(() => {
				cy.get('input')
					.should('be.focused')
					.type('admin')
			})
		cy.findByRole('option', { name: /admin/ })
			.click()
		cy.findByRole('button', { name: 'Save' })
			.click()

		handlePasswordConfirmation(admin.password)

		cy.get('#app-sidebar-vue')
			.findByRole('list', { name: 'Limited to groups' })
			.findByRole('listitem', { name: /admin/ })
			.should('be.visible')

		// Then I disable the group limitation
		cy.get('#app-sidebar-vue')
			.findByRole('button', { name: 'Limit to groups' })
			.click()
		cy.findByRole('dialog')
			.should('be.visible')
			.within(() => {
				cy.findByRole('button', { name: 'Deselect admin' })
					.should('be.visible')
					.click()
				cy.findByRole('button', { name: 'Save' })
					.click()
			})

		handlePasswordConfirmation(admin.password)

		cy.get('#app-sidebar-vue')
			.findByRole('list', { name: 'Limited to groups' })
			.should('not.exist')
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
