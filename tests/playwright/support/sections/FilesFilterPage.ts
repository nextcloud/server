/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

/**
 * The files list filter bar (name filter chip, "Type" filter, active filters).
 * Assumes a wide layout where filter categories render as inline buttons — pin a
 * wide viewport in specs using it (see files-filtering.spec.ts).
 */
export class FilesFilterPage {
	constructor(private readonly page: Page) {}

	private container(): Locator {
		return this.page.locator('[data-test-id="files-list-filters"]')
	}

	/** Open a filter category's popover (e.g. "Type"). */
	async openFilter(category: string): Promise<void> {
		await this.container().getByRole('button', { name: category }).click()
	}

	/** A filter option toggle (e.g. "Spreadsheets", "Folders") in the open popover. */
	filterOption(name: string): Locator {
		return this.page.getByRole('button', { name })
	}

	/** Close any open filter category popover (clicks the expanded toggles). */
	async closeFilterMenu(): Promise<void> {
		for (const toggle of await this.container().locator('button[aria-expanded="true"]').all()) {
			await toggle.click()
		}
	}

	/** The active-filter chips (each an "Active filters" listitem). */
	activeFilters(): Locator {
		return this.page.getByRole('list', { name: 'Active filters' }).getByRole('listitem')
	}

	/** Remove an active filter by clicking the chip's "Remove filter" button. */
	async removeFilter(name: string | RegExp): Promise<void> {
		await this.activeFilters()
			.filter({ hasText: name })
			.getByRole('button', { name: 'Remove filter' })
			.click({ force: true })
	}
}
