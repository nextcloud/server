/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'

/**
 * The unified sharing dialog (`@nextcloud/sharing/dialog`), opened from the
 * files sidebar through the `OCA.Sharing` bridge.
 */
export class SharingDialogPage {
	readonly #page: Page

	constructor(page: Page) {
		this.#page = page
	}

	/** The dialog itself. */
	get dialog(): Locator {
		return this.#page.locator('.sharing-dialog')
	}

	get title(): Locator {
		return this.dialog.locator('.sharing-dialog__title')
	}

	/** The share form; absent while the draft is still being created. */
	get panel(): Locator {
		return this.dialog.locator('.share-panel')
	}

	// --- Share type ---

	/** The invited/anyone tab bar. Only present while the share is a draft. */
	get tabBar(): Locator {
		return this.dialog.locator('.share-panel__tab-bar')
	}

	/**
	 * Switch the share type.
	 *
	 * @param tab The tab to activate
	 */
	async selectTab(tab: 'Invited people' | 'Anyone'): Promise<void> {
		// The radio itself is visually hidden, its label is what a user clicks.
		await this.tabBar.getByText(tab, { exact: true }).click()
	}

	/**
	 * The share type radio, to assert on with `toBeChecked()` — switching runs
	 * through a confirmation and recipient removals, so it needs a retrying
	 * assertion rather than a one-shot read.
	 *
	 * @param tab The tab to locate
	 */
	tab(tab: 'Invited people' | 'Anyone'): Locator {
		return this.tabBar.getByRole('radio', { name: tab })
	}

	// --- Recipients ---

	get recipientSearch(): Locator {
		return this.dialog.getByRole('combobox', { name: 'Add people' })
	}

	get recipientList(): Locator {
		return this.dialog.locator('.recipient-list')
	}

	get recipientRows(): Locator {
		return this.dialog.locator('.recipient-row')
	}

	/**
	 * A recipient row by display name.
	 *
	 * @param name The recipient's display name
	 */
	recipientRow(name: string): Locator {
		return this.recipientRows.filter({ has: this.#page.locator('.recipient-row__name', { hasText: name }) })
	}

	/**
	 * The permission label shown under a recipient's name.
	 *
	 * @param name The recipient's display name
	 */
	recipientPermission(name: string): Locator {
		return this.recipientRow(name).locator('.recipient-row__subtitle')
	}

	/**
	 * Search for a recipient and pick it from the results, which adds it to the
	 * share right away.
	 *
	 * @param query What to type
	 * @param name The result to pick; defaults to the query
	 */
	async addRecipient(query: string, name: string = query): Promise<void> {
		// The search is debounced, the picker fires one of its own when it opens,
		// and a late answer can replace the list with results for another query.
		// Retype until the wanted result is actually on screen.
		await expect(async () => {
			const searched = this.#page.waitForResponse((response) => response.url().includes('/api/v1/recipients')
				&& response.url().includes(query))
			await this.recipientSearch.fill('')
			await this.recipientSearch.fill(query)
			await searched
			await expect(this.option(name)).toBeVisible({ timeout: 5_000 })
		}).toPass({ timeout: 30_000 })

		// The recipient is added by the request the selection triggers.
		const added = this.#page.waitForResponse((response) => response.url().includes('/recipient')
			&& response.request().method() === 'POST')
		await this.option(name).click()
		await added
	}

	/**
	 * A result of the recipient search.
	 *
	 * @param name The display name to match
	 */
	option(name: string): Locator {
		return this.#page.getByRole('option', { name: new RegExp(name) })
	}

	/**
	 * Open a recipient's action menu and return the menu.
	 *
	 * @param name The recipient's display name
	 */
	async openRecipientMenu(name: string): Promise<Locator> {
		await this.recipientRow(name).getByRole('button', { name: 'Recipient actions' }).click()
		return this.#page.getByRole('menu')
	}

	/**
	 * Pick a preset for one recipient from its action menu.
	 *
	 * @param name The recipient's display name
	 * @param preset The preset label as shown in the menu
	 */
	async setRecipientPreset(name: string, preset: string | RegExp): Promise<void> {
		const menu = await this.openRecipientMenu(name)
		const changed = this.#page.waitForResponse((response) => response.url().includes('/recipient/permission'))
		await menu.getByRole('menuitem', { name: preset }).click()
		await changed
	}

	/** The per-recipient custom permissions modal. */
	get recipientPermissionsModal(): Locator {
		return this.#page.getByRole('dialog', { name: /^Permissions for / })
	}

	// --- Share level permissions ---

	/**
	 * A share-level permission toggle, shown once "Custom permissions" is picked.
	 *
	 * @param name The permission's label
	 */
	permissionToggle(name: string): Locator {
		return this.dialog.getByRole('checkbox', { name })
	}

	// --- Actions ---

	get sendButton(): Locator {
		return this.dialog.locator('.share-panel__link-send')
	}

	get copyLinkButton(): Locator {
		return this.dialog.locator('.share-panel__link-copy')
	}

	get settingsButton(): Locator {
		return this.dialog.getByRole('button', { name: 'Additional sharing settings' })
	}

	get deleteButton(): Locator {
		return this.dialog.locator('.share-panel__delete')
	}

	/** Submit the share and wait for the confirmation view. */
	async send(): Promise<void> {
		// Switching to a public link creates its token recipient first, and the
		// button stays disabled until that lands.
		await expect(this.sendButton).toBeEnabled({ timeout: 10_000 })
		await this.sendButton.click()
		await this.confirmation.waitFor()
	}

	get confirmation(): Locator {
		return this.dialog.locator('.share-confirmation')
	}

	get confirmationLink(): Locator {
		return this.dialog.locator('.share-confirmation__link-input').getByRole('textbox')
	}

	/** Close the dialog from the confirmation view. */
	async done(): Promise<void> {
		await this.dialog.locator('.share-confirmation__done').click()
		await this.dialog.waitFor({ state: 'detached' })
	}

	/** Close the dialog with its own close button. */
	async close(): Promise<void> {
		await this.#page.getByRole('button', { name: 'Close', exact: true }).click()
		await this.dialog.waitFor({ state: 'detached' })
	}

	/**
	 * Answer a confirmation dialog raised inside the sharing dialog, e.g. when
	 * switching to a public link or deleting the share.
	 *
	 * @param name The confirmation dialog's name
	 * @param button The button to press
	 */
	async answerConfirmation(name: string, button: string): Promise<void> {
		await this.#page.getByRole('dialog', { name }).getByRole('button', { name: button, exact: true }).click()
	}
}
