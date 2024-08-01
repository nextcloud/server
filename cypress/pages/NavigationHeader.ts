/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Page object model for the Nextcloud navigation header
 */
export class NavigationHeader {

	/**
	 * Locator of the header bar wrapper
	 */
	header() {
		return cy.get('header#header')
	}

	/**
	 * Locator for the logo navigation entry (entry redirects to default app)
	 */
	logo() {
		return this.header()
			.find('#nextcloud')
	}

	/**
	 * Locator of the app navigation bar
	 */
	navigation() {
		return this.header()
			.findByRole('navigation', { name: 'Applications menu' })
	}

	/**
	 * The toggle for the navigation overflow menu
	 */
	overflowNavigationToggle() {
		return this.navigation()
	}

	/**
	 * Get all navigation entries
	 */
	getNavigationEntries() {
		return this.navigation()
			.findAllByRole('listitem')
	}

	/**
	 * Get the navigation entry for a given app
	 * @param name The app name
	 */
	getNavigationEntry(name: string) {
		return this.navigation()
			.findByRole('listitem', { name })
	}

}
