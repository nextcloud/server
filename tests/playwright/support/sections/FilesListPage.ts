/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

export class FilesListPage {
	constructor(private readonly page: Page) {}

	async open(): Promise<void> {
		await this.page.goto('apps/files')
		await this.page.locator('[data-cy-files-list]').waitFor({ state: 'visible' })
	}

	getRowForFile(filename: string): Locator {
		return this.page.locator(`[data-cy-files-list-row-name="${filename}"]`)
	}

	getRowForFileId(fileid: number): Locator {
		return this.page.locator(`[data-cy-files-list-row-fileid="${fileid}"]`)
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

	async selectAll(): Promise<void> {
		await this.page.locator('[data-cy-files-list-selection-checkbox]')
			.getByRole('checkbox')
			.click({ force: true })
	}

	async triggerSelectionAction(actionId: string): Promise<void> {
		const actionsButton = this.page.locator('[data-cy-files-list-selection-actions]')
			.getByRole('button', { name: 'Actions' })
		await actionsButton.click({ force: true })
		// NcActionButton renders as <li data-cy-...><button role="menuitem">
		const actionButton = this.page.locator(`[data-cy-files-list-selection-action="${actionId}"] button`)
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
}
