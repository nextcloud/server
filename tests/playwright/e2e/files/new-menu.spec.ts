/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator } from '@playwright/test'

import { expect, test } from '../../support/fixtures/files-page.ts'

/** Read a native input's constraint-validation message (set by the app). */
function validationMessage(input: Locator): Promise<string> {
	return input.evaluate((el: HTMLInputElement) => el.validationMessage)
}

test.describe('"New"-menu', () => {
	test.beforeEach(async ({ filesListPage }) => {
		await filesListPage.open()
	})

	test('Create new folder', async ({ filesListPage }) => {
		await filesListPage.createFolder('A new folder')
		await expect(filesListPage.getRowForFile('A new folder')).toBeVisible()
	})

	test('Does not allow creating forbidden folder names', async ({ filesListPage }) => {
		const dialog = await filesListPage.openNewFolderDialog()
		const input = dialog.getByRole('textbox', { name: 'Folder name' })
		await input.fill('.htaccess')

		await expect.poll(() => validationMessage(input)).toMatch(/reserved name/i)
		await expect(dialog.getByRole('button', { name: 'Create' })).toBeDisabled()
	})

	test('Does not allow creating folders with already existing names', async ({ filesListPage }) => {
		await filesListPage.createFolder('already exists')

		const dialog = await filesListPage.openNewFolderDialog()
		const input = dialog.getByRole('textbox', { name: 'Folder name' })
		await input.fill('already exists')

		await expect.poll(() => validationMessage(input)).toMatch(/already in use/i)
		await expect(dialog.getByRole('button', { name: 'Create' })).toBeDisabled()
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/47530
	 */
	test('Create same folder in child folder', async ({ filesListPage }) => {
		await filesListPage.createFolder('folder')
		await filesListPage.createFolder('other folder')
		await filesListPage.navigateToFolder('folder')

		const dialog = await filesListPage.openNewFolderDialog()
		const input = dialog.getByRole('textbox', { name: 'Folder name' })
		await input.fill('other folder')

		// A same-named folder in a different parent is allowed
		await expect.poll(() => validationMessage(input)).toBe('')
		await dialog.getByRole('button', { name: 'Create' }).click()

		await expect(filesListPage.getRowForFile('other folder')).toBeVisible()
	})
})
