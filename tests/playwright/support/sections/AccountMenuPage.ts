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
		// NcHeaderMenu trigger button inside the #user-menu nav.
		// The button's accessible name includes the dynamic avatar description
		// ("Avatar of {displayName} — {status}"), so we scope by the stable
		// container ID and the BEM class instead — the same approach used in
		// NavigationHeaderPage for the waffle button.
		return this.page.locator('header#header').locator('#user-menu .header-menu__trigger')
	}

	private panel(): Locator {
		// NcHeaderMenu generates a content div with id="header-menu-{id}".
		return this.page.locator('#header-menu-user-menu')
	}

	/** Open the settings menu and wait until the panel is visible. */
	async open(): Promise<void> {
		const isOpen = await this.trigger().getAttribute('aria-expanded') === 'true'
		if (!isOpen) await this.trigger().click()
		await this.panel().waitFor({ state: 'visible' })
	}

	/**
	 * All <li> entries currently shown in the panel.
	 * Use with toHaveCount() to assert the total number of menu items.
	 */
	entries(): Locator {
		return this.panel().getByRole('listitem')
	}

	/**
	 * A single entry matched by visible text.
	 * Uses filter({ hasText }) so it works for both the primary name and
	 * the subname slot (e.g. the profile entry whose link label is the
	 * user's display name, while "View profile" appears as a subname).
	 */
	entry(name: string): Locator {
		return this.panel().getByRole('listitem').filter({ hasText: name })
	}
}
