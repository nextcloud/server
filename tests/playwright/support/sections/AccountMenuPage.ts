/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

/**
 * The "Settings menu" (account / user menu) in the Nextcloud header bar.
 * Rendered by AccountMenu.vue using NcHeaderMenu (id="user-menu", is-nav).
 *
 * Each entry is a NcListItem rendered as an <li> inside
 * <ul class="account-menu__list">.  The entry names ("View profile",
 * "Log out", …) are visible text inside those items.
 */
export class AccountMenuPage {
	constructor(private readonly page: Page) {}

	private trigger(): Locator {
		return this.page.getByRole('button', { name: 'Settings menu' })
	}

	private panel(): Locator {
		return this.page.locator('#header-menu-user-menu')
	}

	async open(): Promise<void> {
		const isOpen = await this.trigger().getAttribute('aria-expanded') === 'true'
		if (!isOpen) {
			await this.trigger().click()
		}
		await this.panel().waitFor({ state: 'visible' })
	}

	entries(): Locator {
		return this.panel().getByRole('listitem')
	}

	/**
	 * A single entry matched by visible text.
	 */
	entry(name: string): Locator {
		return this.panel().getByRole('listitem').filter({ hasText: name })
	}
}
