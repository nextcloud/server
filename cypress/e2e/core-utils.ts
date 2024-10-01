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
