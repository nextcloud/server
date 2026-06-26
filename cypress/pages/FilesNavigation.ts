/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Page object model for the files app navigation
 */
export class FilesNavigationPage {
	navigation() {
		return cy.findByRole('navigation', { name: 'Files' })
	}

	searchInput() {
		return this.navigation().findByRole('searchbox')
	}

	searchScopeTrigger() {
		return this.navigation().findByRole('button', { name: /search scope options/i })
	}

	/**
	 * Only available after clicking on the search scope trigger
	 */
	searchScopeMenu() {
		return cy.findByRole('menu', { name: /search scope options/i })
	}

	searchClearButton() {
		return this.navigation().findByRole('button', { name: /clear search/i })
	}

	settingsToggle() {
		return this.navigation().findByRole('link', { name: 'Files settings' })
	}

	views() {
		return this.navigation().findByRole('list', { name: 'Views' })
	}

	quota() {
		return this.navigation().find('[data-cy-files-navigation-settings-quota]')
	}
}
