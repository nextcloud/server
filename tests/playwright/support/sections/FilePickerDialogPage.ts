/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'
import { DAV_FILES_ENDPOINT } from '../utils/dav.ts'

/**
 * The file picker dialog of `@nextcloud/dialogs`, used by every feature that
 * lets the user choose a file or folder (custom background, ownership
 * transfer, …).
 *
 * The confirm button is provided by the feature opening the picker, so its
 * label is passed to {@link confirm} instead of being hardcoded here.
 */
export class FilePickerDialogPage {
	constructor(protected readonly page: Page) {}

	/** The open file picker dialog. */
	dialog(): Locator {
		return this.page.getByRole('dialog')
	}

	/**
	 * A file or folder entry of the directory currently listed.
	 *
	 * Rows are matched by their text rather than by accessible name: the picker
	 * renders the base name and the extension of a file as two elements, which
	 * the accessible name computation joins with a space ("file .txt").
	 *
	 * @param name - The name of the file or folder
	 */
	getRow(name: string): Locator {
		return this.dialog().getByRole('row').filter({ hasText: name })
	}

	/**
	 * Navigate into a folder and wait for its content to be listed.
	 *
	 * Clicking a folder always navigates into it — a folder cannot be selected,
	 * it is picked by navigating into it and confirming with no selection.
	 *
	 * @param name - The name of the folder to enter
	 */
	async openFolder(name: string): Promise<void> {
		const listed = this.page.waitForResponse((r) => r.request().method() === 'PROPFIND' && DAV_FILES_ENDPOINT.test(r.url()))
		await this.getRow(name).click()
		await listed
	}

	/**
	 * Select a file row (only files can be selected, see {@link openFolder}).
	 *
	 * @param name - The name of the file to select
	 */
	async selectFile(name: string): Promise<void> {
		const row = this.getRow(name)
		await row.click()
		await expect(row).toHaveAttribute('aria-selected', 'true')
	}

	/**
	 * Confirm the picker with the button carrying the given label.
	 *
	 * @param label - The label of the confirmation button
	 */
	async confirm(label: string | RegExp): Promise<void> {
		await this.dialog().getByRole('button', { name: label }).click()
		await expect(this.dialog()).toBeHidden()
	}
}
