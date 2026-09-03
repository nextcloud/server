/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'
import type { FilesListPage } from './FilesListPage.ts'

import { expect } from '@playwright/test'

/**
 * The unified share list in the files sidebar: one flat list of shares, where a
 * share with several recipients renders as a collapsible group.
 */
export class UnifiedShareListPage {
	readonly #page: Page

	constructor(page: Page) {
		this.#page = page
	}

	/**
	 * Open a file's sidebar on the Sharing tab and wait for the unified list.
	 *
	 * The legacy `openSharingPanel` helper cannot be used here: it waits for the
	 * recipient input of the old editor, which the unified sidebar replaces with
	 * the share button.
	 *
	 * @param filesListPage The files list page object
	 * @param fileName The row whose shares to open
	 */
	async open(filesListPage: FilesListPage, fileName: string): Promise<void> {
		await filesListPage.open()
		await filesListPage.triggerActionForFile(fileName, 'details')
		const tab = this.#page.locator('#app-sidebar-vue').getByRole('tab', { name: 'Sharing' })
		if (await tab.getAttribute('aria-selected') !== 'true') {
			await tab.click()
		}
		await expect(this.#page.getByRole('tabpanel', { name: 'Sharing' })).toBeVisible()
		// Readiness is the share button: it is rendered with the list itself.
		await expect(this.shareButton).toBeVisible()
	}

	/** The sharing tab's own root, to scope away from other sidebar tabs. */
	get root(): Locator {
		return this.#page.locator('.sharingTab')
	}

	/** The flat list of shares. */
	get list(): Locator {
		return this.root.locator('.unified-share-list')
	}

	/** The loading placeholder shown while the list is fetched. */
	get skeleton(): Locator {
		return this.root.locator('.share-skeleton')
	}

	/** The button that opens the dialog to create a new share. */
	get shareButton(): Locator {
		return this.root.getByRole('button', { name: 'Share', exact: true })
	}

	/** Every row, share groups and their recipients alike, in visual order. */
	get rows(): Locator {
		return this.list.locator('.sharing-entry')
	}

	/** The rows nested under an expanded share group. */
	get recipientRows(): Locator {
		return this.list.locator('.sharing-entry.unified-share__recipient')
	}

	/** The error shown when the list cannot be loaded. */
	get error(): Locator {
		return this.root.getByText('Unable to load the shares list')
	}

	/**
	 * A row by the title it renders: a recipient's display name for a
	 * single-recipient share, or the summary for a group.
	 *
	 * @param title The row title
	 */
	row(title: string): Locator {
		return this.rows.filter({ has: this.#page.locator('.sharing-entry__title', { hasText: title }) })
	}

	/** The share groups, i.e. the rows that own a recipients toggle. */
	get groups(): Locator {
		return this.rows.filter({ has: this.#page.getByRole('button', { name: 'Toggle recipients' }) })
	}

	/**
	 * The permission label shown under a row's title.
	 *
	 * @param title The row title
	 */
	subtitle(title: string): Locator {
		// The entry renders its subtitle as a bare paragraph.
		return this.row(title).locator('.sharing-entry__desc p')
	}

	/**
	 * Open a row's action menu and return the menu, so actions can be clicked.
	 *
	 * @param title The row title
	 */
	async openMenu(title: string): Promise<Locator> {
		await this.row(title).getByRole('button', { name: 'Actions' }).click()
		return this.#page.getByRole('menu')
	}

	/**
	 * Trigger a row action by its label.
	 *
	 * @param title The row title
	 * @param action The action label, e.g. 'Edit share'
	 */
	async triggerAction(title: string, action: string): Promise<void> {
		const menu = await this.openMenu(title)
		await menu.getByRole('menuitem', { name: action }).click()
	}

	/**
	 * Collapse or expand a share group.
	 *
	 * @param title The group's summary title
	 */
	async toggleRecipients(title: string): Promise<void> {
		await this.row(title).getByRole('button', { name: 'Toggle recipients' }).click()
	}

	/**
	 * Whether a share group is expanded.
	 *
	 * @param title The group's summary title
	 */
	async isExpanded(title: string): Promise<boolean> {
		const toggle = this.row(title).getByRole('button', { name: 'Toggle recipients' })
		return await toggle.getAttribute('aria-expanded') === 'true'
	}

	/** The avatar stack a collapsed group shows in place of its avatar. */
	get avatarStack(): Locator {
		return this.list.locator('.avatar-stack')
	}

	/**
	 * Answer the confirmation dialog raised by a destructive action.
	 *
	 * @param confirm Whether to go through with it
	 */
	async answerConfirmation(confirm: boolean): Promise<void> {
		const dialog = this.#page.getByRole('dialog', { name: 'Delete share' })
		await dialog.getByRole('button', { name: confirm ? 'Delete' : 'Cancel', exact: true }).click()
	}
}
