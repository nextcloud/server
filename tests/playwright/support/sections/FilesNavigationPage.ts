/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

/**
 * The left-hand files navigation (the view list: All files, Favorites, Recent, …).
 * Distinct from {@link NavigationHeaderPage}, which models the top app bar.
 */
export class FilesNavigationPage {
	constructor(private readonly page: Page) {}

	/** The left-hand "Files" navigation region. */
	navigation(): Locator {
		return this.page.getByRole('navigation', { name: 'Files' })
	}

	/** The search/filter input in the navigation. */
	searchInput(): Locator {
		return this.navigation().getByRole('searchbox')
	}

	searchClearButton(): Locator {
		return this.navigation().getByRole('button', { name: /clear search/i })
	}

	/** Switch the search scope to "everywhere" (global search) via the scope menu. */
	async searchEverywhere(): Promise<void> {
		await this.navigation().getByRole('button', { name: /search scope options/i }).click()
		const menu = this.page.getByRole('menu', { name: /search scope options/i })
		await menu.getByRole('menuitem', { name: /search everywhere/i }).click()
	}

	/**
	 * A navigation entry, e.g. the "favorites" view.
	 * Uses the product-owned data-cy attribute set on NcAppNavigationItem.
	 */
	getNavigationItem(viewId: string): Locator {
		return this.page.locator(`[data-cy-files-navigation-item="${viewId}"]`)
	}

	/**
	 * Expand a collapsible navigation view to reveal its child entries.
	 * Collapsed children are `display: none`, so they must be expanded to be visible.
	 * "Open menu" is the accessible name of NcAppNavigationItem's collapse toggle.
	 */
	async expandNavigationItem(viewId: string): Promise<void> {
		await this.getNavigationItem(viewId)
			.getByRole('button', { name: 'Open menu' })
			.click()
	}

	/** The "Files settings" dialog opened from the navigation footer. */
	settingsDialog(): Locator {
		return this.page.getByRole('dialog', { name: 'Files settings' })
	}

	/** Open the "Files settings" dialog and return its locator. */
	async openSettings(): Promise<Locator> {
		await this.page.getByRole('link', { name: 'Files settings' }).click()
		const dialog = this.settingsDialog()
		await dialog.waitFor({ state: 'visible' })
		return dialog
	}

	async closeSettings(): Promise<void> {
		const dialog = this.settingsDialog()
		await dialog.getByRole('button', { name: 'Close' }).click()
		await dialog.waitFor({ state: 'hidden' })
	}

	/**
	 * Toggle "Show hidden files" in the Files settings dialog, then close it.
	 * Opens the dialog itself, so call from any files view.
	 */
	async setShowHiddenFiles(show: boolean): Promise<void> {
		const dialog = await this.openSettings()
		const toggle = dialog.getByRole('switch', { name: /show hidden files/i })
		await toggle.scrollIntoViewIfNeeded()
		if (show) {
			await toggle.check({ force: true })
		} else {
			await toggle.uncheck({ force: true })
		}
		await this.closeSettings()
	}
}
