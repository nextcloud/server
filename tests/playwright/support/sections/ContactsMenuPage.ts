/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

/**
 * The contacts / people menu in the Nextcloud header bar.
 * Rendered by ContactsMenu.vue using NcHeaderMenu (id="contactsmenu").
 *
 * Contact entries are <li class="contact"> elements inside
 * <ul aria-label="Contacts list">.  When no contacts match the search,
 * that list is absent from the DOM (v-else path) and NcEmptyContent is
 * shown instead.
 */
export class ContactsMenuPage {
	constructor(private readonly page: Page) {}

	private trigger(): Locator {
		// NcHeaderMenu passes its ariaLabel prop ("Search contacts") to the
		// trigger NcButton, so the button is uniquely identifiable by role+name.
		return this.page.locator('header#header').getByRole('button', { name: 'Search contacts' })
	}

	private panel(): Locator {
		// NcHeaderMenu generates a content container with id="header-menu-{id}".
		return this.page.locator('#header-menu-contactsmenu')
	}

	private contactsList(): Locator {
		return this.page.getByRole('list', { name: 'Contacts list' })
	}

	/** Open the menu and wait for the initial contacts fetch to complete. */
	async open(): Promise<void> {
		// Register waitForResponse BEFORE clicking to avoid the race condition
		// described in the migration context.
		const loaded = this.page.waitForResponse(
			(r) => r.url().includes('/contactsmenu/contacts') && r.request().method() === 'POST',
		)
		await this.trigger().click()
		await loaded
	}

	/** Close the menu. */
	async close(): Promise<void> {
		const isOpen = await this.trigger().getAttribute('aria-expanded') === 'true'
		if (isOpen) await this.trigger().click()
		await this.panel().waitFor({ state: 'hidden' })
	}

	/**
	 * A contact entry matched by the user's userId / display name.
	 * Returns the <li> element containing that text.  Returns an empty
	 * locator (count 0) when the contacts list is absent from the DOM.
	 */
	contact(userId: string): Locator {
		return this.contactsList().getByRole('listitem').filter({ hasText: userId })
	}

	/**
	 * Fill the search input and wait for the server response.
	 * The input is debounced by 500 ms inside the component, so
	 * a network request always follows a fill().
	 */
	async search(query: string): Promise<void> {
		await this.page.getByRole('searchbox', { name: 'Search contacts' }).fill(query)
	}
}
