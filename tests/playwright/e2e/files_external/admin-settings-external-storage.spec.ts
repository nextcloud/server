/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect, test } from '../../support/fixtures/external-storage-page.ts'
import { deleteAllGlobalStorages } from '../../support/utils/files_external.ts'
import { handlePasswordConfirmation } from '../../support/utils/password-confirmation.ts'

// Runs in the serial "admin-settings" project: it configures *global* external
// storages, which are visible to every user, so it must not run concurrently
// with other tests that enumerate files.
test.describe('files_external settings', () => {
	test.beforeAll(async () => {
		await runOcc(['app:enable', 'files_external'])
	})

	test.beforeEach(async ({ externalStorageSettings }) => {
		await deleteAllGlobalStorages()
		await externalStorageSettings.open()
	})

	test('can see the settings section', async ({ externalStorageSettings }) => {
		await expect(externalStorageSettings.heading()).toBeVisible()
		await expect(externalStorageSettings.table()).toBeVisible()
	})

	test('can see the dialog', async ({ externalStorageSettings }) => {
		const dialog = await externalStorageSettings.openAddDialog()

		await expect(dialog.getByRole('textbox', { name: 'Folder name' })).toBeVisible()
		await expect(externalStorageSettings.comboBox(/External storage/)).toBeVisible()
		await expect(externalStorageSettings.comboBox(/Authentication/)).toBeVisible()
		await expect(externalStorageSettings.comboBox(/Restrict to/)).toBeVisible()

		const createButton = externalStorageSettings.createButton()
		await expect(createButton).toBeVisible()
		await expect(createButton).toHaveAttribute('type', 'submit')
	})

	test('can create storage using the dialog', async ({ page, externalStorageSettings }) => {
		const dialog = await externalStorageSettings.openAddDialog()

		await dialog.getByRole('textbox', { name: 'Folder name' }).fill('My Storage')

		await externalStorageSettings.selectComboBoxOption(/External storage/, 'WebDAV')
		await externalStorageSettings.selectComboBoxOption(/Authentication/, /Login and password/)

		await dialog.getByRole('textbox', { name: 'Login' }).fill('admin')
		await dialog.locator('input[type="password"]').fill('admin')

		// First submit is blocked by the still-empty (required, invalid) URL field
		await externalStorageSettings.createButton().click()

		const urlField = dialog.getByRole('textbox', { name: 'URL' })
		await expect(urlField).toBeVisible()
		await urlField.fill('http://localhost/remote.php/dav/files/admin')

		await dialog.getByRole('checkbox', { name: /Secure/ }).uncheck({ force: true })

		await externalStorageSettings.createButton().click()
		await handlePasswordConfirmation(page, 'admin')

		await expect(page.getByRole('dialog')).toHaveCount(0)

		// The newly created storage is the single row in the table
		await expect(externalStorageSettings.rows()).toHaveCount(1)
		const row = externalStorageSettings.rows().first()
		await expect(row.getByRole('cell', { name: /My Storage/ })).toBeVisible()
		await expect(row.getByRole('cell', { name: /WebDAV/ })).toBeVisible()
		await expect(row.getByRole('cell', { name: /Login and password/ })).toBeVisible()
		await expect(row.getByRole('button', { name: /Edit/ })).toBeVisible()

		const deleteButton = row.getByRole('button', { name: /Delete/ })
		await expect(deleteButton).toBeVisible()
		await deleteButton.click()
		await handlePasswordConfirmation(page, 'admin')

		await expect(externalStorageSettings.rows()).toHaveCount(0)
	})
})
