/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'

/**
 * The version actions, keyed by the stable action id (matching the app's
 * `data-cy-files-versions-version-action`), mapped to the accessible name of the
 * corresponding menu item. Every entry has a distinct label, so version actions
 * are addressed by role + name rather than by data-* selectors.
 */
const VERSION_ACTION_NAMES = {
	label: /Name this version|Edit version name/,
	compare: 'Compare to current version',
	restore: 'Restore version',
	download: 'Download version',
	delete: 'Delete version',
} as const

export type VersionAction = keyof typeof VERSION_ACTION_NAMES

/**
 * A DAV request against the versions collection (list / restore / label / delete).
 */
function isVersionsRequest(url: string) {
	return /\/dav\/versions\/[^/]+\/versions\//.test(url)
}

/**
 * The "Versions" tab of the Files right sidebar (the files_versions app).
 *
 * The tab must already be reachable — i.e. the sidebar is open for a file (via a
 * row's "Details" action). {@link open} then selects the Versions tab and waits
 * for the version list to be fetched.
 */
export class VersionsTab {
	constructor(private readonly page: Page) {}

	/** The Versions tab panel (`role="tabpanel"`, accessible name "Versions"). */
	panel(): Locator {
		return this.page.getByRole('tabpanel', { name: 'Versions' })
	}

	/** The `<ul>` holding the version entries (accessible name "File versions"). */
	list(): Locator {
		return this.panel().getByRole('list', { name: 'File versions' })
	}

	/** All version entries, newest first (index 0 is the current version). */
	versions(): Locator {
		return this.list().getByRole('listitem')
	}

	/** The version entry at the given index (0 = current version). */
	version(index: number): Locator {
		return this.versions().nth(index)
	}

	/**
	 * The author element of a version entry (avatar + display name, or "You" for
	 * the current user). Scoped by the app's `data-cy-files-version-author-name`
	 * hook — the author has no accessible role of its own.
	 */
	authorName(index: number): Locator {
		return this.version(index).locator('[data-cy-files-version-author-name]')
	}

	/** The actions-menu toggle button of a version entry. */
	private menuToggle(index: number): Locator {
		return this.version(index).getByRole('button', { name: /^Actions for version/ })
	}

	/**
	 * Select the Versions tab in the already-open sidebar and wait for the
	 * version list PROPFIND to land, so the entries are present before any
	 * assertion runs.
	 */
	async open(): Promise<void> {
		const fetched = this.page.waitForResponse((r) => r.request().method() === 'PROPFIND' && isVersionsRequest(r.url()))
		await this.page.getByRole('tab', { name: 'Versions' }).click()
		await fetched
		await expect(this.panel()).toBeVisible()
	}

	/**
	 * Open a version's actions menu and return the (teleported) menu locator.
	 *
	 * The NcActions toggle can drop its first click while still (re-)mounting, and
	 * `aria-controls` is set even while the menu is closed, so retry opening until
	 * the referenced menu is actually visible — clicking only while it is closed so
	 * an already-open menu is never toggled shut. Mirrors the files list row menu.
	 */
	private async openMenu(index: number): Promise<Locator> {
		const toggle = this.menuToggle(index)
		await toggle.scrollIntoViewIfNeeded()

		let menu!: Locator
		await expect(async () => {
			let menuId = await toggle.getAttribute('aria-controls')
			const alreadyOpen = !!menuId && await this.page.locator(`#${menuId}`).isVisible()
			if (!alreadyOpen) {
				await toggle.click()
				menuId = await toggle.getAttribute('aria-controls')
			}
			expect(menuId).toBeTruthy()
			menu = this.page.locator(`#${menuId}`)
			await expect(menu).toBeVisible({ timeout: 2000 })
		}).toPass({ timeout: 15000 })
		return menu
	}

	/**
	 * Close the actions menu of the version at `index` by toggling its button.
	 *
	 * The menu is closed with a second click on its own toggle rather than with
	 * Escape: Escape bubbles up and closes the whole sidebar, which would remove
	 * the other version rows a caller may still want to inspect.
	 */
	private async closeMenu(index: number): Promise<void> {
		const toggle = this.menuToggle(index)
		const menuId = await toggle.getAttribute('aria-controls')
		if (menuId && await this.page.locator(`#${menuId}`).isVisible()) {
			await toggle.click()
			await expect(this.page.locator(`#${menuId}`)).toBeHidden()
		}
	}

	private actionItem(menu: Locator, action: VersionAction): Locator {
		return menu.getByRole('menuitem', { name: VERSION_ACTION_NAMES[action] })
	}

	/**
	 * Restore the version at `index` and wait for the restore MOVE to complete.
	 * Only non-current versions offer this action.
	 */
	async restore(index: number): Promise<void> {
		const restored = this.page.waitForResponse((r) => r.request().method() === 'MOVE' && isVersionsRequest(r.url()))
		const menu = await this.openMenu(index)
		await this.actionItem(menu, 'restore').click()
		await restored
	}

	/**
	 * Delete the version at `index` and wait for the DELETE to complete.
	 * Only non-current versions offer this action.
	 */
	async delete(index: number): Promise<void> {
		const deleted = this.page.waitForResponse((r) => r.request().method() === 'DELETE' && isVersionsRequest(r.url()))
		const menu = await this.openMenu(index)
		await this.actionItem(menu, 'delete').click()
		await deleted
	}

	/**
	 * Set (or edit) the label of the version at `index` through the label dialog
	 * and wait for the PROPPATCH to complete.
	 */
	async nameVersion(index: number, name: string): Promise<void> {
		const labelled = this.page.waitForResponse((r) => r.request().method() === 'PROPPATCH' && isVersionsRequest(r.url()))
		const menu = await this.openMenu(index)
		await this.actionItem(menu, 'label').click()

		const dialog = this.page.getByRole('dialog', { name: 'Name this version' })
		await dialog.getByRole('textbox', { name: 'Version name' }).fill(name)
		await dialog.getByRole('button', { name: 'Save version name' }).click()

		await labelled
		await expect(dialog).toBeHidden()
	}

	/**
	 * Fetch the content served by the version's "Download version" action.
	 *
	 * Rather than driving a browser download (which is racy to read back), this
	 * reads the download link's href and fetches it with the page's own
	 * (authenticated) request context — verifying the exact URL the UI would open
	 * serves the expected bytes.
	 */
	async getVersionContent(index: number): Promise<string> {
		const menu = await this.openMenu(index)
		const href = await this.actionItem(menu, 'download').getAttribute('href')
		await this.closeMenu(index)
		if (!href) {
			throw new Error(`Version ${index} has no download link`)
		}
		const response = await this.page.request.get(href)
		return await response.text()
	}

	/** Assert the version at `index` offers no actions menu at all. */
	async expectNoActionsMenu(index: number): Promise<void> {
		await expect(this.menuToggle(index)).toHaveCount(0)
	}

	/** Assert the version at `index` does not offer the given action. */
	async expectActionMissing(index: number, action: VersionAction): Promise<void> {
		const menu = await this.openMenu(index)
		await expect(this.actionItem(menu, action)).toHaveCount(0)
		await this.closeMenu(index)
	}
}
