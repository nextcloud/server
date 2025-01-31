/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Get the unified search modal (if open)
 */
export function getUnifiedSearchModal() {
	return cy.get('#unified-search')
}

/**
 * Open the unified search modal
 */
export function openUnifiedSearch() {
	cy.get('button[aria-label="Unified search"]').click({ force: true })
	// wait for it to be open
	getUnifiedSearchModal().should('be.visible')
}

/**
 * Close the unified search modal
 */
export function closeUnifiedSearch() {
	getUnifiedSearchModal().find('button[aria-label="Close"]').click({ force: true })
	getUnifiedSearchModal().should('not.be.visible')
}

/**
 * Get the input field of the unified search
 */
export function getUnifiedSearchInput() {
	return getUnifiedSearchModal().find('[data-cy-unified-search-input]')
}

export enum UnifiedSearchFilter {
	FilterCurrentView = 'current-view',
	Places = 'places',
	People = 'people',
	Date = 'date',
}

/**
 * Get a filter action from the unified search
 * @param filter The filter to get
 */
export function getUnifiedSearchFilter(filter: UnifiedSearchFilter) {
	return getUnifiedSearchModal().find(`[data-cy-unified-search-filters] [data-cy-unified-search-filter="${CSS.escape(filter)}"]`)
}

/**
 * Assertion that an element is fully within the current viewport.
 * @param $el The element
 * @param expected If the element is expected to be fully in viewport or not fully
 * @example
 * ```js
 * cy.get('#my-element')
 *   .should(beFullyInViewport)
 * ```
 */
export function beFullyInViewport($el: JQuery<HTMLElement>, expected = true) {
	const { top, left, bottom, right } = $el.get(0)!.getBoundingClientRect()
	const innerHeight = Cypress.$('body').innerHeight()!
	const innerWidth = Cypress.$('body').innerWidth()!
	const fullyVisible = top >= 0 && left >= 0 && bottom <= innerHeight && right <= innerWidth

	console.debug(`fullyVisible: ${fullyVisible}, top: ${top >= 0}, left: ${left >= 0}, bottom: ${bottom <= innerHeight}, right: ${right <= innerWidth}`)

	if (expected) {
		// eslint-disable-next-line no-unused-expressions
		expect(fullyVisible, 'Fully within viewport').to.be.true
	} else {
		// eslint-disable-next-line no-unused-expressions
		expect(fullyVisible, 'Not fully within viewport').to.be.false
	}
}

/**
 * Opposite of `beFullyInViewport` - resolves when element is not or only partially in viewport.
 * @param $el The element
 * @example
 * ```js
 * cy.get('#my-element')
 *   .should(notBeFullyInViewport)
 * ```
 */
export function notBeFullyInViewport($el: JQuery<HTMLElement>) {
	return beFullyInViewport($el, false)
}
