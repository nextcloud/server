/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'
import { escapeAttributeValue } from '../utils/css.ts'
import { DAV_FILES_ENDPOINT } from '../utils/dav.ts'

export class FilesListPage {
	constructor(protected readonly page: Page) {}

	/**
	 * Open the files app. Pass a view id (e.g. 'recent') to open that view
	 * instead of the default "All files" list.
	 */
	async open(viewId?: string): Promise<void> {
		await this.page.goto(viewId ? `apps/files/${viewId}` : 'apps/files')
		await this.waitForList()
	}

	getFilesList(): Locator {
		return this.page.locator('[data-cy-files-list]')
	}

	/**
	 * Wait for the file list container to be rendered (e.g. after a direct goto).
	 */
	async waitForList(): Promise<void> {
		await this.getFilesList().waitFor({ state: 'visible', timeout: 20_000 })
	}

	/**
	 * The list's loading indicator.
	 *
	 * Every view renders the same spinner while it fetches updated conent.
	 * In the list header ("File list is reloading") when the folder
	 * already has contents to keep showing. In place of the list
	 * ("Loading current folder") when it does not.
	 * The two spinners are mutually exclusive.
	 */
	getLoadingIndicator(): Locator {
		return this.page.getByRole('img', { name: /^(File list is reloading|Loading current folder)$/ })
	}

	/**
	 * Wait for a pending list fetch to settle, i.e. for the loading indicator to
	 * come and go.
	 *
	 * The indicator is only waited for briefly: a fetch that resolves before the
	 * first poll is never observed as visible, and since the app raises the
	 * loading state synchronously with the action that triggers the fetch, an
	 * absent indicator means the fetch has already finished — so missing its
	 * appearance is not an error. Waiting for it to be gone is what matters.
	 */
	async waitForListLoaded(): Promise<void> {
		const indicator = this.getLoadingIndicator()
		await indicator.waitFor({ state: 'visible', timeout: 2000 }).catch(() => {})
		await indicator.waitFor({ state: 'hidden' })
	}

	/**
	 * Switch the list to grid view and wait for the preference to persist. The
	 * toggle button is labelled "Switch to grid view" (only present in list view).
	 */
	async enableGridView(): Promise<void> {
		const saved = this.page.waitForResponse((r) => r.url().includes('/apps/files/api/v1/config/grid_view'))
		await this.page.getByRole('button', { name: 'Switch to grid view' }).click()
		await saved
	}

	/** The breadcrumbs navigation ("All files › folder › …"). */
	getBreadcrumbs(): Locator {
		return this.page.getByRole('navigation', { name: 'Current directory path' })
	}

	/**
	 * Navigate to an ancestor of the current folder through the breadcrumb
	 *
	 * @param name - The label of the crumb to navigate to
	 */
	async navigateToBreadcrumb(name: string): Promise<void> {
		await this.getBreadcrumbs().getByRole('button', { name, exact: true }).click()
		await this.waitForListLoaded()
	}

	getRowForFile(filename: string): Locator {
		return this.page.locator(`[data-cy-files-list-row-name="${escapeAttributeValue(filename)}"]`)
	}

	/**
	 * The "Search everywhere" chip shown in the list filter bar once a filter is
	 * active (turns a local filter into a global search). It is a button, distinct
	 * from the "Search everywhere" menuitem in the navigation scope menu.
	 */
	getSearchEverywhereButton(): Locator {
		return this.page.getByRole('button', { name: /Search everywhere/i })
	}

	/**
	 * Reload the current folder via the breadcrumb's menu ("Reload content"). The
	 * menu toggle is the current-directory breadcrumb carrying `aria-haspopup="menu"`
	 * (`.last()` skips the collapsed-crumbs overflow menu when the path is deep).
	 *
	 * Opening is retried until the "Reload content" item is visible: like the row
	 * actions menu, the breadcrumb's NcActions can drop the first click while it is
	 * still (re-)mounting — notably in the search view on slower (CI) machines. Only
	 * click while the item is hidden so an already-open menu is never toggled shut.
	 *
	 * Returns once the refetch has settled. The request to await differs by view
	 * (PROPFIND for a folder, SEARCH for the search view, REPORT for favorites),
	 * so this waits on the view-independent loading indicator instead — see
	 * {@link waitForListLoaded}. Callers therefore never act on a list that is
	 * still being replaced.
	 */
	async reloadCurrentFolder(): Promise<void> {
		const toggle = this.getBreadcrumbs().locator('button[aria-haspopup="menu"]').last()
		const reloadItem = this.page.getByRole('menuitem', { name: 'Reload content' })

		await expect(async () => {
			if (!(await reloadItem.isVisible())) {
				await toggle.click()
			}
			await expect(reloadItem).toBeVisible({ timeout: 2000 })
		}).toPass({ timeout: 15000 })

		await reloadItem.click()
		await this.waitForListLoaded()
	}

	getRowForFileId(fileid: number): Locator {
		return this.page.locator(`[data-cy-files-list-row-fileid="${fileid}"]`)
	}

	/** All file rows currently rendered in the list (e.g. for count assertions). */
	getRows(): Locator {
		return this.page.locator('[data-cy-files-list-row-fileid]')
	}

	/** The rendered row names in visual (DOM) order — for sort assertions. */
	async getRowNames(): Promise<string[]> {
		return this.getRows().evaluateAll((rows) => rows.map((row) => row.getAttribute('data-cy-files-list-row-name') ?? ''))
	}

	/** A sortable column header (e.g. "Name", "Size", "Modified") for aria-sort assertions. */
	getColumnHeader(name: string): Locator {
		return this.page.getByRole('columnheader', { name })
	}

	/** Click a column header's sort button to toggle sorting by that column. */
	async sortByColumn(name: string): Promise<void> {
		const saved = this.page.waitForResponse((r) => r.request().method() === 'PUT' && r.url().includes('/index.php/apps/files/api/v1/views'))

		await this.getColumnHeader(name).getByRole('button', { name }).click()

		await saved
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
	 * Open a row's actions menu and return the menu popover locator. Keyed on a
	 * row Locator so it serves both name- and fileid-addressed rows.
	 */
	private async openActionsMenuForRow(row: Locator): Promise<Locator> {
		await expect(row).toBeVisible({ timeout: 15_000 })

		await row.hover()

		const actionsButton = row.getByRole('button', { name: 'Actions' })
		await actionsButton.scrollIntoViewIfNeeded()

		// A row's NcActions can still be (re-)mounting right after a list render
		// (e.g. a freshly reloaded folder), so the first click may be a no-op and
		// `aria-controls` is present even while the menu is closed. Retry opening
		// until the teleported menu is actually visible, clicking only while it is
		// closed so we never toggle an open menu shut. force: true dodges the
		// sticky list header overlapping the button.
		let menu!: Locator
		await expect(async () => {
			let menuId = await actionsButton.getAttribute('aria-controls')
			const alreadyOpen = !!menuId && await this.page.locator(`#${menuId}`).isVisible()
			if (!alreadyOpen) {
				await actionsButton.click({ force: true })
				menuId = await actionsButton.getAttribute('aria-controls')
			}
			expect(menuId).toBeTruthy()
			menu = this.page.locator(`#${menuId}`)
			await expect(menu).toBeVisible({ timeout: 2000 })
		}).toPass({ timeout: 15000 })
		return menu
	}

	private async triggerActionForRow(row: Locator, actionId: string): Promise<void> {
		const menu = await this.openActionsMenuForRow(row)
		const actionEntry = this.getActionButtonInMenu(menu, actionId)
		await actionEntry.waitFor({ state: 'visible' })
		await actionEntry.click()
	}

	/**
	 * Open the row actions menu for a file and return the menu popover locator.
	 * Use this when a test needs to inspect a menu entry (e.g. its label) before
	 * clicking; for a plain "open and click" use {@link triggerActionForFile}.
	 */
	async openActionsMenuForFile(filename: string): Promise<Locator> {
		return this.openActionsMenuForRow(this.getRowForFile(filename))
	}

	getActionButtonInMenu(menu: Locator, actionId: string): Locator {
		// The action button has role="menuitem", so use tag selector not getByRole
		return menu.locator(`[data-cy-files-list-row-action="${actionId}"] button`)
	}

	async triggerActionForFile(filename: string, actionId: string): Promise<void> {
		await this.triggerActionForRow(this.getRowForFile(filename), actionId)
	}

	/**
	 * Like {@link triggerActionForFile} but addresses the row by file id. Trashbin
	 * rows are keyed by id because a deleted file's name is no longer unique (the
	 * same name can be trashed several times).
	 */
	async triggerActionForFileId(fileid: number, actionId: string): Promise<void> {
		await this.triggerActionForRow(this.getRowForFileId(fileid), actionId)
	}

	/**
	 * A file-list-level action button rendered in the list header (e.g.
	 * "empty-trash"), as opposed to a per-row or selection action.
	 */
	getListActionButton(actionId: string): Locator {
		return this.page.locator(`[data-cy-files-list-action="${actionId}"]`)
	}

	async triggerListAction(actionId: string): Promise<void> {
		// .last(): the action can render both inline and inside the overflow menu;
		// the last match is the actionable one
		await this.getListActionButton(actionId).last().click({ force: true })
	}

	/**
	 * The clickable name link of a row. Clicking it opens a folder or previews a
	 * file; for an unavailable storage it is inert.
	 */
	getRowNameLinkForFile(filename: string): Locator {
		return this.getRowForFile(filename).locator('[data-cy-files-list-row-name-link]')
	}

	/**
	 * An inline row action rendered directly in the row's action area (an action
	 * declared `inline: () => true`, e.g. the external-storage credentials action),
	 * as opposed to one nested in the overflow menu.
	 */
	getInlineActionEntryForFile(filename: string, actionId: string): Locator {
		return this.getRowForFile(filename)
			.locator(`[data-cy-files-list-row-action="${actionId}"]`)
	}

	/**
	 * Hover a row and click one of its inline actions. The action area only
	 * renders on hover, so the row must be hovered first.
	 */
	async triggerInlineActionForFile(filename: string, actionId: string): Promise<void> {
		await this.triggerInlineActionForRow(this.getRowForFile(filename), actionId)
	}

	/**
	 * Like {@link triggerInlineActionForFile} but addresses the row by file id.
	 * Trashbin rows carry a `.dNNN` deletion suffix on their name, so they are
	 * keyed by id (e.g. to restore a specific trashed file).
	 */
	async triggerInlineActionForFileId(fileid: number, actionId: string): Promise<void> {
		await this.triggerInlineActionForRow(this.getRowForFileId(fileid), actionId)
	}

	private async triggerInlineActionForRow(row: Locator, actionId: string): Promise<void> {
		await row.hover()
		const button = row.locator(`button[data-cy-files-list-row-action="${actionId}"]`)
		await button.click()
	}

	/**
	 * Rename a file via its row action and wait for the MOVE to land. `fill`
	 * replaces the whole name, then Enter commits the rename.
	 */
	async renameFile(oldName: string, newName: string): Promise<void> {
		// Matches public.php too, so a rename on a public share is awaited as well
		const moved = this.page.waitForResponse((r) => r.request().method() === 'MOVE' && DAV_FILES_ENDPOINT.test(r.url()))

		await this.triggerActionForFile(oldName, 'rename')
		const input = this.getRenameInputForFile(oldName)
		await input.fill(newName)
		await input.press('Enter')

		await moved
	}

	/**
	 * Wait for a row's preview thumbnail to be loaded.
	 *
	 * Generating a preview locks the file on the server, so a MOVE, COPY or
	 * DELETE issued while the thumbnail is still being fetched fails with a
	 * `LockedException`. Await this before any action that writes to the file.
	 */
	async waitForPreviewLoaded(filename: string): Promise<void> {
		await expect(this.getRowForFile(filename).locator('.files-list__row-icon-preview--loaded')).toBeVisible()
	}

	getFavoriteIconForFile(filename: string): Locator {
		return this.getRowForFile(filename).getByRole('img', { name: 'Favorite' })
	}

	/** The overlay shown while dragging files over the list (product-owned hook). */
	getDropArea(): Locator {
		return this.page.locator('[data-cy-files-drag-drop-area]')
	}

	/** The main content area that accepts file drops. */
	getContentArea(): Locator {
		return this.page.locator('main.app-content')
	}

	/** The size cell of a file row (e.g. to assert an uploaded file's size). */
	getRowSizeForFile(filename: string): Locator {
		return this.getRowForFile(filename).locator('[data-cy-files-list-row-size]')
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
		// The toolbar renders the first actions inline and moves the rest into the
		// overflow menu — with only one available action (e.g. "Download" on a
		// public share) there is no menu toggle at all, so only open the menu when
		// the entry is not already on screen.
		const inlineButton = this.getSelectionActionEntry(actionId)
		if (await inlineButton.isVisible()) {
			await inlineButton.click()
			return
		}

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
			// Click the row's name link (the folder-open action) directly. Filtering
			// the row's buttons by the folder name is ambiguous for shared folders,
			// whose row also carries a "Shared by …" action button that can contain
			// the same text.
			const link = this.getRowNameLinkForFile(directory)
			await expect(link).toBeVisible()
			await link.click()

			// Assert the deepest segment of the `dir` query param matches the folder
			// we just opened. Comparing the decoded value (URLSearchParams decodes
			// percent-encoding) rather than building a regex from the raw name avoids
			// two pitfalls with special characters: the app encodes some chars that
			// encodeURIComponent leaves alone (e.g. "'" → "%27"), and regex
			// metacharacters in the name (e.g. "foo.bar (1)") would corrupt the pattern.
			await expect.poll(() => {
				const dir = new URL(this.page.url()).searchParams.get('dir') ?? ''
				return dir.split('/').pop()
			}).toBe(directory)
		}
	}

	/**
	 * Open the upload picker's "New" menu and its "New folder" dialog, returning
	 * the dialog locator. Use for cases that inspect validation before submitting;
	 * for the happy path use {@link createFolder}. The upload-picker container has
	 * no accessible name, so it is still scoped by its product-owned data-cy hook.
	 */
	async openNewFolderDialog(): Promise<Locator> {
		// An empty folder renders a second UploadPicker inside its "no files here"
		// placeholder, so two "New" buttons can exist. The list-header picker is
		// always present and comes first in the DOM — target it.
		await this.page.locator('[data-cy-upload-picker]')
			.getByRole('button', { name: 'New' })
			.first()
			.click()
		await this.page.getByRole('menuitem', { name: 'New folder' }).click()

		const dialog = this.page.getByRole('dialog', { name: /create new folder/i })
		await dialog.waitFor({ state: 'visible' })
		return dialog
	}

	/**
	 * Create a folder through the upload picker's "New" menu and wait for the
	 * MKCOL to land.
	 */
	async createFolder(folderName: string): Promise<void> {
		const created = this.page.waitForResponse((r) => r.request().method() === 'MKCOL' && DAV_FILES_ENDPOINT.test(r.url()))

		const dialog = await this.openNewFolderDialog()
		await dialog.getByRole('textbox', { name: 'Folder name' }).fill(folderName)
		await dialog.getByRole('button', { name: 'Create' }).click()

		await created
		await this.getRowForFile(folderName).waitFor({ state: 'visible' })
	}
}
