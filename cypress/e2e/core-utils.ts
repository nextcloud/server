/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
