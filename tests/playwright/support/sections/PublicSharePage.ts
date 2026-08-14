/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'

/**
 * A public share page as a guest sees it: the page header (download / direct
 * link / federated share), the guest identification menu, and the file-drop
 * view. The file list itself is the regular files list, so use `FilesListPage`
 * for anything below the header.
 */
export class PublicSharePage {
	constructor(private readonly page: Page) {}

	/** Open a share URL (as returned by `createLinkShare`). */
	async open(url: string): Promise<void> {
		await this.page.goto(url)
	}

	/** The page header, which carries the share-level actions. */
	header(): Locator {
		return this.page.getByRole('banner')
	}

	/**
	 * The header's primary action — the first share action, rendered as a button
	 * next to the menu (e.g. "Download"). On small screens there is none: every
	 * action moves into the actions menu.
	 */
	primaryAction(name: string | RegExp): Locator {
		return this.header().getByRole('button', { name })
	}

	/** The header's "More actions" menu (teleported, so matched at page level). */
	actionsMenu(): Locator {
		return this.page.getByRole('menu', { name: 'More actions' })
	}

	/** Open the header's "More actions" menu and return it. */
	async openActionsMenu(): Promise<Locator> {
		await this.header().getByRole('button', { name: /More actions/i }).click()
		const menu = this.actionsMenu()
		await expect(menu).toBeVisible()
		return menu
	}

	/** An entry of the "More actions" menu, e.g. "Direct link". */
	actionsMenuEntry(name: string | RegExp): Locator {
		return this.actionsMenu().getByRole('menuitem', { name })
	}

	/** The guest user menu in the header. */
	private userMenu(): Locator {
		return this.header().getByRole('navigation', { name: /User menu/i })
	}

	/** The guest user menu's toggle, whose avatar reflects the current guest name. */
	userMenuButton(): Locator {
		return this.userMenu().getByRole('button', { name: /User menu/i })
	}

	/**
	 * Open the guest user menu and return its popover. The toggle is a switch, so
	 * it is only clicked while the menu is closed — that keeps the call safe for a
	 * caller that already opened it.
	 */
	async openUserMenu(): Promise<Locator> {
		const menu = this.page.locator('#header-menu-public-page-user-menu')
		if (!(await menu.isVisible())) {
			await this.userMenuButton().click()
		}
		await expect(menu).toBeVisible()
		return menu
	}

	/**
	 * Set (or change) the guest name through the user menu, and wait for the
	 * dialog to be gone again.
	 *
	 * @param name - The public name to submit
	 */
	async setPublicName(name: string): Promise<void> {
		const menu = await this.openUserMenu()
		await menu.getByRole('link', { name: /(Set|Change) public name/i }).click()

		const dialog = this.guestIdentificationDialog()
		await expect(dialog).toBeVisible()
		await dialog.getByRole('textbox', { name: 'Name' }).fill(name)
		await dialog.getByRole('button', { name: 'Submit name' }).click()
		await expect(dialog).toBeHidden()
	}

	/** The dialog that asks a guest for their name. */
	guestIdentificationDialog(): Locator {
		return this.page.getByRole('dialog', { name: /Guest identification/i })
	}

	/**
	 * Answer the name prompt a file request shows before its upload form. Unlike
	 * the user menu's dialog this one is titled after the destination folder.
	 *
	 * @param name - The guest name to submit
	 */
	async submitGuestName(name: string): Promise<void> {
		const dialog = this.page.getByRole('dialog', { name: /Upload files to/ })
		await expect(dialog).toBeVisible()
		await dialog.getByRole('textbox', { name: 'Name' }).fill(name)
		await dialog.getByRole('button', { name: 'Submit name' }).click()
		await expect(dialog).toBeHidden()
	}

	/**
	 * The file-drop view's description, naming the destination folder. The
	 * heading above it is the share label (or "File drop"), so this is what
	 * identifies the shared folder on a file-drop page.
	 *
	 * @param folder - The name of the folder files are uploaded to
	 */
	fileDropDescription(folder: string): Locator {
		return this.page.getByText(`Upload files to ${folder}.`, { exact: true })
	}

	/** The file-drop view's heading — the share label, or "File drop" by default. */
	fileDropHeading(name = 'File drop'): Locator {
		return this.page.getByRole('heading', { name })
	}

	/**
	 * The federated-share dialog behind the header's "Add to your Nextcloud"
	 * action.
	 */
	federatedShareDialog(): Locator {
		return this.page.getByRole('dialog', { name: /Add to your Nextcloud/i })
	}

	/**
	 * Pick files for an upload menu entry ("Upload files" / "Upload folders"),
	 * driving the file chooser the way a user would instead of writing to the
	 * hidden input.
	 *
	 * @param entry - The upload menu entry to use
	 * @param files - The files to select
	 */
	async uploadFiles(
		entry: string | RegExp,
		files: { name: string, mimeType: string, buffer: Buffer }[],
	): Promise<void> {
		const chooser = this.page.waitForEvent('filechooser')
		await this.page.getByRole('menuitem', { name: entry }).click()
		await (await chooser).setFiles(files)
	}
}
