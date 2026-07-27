/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'
import { escapeAttributeValue } from '../utils/css.ts'

/** The DAV endpoint all of the picker's and the action's requests go to. */
const DAV_FILES_ENDPOINT = /\/(remote|public)\.php\/dav\/files\//

/**
 * The file-picker dialog opened by the files "Move or copy" action
 * (the NcDialog-based FilePicker from @nextcloud/dialogs).
 *
 * The confirm buttons carry the visible label the user sees: "Copy" / "Move"
 * for the current folder, "Copy to <name>" / "Move to <name>" once a
 * destination is selected. Navigation and confirmation each own their own
 * DAV wait so callers don't have to repeat the register-before/await-after dance.
 */
export class CopyMoveDialogPage {
	constructor(private readonly page: Page) {}

	/** The open file-picker dialog. */
	dialog(): Locator {
		return this.page.getByRole('dialog')
	}

	/**
	 * A destination row inside the picker. The picker tags each row with the
	 * library-owned `data-filename`; rows have no per-folder accessible name to
	 * navigate by (their role name is a generic "Select the row for …").
	 */
	getDestination(name: string): Locator {
		return this.dialog().locator(`[data-filename="${escapeAttributeValue(name)}"]`)
	}

	/**
	 * The breadcrumb path. The picker has two navs — the left view list (labelled
	 * like the dialog, holding the All files / Recent / Favorites shortcuts) and
	 * the breadcrumb. Only the breadcrumb changes the destination folder, so
	 * select it as the nav without the view shortcuts.
	 */
	private breadcrumbs(): Locator {
		return this.dialog()
			.getByRole('navigation')
			.filter({ hasNot: this.page.getByRole('button', { name: 'Favorites' }) })
	}

	/** A confirm button by its exact visible label, e.g. "Copy" or "Move to docs". */
	confirmButton(label: string): Locator {
		return this.dialog().getByRole('button', { name: label, exact: true })
	}

	/**
	 * The skeleton rows the picker renders while it loads a folder listing
	 * (library-owned markup, the rows are `aria-hidden` so they have no role to
	 * address them by).
	 */
	private loadingRows(): Locator {
		return this.dialog().locator('.loading-row')
	}

	/**
	 * Whether `url` is the picker's listing request for `folder` — or for the
	 * user's root if `folder` is undefined.
	 *
	 * @param url - The request URL to check.
	 * @param folder - The folder name to check for, or undefined to check for the root listing.
	 */
	private isListingUrl(url: string, folder?: string): boolean {
		const path = decodeURIComponent(new URL(url).pathname).replace(/\/+$/, '')
		// Capture what follows the DAV root ("/remote.php/dav/files/<user>"), so
		// the root listing is the match with nothing left over.
		const match = path.match(/\/(?:remote|public)\.php\/dav\/files\/[^/]+(?<path>\/.*)?$/)
		if (!match) {
			return false
		}
		const relative = match.groups?.path ?? ''
		return folder === undefined ? relative === '' : relative.endsWith(`/${folder}`)
	}

	/**
	 * Register the wait for the listing the picker loads when its destination folder changes
	 *
	 * @param folder - The folder name to wait for, or undefined to wait for the root listing.
	 */
	private async listingLoaded(folder?: string): Promise<void> {
		await this.page.waitForResponse((r) => r.request().method() === 'PROPFIND'
			&& this.isListingUrl(r.url(), folder))
		await expect(this.loadingRows()).toHaveCount(0)
	}

	/** Navigate into a (possibly nested) folder inside the picker; returns the leaf folder name. */
	async navigateTo(dirPath: string): Promise<string | undefined> {
		const segments = dirPath.split('/').filter(Boolean)
		for (const dir of segments) {
			const loaded = this.listingLoaded(dir)
			await this.getDestination(dir).click()
			await loaded
		}
		return segments.at(-1)
	}

	/** Navigate the destination back to the user's root via the breadcrumb. */
	async goToAllFiles(): Promise<void> {
		const loaded = this.listingLoaded()
		await this.breadcrumbs().getByRole('button', { name: 'All files' }).click()
		await loaded
	}

	private async confirm(label: string, method: 'COPY' | 'MOVE'): Promise<void> {
		const button = this.confirmButton(label)
		// The picker disables its buttons while a listing loads. Get past that
		// before arming the response wait: its timeout starts when it is
		// registered, so an unsettled picker would eat the operation's budget and
		// surface as a bogus "waiting for response" timeout. The timeout is raised
		// over the 5s default because this also covers the picker's initial
		// listing when the caller confirms without navigating first.
		await expect(button).toBeEnabled({ timeout: 15000 })

		const done = this.page.waitForResponse((r) => r.request().method() === method
			&& DAV_FILES_ENDPOINT.test(r.url()))
		await button.click()

		// The picker resolves and closes, then the action checks the destination
		// for conflicts and finally issues the COPY/MOVE.
		await this.dialog().waitFor({ state: 'hidden' })
		await done
		await this.actionSettled()
	}

	/**
	 * Wait for the move/copy action to be fully done. The action shows a
	 * permanent loading toast up front and only hides it once every node has been
	 * transferred and the file list has caught up — a copy into the current
	 * folder stats the new node after the COPY to insert its row. So the toast
	 * going away, not the DAV response, is what says "settled"; it also keeps the
	 * toast from covering the list for whatever the test does next.
	 *
	 * This cannot pass too early: the toast is shown before the COPY/MOVE request
	 * that {@link confirm} has already awaited.
	 */
	private async actionSettled(): Promise<void> {
		await expect(this.page.locator('.toastify.toast-loading')).toHaveCount(0, { timeout: 15000 })
	}

	/** Copy into the folder currently shown in the picker. */
	async copyToCurrentFolder(): Promise<void> {
		await this.confirm('Copy', 'COPY')
	}

	/** Move into the folder currently shown in the picker. */
	async moveToCurrentFolder(): Promise<void> {
		await this.confirm('Move', 'MOVE')
	}

	/** Navigate into the destination folder and copy there. */
	async copyToFolder(dirPath: string): Promise<void> {
		const target = await this.navigateTo(dirPath)
		await this.confirm(`Copy to ${target}`, 'COPY')
	}

	/** Navigate into the destination folder and move there. */
	async moveToFolder(dirPath: string): Promise<void> {
		const target = await this.navigateTo(dirPath)
		await this.confirm(`Move to ${target}`, 'MOVE')
	}
}
