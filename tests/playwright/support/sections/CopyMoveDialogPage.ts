/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'
import { escapeAttributeValue } from '../utils/css.ts'

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

	/** Navigate into a (possibly nested) folder inside the picker; returns the leaf folder name. */
	async navigateTo(dirPath: string): Promise<string | undefined> {
		const segments = dirPath.split('/').filter(Boolean)
		for (const dir of segments) {
			await this.getDestination(dir).click()
		}
		return segments.at(-1)
	}

	/** Navigate the destination back to the user's root via the breadcrumb. */
	async goToAllFiles(): Promise<void> {
		await this.breadcrumbs().getByRole('button', { name: 'All files' }).click()
	}

	private async confirm(label: string, method: 'COPY' | 'MOVE'): Promise<void> {
		const done = this.page.waitForResponse(
			(r) => r.request().method() === method
				&& /\/(remote|public)\.php\/dav\/files\//.test(r.url()),
		)
		await this.confirmButton(label).click()
		await done
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
