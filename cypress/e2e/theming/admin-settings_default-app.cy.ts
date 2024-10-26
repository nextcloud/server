/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/cypress'
import { NavigationHeader } from '../../pages/NavigationHeader'

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
		cy.get('[data-cy-switch-default-app]').should('exist')
		cy.get('[data-cy-switch-default-app]').scrollIntoView()
	})

	it('Toggle the "use custom default app" switch', () => {
		cy.get('[data-cy-switch-default-app] input').should('not.be.checked')
		cy.get('[data-cy-switch-default-app] .checkbox-content').click()
		cy.get('[data-cy-switch-default-app] input').should('be.checked')
	})

	it('See the default app order selector', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element]').then(elements => {
			const appIDs = elements.map((idx, el) => el.getAttribute('data-cy-app-order-element')).get()
			expect(appIDs).to.deep.eq(['dashboard', 'files'])
		})
	})

	it('Change the default app', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"]').scrollIntoView()

		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('be.visible')
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').click()
		cy.get('[data-cy-app-order] [data-cy-app-order-element="files"] [data-cy-app-order-button="up"]').should('not.be.visible')

	})

	it('See the default app is changed', () => {
		cy.get('[data-cy-app-order] [data-cy-app-order-element]').then(elements => {
			const appIDs = elements.map((idx, el) => el.getAttribute('data-cy-app-order-element')).get()
			expect(appIDs).to.deep.eq(['files', 'dashboard'])
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
		cy.get('[data-cy-switch-default-app]').scrollIntoView()

		cy.get('[data-cy-switch-default-app] input').should('be.checked')
		cy.get('[data-cy-switch-default-app] .checkbox-content').click()
		cy.get('[data-cy-switch-default-app] input').should('be.not.checked')
	})

	it('See the default app is changed back to default', () => {
		// Check the redirect to the default app works
		cy.request({ url: '/', followRedirect: false }).then((response) => {
			expect(response.status).to.eq(302)
			expect(response).to.have.property('headers')
			expect(response.headers.location).to.contain('/apps/dashboard')
		})
	})
})
