/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

/**
 * The file-picker dialog opened by the "Custom background" card/button on
 * Personal settings > Appearance and accessibility > Background and color
 */
export class BackgroundFilePickerDialogPage {
	constructor(private readonly page: Page) {}

	/** The open file-picker dialog. */
	dialog(): Locator {
		return this.page.getByRole('dialog')
	}

	/**
	 * Returns a row (file or folder) from inside the picker.
	 */
	getRow(name: string): Locator {
		return this.dialog().getByTestId('row-name').filter({ hasText: name })
	}

	/** Navigate into a folder. */
	async openFolder(name: string): Promise<void> {
		await this.getRow(name).click()
	}

	/** Select a file row. */
	async selectFile(name: string): Promise<void> {
		await this.getRow(name).click()
	}

	/** Confirm the current selection as the new background. */
	async confirm(): Promise<void> {
		await this.dialog().getByRole('button', { name: 'Select background', exact: true }).click()
	}
}
