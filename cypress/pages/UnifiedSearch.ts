/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Page object model for the UnifiedSearch
 */
export class UnifiedSearchPage {

	toggleButton() {
		return cy.findByRole('button', { name: 'Unified search' })
	}

	globalSearchButton() {
		return cy.findByRole('button', { name: 'Search everywhere' })
	}

	localSearchInput() {
		return cy.findByRole('textbox', { name: 'Search in current app' })
	}

	globalSearchInput() {
		return cy.findByRole('textbox', { name: /Search apps, files/ })
	}

	globalSearchModal() {
		// TODO: Broken in library
		// return cy.findByRole('dialog', { name: 'Unified search' })
		return cy.get('#unified-search')
	}

	// functions

	openLocalSearch() {
		this.toggleButton()
			.if('visible')
			.click()

		this.localSearchInput().should('exist').and('not.have.css', 'display', 'none')
	}

	/**
	 * Type in the local search (must be open before)
	 * Helper because the input field is overlayed by the global-search button -> cypress thinks the input is not visible
	 *
	 * @param text The text to type
	 * @param options Options as for `cy.type()`
	 */
	typeLocalSearch(text: string, options?: Partial<Omit<Cypress.TypeOptions, 'force'>>) {
		return this.localSearchInput()
			.type(text, { ...options, force: true })
	}

	openGlobalSearch() {
		this.toggleButton()
			.if('visible').click()

		this.globalSearchButton()
			.if('visible').click()
	}

	closeGlobalSearch() {
		this.globalSearchModal()
			.findByRole('button', { name: 'Close' })
			.click()
	}

	getResults(category: string | RegExp) {
		return this.globalSearchModal()
			.findByRole('list', { name: category })
			.findAllByRole('listitem')
	}

}
