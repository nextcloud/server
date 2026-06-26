/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Page object model for the files filters
 */
export class FilesFilterPage {
	/**
	 * Get the filters menu button (only on narrow and medium widths)
	 */
	getFiltersMenuToggle() {
		return cy.get('[data-test-id="files-list-filters"]')
			.findByRole('button', { name: 'Filters' })
	}

	/**
	 * Get and trigger the filter within the menu (only on narrow and medium widths)
	 *
	 * @param name - The name of the filter button
	 */
	triggerFilterMenu(name: string | RegExp) {
		cy.get('[data-test-id="files-list-filters"]')
			.findByRole('button', { name: 'Filters' })
			.should('be.visible')
			.as('filtersMenuToggle')
			.click()

		cy.get('@filtersMenuToggle')
			.should('have.attr', 'aria-expanded', 'true')

		cy.findByRole('menu')
			.should('be.visible')
			.findByRole('menuitem', { name })
			.should('be.visible')
			.click()
	}

	/**
	 * Get and trigger the filter button if the files list is wide enough to show all filters
	 *
	 * @param name - The name of the filter button
	 */
	triggerFilterButton(name: string | RegExp) {
		cy.get('[data-test-id="files-list-filters"]')
			.findByRole('button', { name })
			.should('be.visible')
			.click()
	}

	triggerFilter(name: string | RegExp) {
		cy.get('[data-cy-files-list]')
			.should('be.visible')
			.if(($el) => expect($el.get(0).clientWidth).to.be.gte(1024))
			.then(() => this.triggerFilterButton(name))
			.else()
			.then(() => this.triggerFilterMenu(name))
	}

	closeFilterMenu() {
		cy.get('[data-test-id="files-list-filters"]')
			.findAllByRole('button')
			.filter('[aria-expanded="true"]')
			.click({ multiple: true })
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
