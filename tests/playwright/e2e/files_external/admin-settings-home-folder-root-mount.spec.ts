/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect, test } from '../../support/fixtures/files-page.ts'
import { AuthBackend, createStorageWithConfig, deleteAllGlobalStorages, setStorageMountOptions, StorageBackend } from '../../support/utils/files_external.ts'

// Mounts a read-only storage at the *root* of the home folder, which makes every
// user's home read-only and flips the global `overwrites_home_folders` app
// config. Both are global side effects, so this runs serially in the
// "admin-settings" project.
test.beforeAll(async () => {
	await runOcc(['app:enable', 'files_external'])
})

test.afterEach(async () => {
	await deleteAllGlobalStorages()
})

test('Does not show write actions on read-only storage mounted at the root of the user\'s home folder', async ({ page, filesListPage }) => {
	const uploadPicker = page.locator('[data-cy-upload-picker]')

	await filesListPage.open()
	expect(await getOverwritesHomeFolders()).toBe('[]')
	await expect(uploadPicker).toBeVisible()

	const id = await createStorageWithConfig('/', StorageBackend.LOCAL, AuthBackend.Null, { datadir: '/tmp' })
	await setStorageMountOptions(id, { readonly: true })
	// HACK: a second storage targeting a subpath is needed for the root one to apply
	await createStorageWithConfig('/a', StorageBackend.LOCAL, AuthBackend.Null, { datadir: '/tmp' })

	await filesListPage.open()
	await filesListPage.open()
	expect(await getOverwritesHomeFolders()).toBe('["files_external"]')
	await expect(uploadPicker).toHaveCount(0)

	await deleteAllGlobalStorages()
	await filesListPage.open()
	expect(await getOverwritesHomeFolders()).toBe('[]')
	await expect(uploadPicker).toBeVisible()
})

/** Read the `overwrites_home_folders` files app config as a trimmed string. */
async function getOverwritesHomeFolders(): Promise<string> {
	const result = await runOcc(['config:app:get', 'files', 'overwrites_home_folders', '--default-value=[]'])
	return result.trim()
}
