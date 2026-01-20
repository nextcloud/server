/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import { NavigationHeader } from '../../pages/NavigationHeader.ts'

const admin = new User('admin', 'admin')

describe('Admin theming set default apps', () => {
	const navigationHeader = new NavigationHeader()

	before(function() {
		// Just in case previous test failed
		cy.resetAdminTheming()
		cy.login(admin)
	})

	it('See the current default app is the dashboard', () => {
		// check default route
		cy.visit('/')
		cy.url().should('match', /apps\/dashboard/)

		// Also check the top logo link
		navigationHeader.logo().click()
		cy.url().should('match', /apps\/dashboard/)
	})

	it('See the default app settings', () => {
		cy.visit('/settings/admin/theming')

		cy.get('.settings-section').contains('Navigation bar settings').should('exist')
		getDefaultAppSwitch().should('exist')
		getDefaultAppSwitch().scrollIntoView()
	})

	it('Toggle the "use custom default app" switch', () => {
		getDefaultAppSwitch().should('not.be.checked')
		cy.findByRole('region', { name: 'Global default app' })
			.should('not.exist')

		getDefaultAppSwitch().check({ force: true })
		getDefaultAppSwitch().should('be.checked')
		cy.findByRole('region', { name: 'Global default app' })
			.should('exist')
	})

	it('See the default app combobox', () => {
		cy.findByRole('region', { name: 'Global default app' })
			.should('exist')
			.findByRole('combobox')
			.as('defaultAppSelect')
			.scrollIntoView()

		cy.get('@defaultAppSelect')
			.findByText('Dashboard')
			.should('be.visible')
		cy.get('@defaultAppSelect')
			.findByText('Files')
			.should('be.visible')
	})

	it('See the default app order selector', () => {
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
		cy.visit('/settings/admin/theming')
		getDefaultAppSwitch().scrollIntoView()

		getDefaultAppSwitch().should('be.checked')
		getDefaultAppSwitch().uncheck({ force: true })
		getDefaultAppSwitch().should('be.not.checked')

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
