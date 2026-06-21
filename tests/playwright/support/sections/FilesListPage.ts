/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'
import { escapeAttributeValue } from '../utils/css.ts'

export class FilesListPage {
	constructor(protected readonly page: Page) {}

	/**
	 * Open the files app. Pass a view id (e.g. 'recent') to open that view
	 * instead of the default "All files" list.
	 */
	async open(viewId?: string): Promise<void> {
		await this.page.goto(viewId ? `apps/files/${viewId}` : 'apps/files')
		await this.page.locator('[data-cy-files-list]').waitFor({ state: 'visible' })
	}

	getRowForFile(filename: string): Locator {
		return this.page.locator(`[data-cy-files-list-row-name="${escapeAttributeValue(filename)}"]`)
	}

	getRowForFileId(fileid: number): Locator {
		return this.page.locator(`[data-cy-files-list-row-fileid="${fileid}"]`)
	}

	/** All file rows currently rendered in the list (e.g. for count assertions). */
	getRows(): Locator {
		return this.page.locator('[data-cy-files-list-row-fileid]')
	}

	/** The per-row selection checkboxes. */
	getRowCheckboxes(): Locator {
		return this.page.locator('[data-cy-files-list-row-checkbox]')
	}

	/** The per-row selection checkboxes that are currently checked (i.e. selected rows). */
	getSelectedRowCheckboxes(): Locator {
		return this.getRowCheckboxes().getByRole('checkbox', { checked: true })
	}

	private getActionsButtonForFile(filename: string): Locator {
		return this.getRowForFile(filename)
			.getByRole('button', { name: 'Actions' })
	}

	/**
	 * Open the row actions menu for a file and return the menu popover locator.
	 * Use this when a test needs to inspect a menu entry (e.g. its label) before
	 * clicking; for a plain "open and click" use {@link triggerActionForFile}.
	 */
	async openActionsMenuForFile(filename: string): Promise<Locator> {
		const row = this.getRowForFile(filename)
		await row.hover()

		const actionsButton = this.getActionsButtonForFile(filename)
		await actionsButton.scrollIntoViewIfNeeded()
		// force: true to avoid issues with the sticky file list header
		await actionsButton.click({ force: true })

		const menuId = await actionsButton.getAttribute('aria-controls')
		const menu = this.page.locator(`#${menuId}`)
		await menu.waitFor({ state: 'visible' })
		return menu
	}

	getActionButtonInMenu(menu: Locator, actionId: string): Locator {
		// The action button has role="menuitem", so use tag selector not getByRole
		return menu.locator(`[data-cy-files-list-row-action="${actionId}"] button`)
	}

	async triggerActionForFile(filename: string, actionId: string): Promise<void> {
		const menu = await this.openActionsMenuForFile(filename)
		const actionEntry = this.getActionButtonInMenu(menu, actionId)
		await actionEntry.waitFor({ state: 'visible' })
		await actionEntry.click()
	}

	getFavoriteIconForFile(filename: string): Locator {
		return this.getRowForFile(filename).getByRole('img', { name: 'Favorite' })
	}

	/**
	 * The inline "Download" button rendered on a row for the default download action.
	 */
	getDownloadButtonForFile(filename: string): Locator {
		return this.getRowForFile(filename).getByRole('button', { name: 'Download' })
	}

	private getSelectAllCheckbox(): Locator {
		return this.page.locator('[data-cy-files-list-selection-checkbox]')
			.getByRole('checkbox')
	}

	async selectAll(): Promise<void> {
		await this.getSelectAllCheckbox().click({ force: true })
	}

	/**
	 * Clear the current selection via the master checkbox. It is a toggle, so it
	 * clicks the same control as {@link selectAll}; call it while rows are
	 * selected to deselect them all.
	 */
	async deselectAll(): Promise<void> {
		await this.getSelectAllCheckbox().click({ force: true })
	}

	/**
	 * Select a single row's checkbox. Pass `{ shift: true }` to extend the
	 * selection as a range from the previously selected row. Range selection
	 * reads the global keyboard store, so Shift is held with real keyboard
	 * events rather than a click modifier.
	 */
	async selectRowForFile(filename: string, { shift = false }: { shift?: boolean } = {}): Promise<void> {
		// The checkbox is visually hidden inside NcCheckboxRadioSwitch, so force the interaction
		const checkbox = this.getRowForFile(filename)
			.getByRole('checkbox', { name: /Toggle selection/ })

		if (shift) {
			await this.page.keyboard.down('Shift')
			await checkbox.click({ force: true })
			await this.page.keyboard.up('Shift')
		} else {
			await checkbox.check({ force: true })
		}
	}

	/**
	 * The toolbar that replaces the list header once one or more rows are selected.
	 */
	getSelectionActionsToolbar(): Locator {
		return this.page.locator('[data-cy-files-list-selection-actions]')
	}

	private getSelectionActionsButton(): Locator {
		return this.getSelectionActionsToolbar().getByRole('button', { name: 'Actions' })
	}

	/**
	 * Open the bulk-selection actions menu. Pair with {@link getSelectionActionEntry}
	 * to inspect an entry (e.g. assert it is visible) before acting; for a plain
	 * "open and click" use {@link triggerSelectionAction}.
	 */
	async openSelectionActionsMenu(): Promise<void> {
		await this.getSelectionActionsButton().click({ force: true })
	}

	/**
	 * A selection action entry. Matched at page level on the product-owned
	 * attribute because selection actions can render inline or inside the menu popover.
	 */
	getSelectionActionEntry(actionId: string): Locator {
		return this.page.locator(`[data-cy-files-list-selection-action="${actionId}"]`)
	}

	async triggerSelectionAction(actionId: string): Promise<void> {
		await this.openSelectionActionsMenu()
		// NcActionButton renders as <li data-cy-...><button role="menuitem">
		const actionButton = this.getSelectionActionEntry(actionId).locator('button')
		await actionButton.waitFor({ state: 'visible' })
		await actionButton.click()
	}

	getRenameInputForFile(filename: string): Locator {
		return this.getRowForFile(filename).getByRole('textbox', { name: 'Filename' })
	}

	getRenameInputForFolder(foldername: string): Locator {
		return this.getRowForFile(foldername).getByRole('textbox', { name: 'Folder name' })
	}

	async navigateToFolder(dirPath: string): Promise<void> {
		for (const directory of dirPath.split('/').filter(Boolean)) {
			await this.getRowForFile(directory)
				.getByRole('button')
				.filter({ hasText: directory })
				.click()
		}
	}

	/**
	 * Create a folder through the upload picker's "New" menu and wait for the
	 * MKCOL to land. The upload-picker and new-node-dialog hooks are product-owned
	 * data-cy attributes (no stable accessible name to target by role).
	 */
	async createFolder(folderName: string): Promise<void> {
		const created = this.page.waitForResponse(
			(r) => r.request().method() === 'MKCOL' && r.url().includes('/remote.php/dav/files/'),
		)

		await this.page.locator('[data-cy-upload-picker]')
			.getByRole('button', { name: 'New' })
			.click()
		await this.page.locator('[data-cy-upload-picker-menu-entry="newFolder"]')
			.getByRole('menuitem')
			.click()

		const dialog = this.page.locator('[data-cy-files-new-node-dialog]')
		await dialog.getByRole('textbox').fill(folderName)
		await dialog.locator('[data-cy-files-new-node-dialog-submit]').click()

		await created
		await this.getRowForFile(folderName).waitFor({ state: 'visible' })
	}
}
