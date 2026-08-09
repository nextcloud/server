/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import { NavigationHeader } from '../../pages/NavigationHeader.ts'

const admin = new User('admin', 'admin')

/**
 * Seed the global default-app config and open the theming settings on it.
 *
 * Every test establishes the state it needs itself: the tests mutate that
 * config, and `it` bodies are re-run alone on a retry, so inheriting the state
 * from the preceding test would make a single failure poison all attempts.
 *
 * @param defaultApps value for the `defaultapp` system config
 */
function visitSettingsWithDefaultApps(defaultApps: string) {
	cy.runOccCommand(`config:system:set defaultapp --value '${defaultApps}'`)
	cy.visit('/settings/admin/theming')
	getDefaultAppSwitch().scrollIntoView()
}

describe('Admin theming set default apps', () => {
	const navigationHeader = new NavigationHeader()

	before(function() {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.login(admin)
	})

	it('See the current default app is the dashboard', () => {
		cy.runOccCommand('config:system:set defaultapp --value \'\'')

		// check default route
		cy.visit('/')
		cy.url().should('match', /apps\/dashboard/)

		// Also check the top logo link
		navigationHeader.logo().click()
		cy.url().should('match', /apps\/dashboard/)
	})

	it('See the default app settings', () => {
		visitSettingsWithDefaultApps('')

		cy.get('.settings-section').contains('Navigation bar settings').should('exist')
		getDefaultAppSwitch().should('exist')
	})

	it('Toggle the "use custom default app" switch', () => {
		visitSettingsWithDefaultApps('')

		getDefaultAppSwitch().should('not.be.checked')
		cy.findByRole('region', { name: 'Global default app' })
			.should('not.exist')

		getDefaultAppSwitch().check({ force: true })
		getDefaultAppSwitch().should('be.checked')
		cy.findByRole('region', { name: 'Global default app' })
			.should('exist')
	})

	it('See the default app combobox', () => {
		visitSettingsWithDefaultApps('dashboard,files')

		cy.findByRole('region', { name: 'Global default app' })
			.should('exist')
			.findByRole('combobox')
			.scrollIntoView()

		// Assert the selected apps via their deselect buttons: `role="combobox"`
		// sits on the search input, which has no child nodes to search for the
		// app names in.
		cy.findByRole('region', { name: 'Global default app' })
			.findByRole('button', { name: 'Deselect Dashboard' })
			.should('be.visible')
		cy.findByRole('region', { name: 'Global default app' })
			.findByRole('button', { name: 'Deselect Files' })
			.should('be.visible')
	})

	it('See the default app order selector', () => {
		visitSettingsWithDefaultApps('dashboard,files')

		cy.findByRole('region', { name: 'Global default app' })
			.should('exist')
		cy.findByRole('list', { name: 'Navigation bar app order' })
			.should('exist')
			.findAllByRole('listitem')
			.should('have.length', 2)
			.then((elements) => {
				const appIDs = elements.map((idx, el) => el.innerText.trim()).get()
				expect(appIDs).to.deep.eq(['Dashboard', 'Files'])
			})
	})

	it('Change the default app', () => {
		visitSettingsWithDefaultApps('dashboard,files')

		cy.findByRole('list', { name: 'Navigation bar app order' })
			.should('exist')
			.as('appOrderSelector')
			.scrollIntoView()

		cy.get('@appOrderSelector')
			.findAllByRole('listitem')
			.filter((_, e) => !!e.innerText.match(/Files/i))
			.findByRole('button', { name: 'Move up' })
			.as('moveFilesUpButton')

		cy.get('@moveFilesUpButton').should('be.visible')
		cy.get('@moveFilesUpButton').click()
		cy.get('@moveFilesUpButton').should('not.exist')
	})

	it('See the default app is changed', () => {
		visitSettingsWithDefaultApps('files,dashboard')

		cy.findByRole('list', { name: 'Navigation bar app order' })
			.findAllByRole('listitem')
			.then((elements) => {
				const appIDs = elements.map((idx, el) => el.innerText.trim()).get()
				expect(appIDs).to.deep.eq(['Files', 'Dashboard'])
			})

		// Check the redirect to the default app works
		cy.request({ url: '/', followRedirect: false }).then((response) => {
			expect(response.status).to.eq(302)
			expect(response).to.have.property('headers')
			expect(response.headers.location).to.contain('/apps/files')
		})
	})

	it('Toggle the "use custom default app" switch back to reset the default apps', () => {
		visitSettingsWithDefaultApps('files,dashboard')

		getDefaultAppSwitch().should('be.checked')
		cy.intercept('PUT', '**/apps/theming/ajax/updateAppMenu').as('updateAppMenu')
		getDefaultAppSwitch().uncheck({ force: true })
		getDefaultAppSwitch().should('be.not.checked')
		// The uncheck persists asynchronously
		cy.wait('@updateAppMenu')

		// Check the redirect to the default app works
		cy.request({ url: '/', followRedirect: false }).then((response) => {
			expect(response.status).to.eq(302)
			expect(response).to.have.property('headers')
			expect(response.headers.location).to.contain('/apps/dashboard')
		})
	})
})

function getDefaultAppSwitch() {
	return cy.findByRole('checkbox', { name: 'Use custom default app' })
}
