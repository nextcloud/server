/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

describe('404 error page', { testIsolation: true }, () => {
	it('renders 404 page', () => {
		cy.visit('/doesnotexist', { failOnStatusCode: false })

		cy.findByRole('heading', { name: /Page not found/ })
			.should('be.visible')
		cy.findByRole('link', { name: /Back to Nextcloud/ })
			.should('be.visible')
			.click()

		cy.url()
			.should('match', /(\/index.php)\/login$/)
	})
})
