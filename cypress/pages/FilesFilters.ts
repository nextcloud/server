/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Page object model for the files filters
 */
export class FilesFilterPage {

	filterContainter() {
		return cy.get('[data-cy-files-filters]')
	}

	activeFiltersList() {
		return cy.findByRole('list', { name: 'Active filters' })
	}

	activeFilters() {
		return this.activeFiltersList().findAllByRole('listitem')
	}

	removeFilter(name: string | RegExp) {
		const el = typeof name === 'string'
			? this.activeFilters().should('contain.text', name)
			: this.activeFilters().should('match', name)
		el.should('exist')
		// click the button
		el.findByRole('button', { name: 'Remove filter' })
			.should('exist')
			.click({ force: true })
	}

}
