/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Regression test of https://github.com/nextcloud/server/issues/48403
 * Ensure that only visible widget data is loaded
 */
describe('dashboard: performance', () => {
	before(() => {
		cy.createRandomUser().then((user) => {
			// Enable one widget
			cy.runOccCommand(`user:setting -- '${user.userId}' dashboard layout files-favorites`)
			cy.login(user)
		})
	})

	it('Only load needed widgets', () => {
		cy.intercept('**/dashboard/api/v2/widget-items?widgets*').as('loadedWidgets')

		const now = new Date(2025, 0, 14, 15)
		cy.clock(now)

		// The dashboard is loaded
		cy.visit('/apps/dashboard')
		cy.get('#app-dashboard')
			.should('be.visible')
			.contains('Good afternoon')
			.should('be.visible')

		// Wait that one data is loaded (ensure the API works), this should be the favorite files.
		cy.wait('@loadedWidgets')
		// Wait and check no requests are made (ensure that the user statuses data is NOT loaded)
		// eslint-disable-next-line cypress/no-unnecessary-waiting
		cy.wait(4000, { timeout: 8000 })
		cy.get('@loadedWidgets.all').then((interceptions) => {
			expect(interceptions).to.have.length(1)
		})
	})
})
