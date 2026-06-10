/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

export class FilesSidebarPage {
	constructor(private readonly page: Page) {}

	sidebar(): Locator {
		return this.page.locator('#app-sidebar-vue')
	}

	heading(name: string): Locator {
		return this.sidebar().getByRole('heading', { name })
	}

	/**
	 * Open the sidebar "Actions" menu and click the entry with the given name
	 * (e.g. "Favorite" / "Unfavorite").
	 */
	async triggerAction(name: string): Promise<void> {
		await this.sidebar().getByRole('button', { name: 'Actions' }).click()
		const action = this.page.getByRole('menuitem', { name })
		await action.waitFor({ state: 'visible' })
		await action.click()
	}

	async close(): Promise<void> {
		await this.sidebar().getByRole('button', { name: 'Close sidebar' }).click()
	}
}
