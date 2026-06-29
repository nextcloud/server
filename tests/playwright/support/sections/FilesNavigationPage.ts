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
}
