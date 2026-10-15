/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'
import { FilePickerDialogPage } from './FilePickerDialogPage.ts'

/**
 * The "Transfer ownership of a file or folder" form of the files app, rendered
 * in the personal sharing settings.
 */
export class TransferOwnershipPage {
	private readonly filePicker: FilePickerDialogPage

	constructor(private readonly page: Page) {
		this.filePicker = new FilePickerDialogPage(page)
	}

	/** Open the personal settings section hosting the transfer form. */
	async open(): Promise<void> {
		await this.page.goto('settings/user/sharing')
		await expect(this.getSection()).toBeVisible()
	}

	/** The form, rendered as a labelled fieldset. */
	getSection(): Locator {
		return this.page.getByRole('group', { name: 'Transfer ownership of a file or folder' })
	}

	/** The button opening the file picker. */
	getNodeButton(): Locator {
		return this.getSection().getByRole('button', { name: 'File or folder to transfer' })
	}

	/** The user search picking the account to transfer the ownership to. */
	getNewOwnerCombobox(): Locator {
		return this.getSection().getByRole('combobox', { name: 'New owner' })
	}

	/**
	 * The submit button. Its label names both the file and the new owner as soon
	 * as the form is complete, which is the only accessible confirmation of what
	 * is about to be transferred — the description of {@link getNodeButton} is
	 * not part of the button.
	 */
	getSubmitButton(): Locator {
		return this.getSection().getByRole('button', { name: /^Transfer\b/ })
	}

	/**
	 * The hints of the live region naming what is still missing to submit. They
	 * are rendered only while the respective input is empty, so assert on their
	 * count: being visually hidden they always count as visible.
	 */
	getMissingNodeHint(): Locator {
		return this.getSection().getByText('You need to select a file or folder to transfer ownership.')
	}

	/** See {@link getMissingNodeHint}. */
	getMissingOwnerHint(): Locator {
		return this.getSection().getByText('You need to select a new owner for the file or folder.')
	}

	/**
	 * Pick a file of the users root folder for transfer.
	 *
	 * @param name - The name of the file to transfer
	 */
	async selectFile(name: string): Promise<void> {
		await this.openFilePicker()
		await this.filePicker.selectFile(name)
		await this.filePicker.confirm(`Transfer "${name}"`)
	}

	/**
	 * Pick a folder for transfer.
	 *
	 * The picker has no way to select a folder, so the folder is navigated into
	 * and then confirmed as the current directory.
	 *
	 * @param path - The path of the folder, relative to the users root folder
	 */
	async selectFolder(path: string): Promise<void> {
		const segments = path.split('/').filter(Boolean)
		await this.openFilePicker()
		for (const segment of segments) {
			await this.filePicker.openFolder(segment)
		}
		await this.filePicker.confirm(`Transfer "${segments.at(-1)}"`)
	}

	/**
	 * Pick the users root folder — transferring all of their files at once.
	 * It is confirmed as the picker's initial directory, without a selection.
	 *
	 * @param user - The owner of the files, whose id names their root folder
	 */
	async selectAllFiles(user: User): Promise<void> {
		await this.openFilePicker()
		await this.filePicker.confirm(`Transfer "${user.userId}"`)
	}

	/**
	 * Search for a user and pick them as the new owner. The suggestions are
	 * fetched from the sharees API, debounced, so the request is awaited before
	 * the matching option is picked.
	 *
	 * @param user - The user to receive the ownership
	 */
	async selectNewOwner(user: User): Promise<void> {
		const suggestions = this.page.waitForResponse((r) => r.url().includes('/apps/files_sharing/api/v1/sharees')
			&& new URL(r.url()).searchParams.get('search') === user.userId)

		await this.getNewOwnerCombobox().fill(user.userId)
		await suggestions

		await this.page.getByRole('option', { name: user.userId }).click()
	}

	/** Submit the form and wait for the transfer request to be created. */
	async submit(): Promise<void> {
		const requested = this.page.waitForResponse((r) => r.request().method() === 'POST'
			&& r.url().includes('/apps/files/api/v1/transferownership'))

		await this.getSubmitButton().click()

		expect((await requested).status()).toBe(200)
	}

	private async openFilePicker(): Promise<void> {
		await this.getNodeButton().click()
		await expect(this.filePicker.dialog()).toBeVisible()
	}
}
