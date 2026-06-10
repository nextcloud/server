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
}
