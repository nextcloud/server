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

	navigationEntries(): Locator {
		return this.navigation().getByRole('listitem')
	}
}
