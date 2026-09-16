/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

export class NavigationHeaderPage {
	constructor(private readonly page: Page) {}

	private get header(): Locator {
		return this.page.locator('header#header')
	}

	logo(): Locator {
		return this.header.locator('#nextcloud')
	}

	navigation(): Locator {
		return this.header.getByRole('navigation', { name: 'Applications menu' })
	}

	/**
	 * The app entries rendered inline in the navigation bar, in display order.
	 * Entries that do not fit are moved into the "More apps" flyout instead.
	 */
	navigationEntries(): Locator {
		return this.navigation().locator('.app-menu-entry__link')
	}
}
