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
		return this.header.getByRole('navigation', { name: 'Applications' })
	}

	private waffleButton(): Locator {
		return this.navigation().locator('.app-menu__waffle')
	}

	/**
	 * Open the waffle launcher popover.
	 * The app entries only exist in the DOM while the popover is open.
	 */
	async openMenu(): Promise<void> {
		const isOpen = await this.waffleButton().getAttribute('aria-expanded') === 'true'
		if (!isOpen) {
			await this.waffleButton().click()
		}
		await this.popover().waitFor({ state: 'visible' })
	}

	popover(): Locator {
		return this.page.locator('[role="menu"][aria-label="Apps"]')
	}

	/**
	 * Returns navigation entries from the waffle popover.
	 * Call {@link openMenu} first — entries are only in the DOM while the popover is open.
	 */
	navigationEntries(): Locator {
		return this.popover().getByRole('menuitem')
	}
}
