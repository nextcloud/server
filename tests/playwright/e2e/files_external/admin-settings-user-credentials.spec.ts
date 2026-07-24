/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { Page } from '@playwright/test'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser, login } from '@nextcloud/e2e-test-server/playwright'
import { expect, test } from '@playwright/test'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { FilesListPage } from '../../support/sections/FilesListPage.ts'
import { uploadContent } from '../../support/utils/dav.ts'
import { AuthBackend, createStorageWithConfig, deleteAllGlobalStorages, StorageBackend } from '../../support/utils/files_external.ts'
import { handlePasswordConfirmation } from '../../support/utils/password-confirmation.ts'

const ACTION_CREDENTIALS_EXTERNAL_STORAGE = 'credentials-external-storage'

// The credentials flow (user-provided / global-user auth) only exists on *global*
// storages, which are mounted for every user. These tests therefore run serially
// in the "admin-settings" project, and tests 2 & 3 share one user to exercise
// global-user credential reuse — hence serial mode and shared `beforeAll` state.
test.describe.configure({ mode: 'serial' })

test.describe('Files user credentials', () => {
	let user1: User
	let user2: User
	let storageUser: User

	test.beforeAll(async ({ playwright, baseURL }) => {
		await runOcc(['app:enable', 'files_external'])

		user1 = await createRandomUser()
		user2 = await createRandomUser()

		// This user holds the WebDAV storage backing the mounts
		storageUser = await createRandomUser()
		const storageContext = await playwright.request.newContext({ baseURL })
		await login(storageContext, storageUser)
		const image = readFileSync(resolve(process.cwd(), 'cypress/fixtures/image.jpg'))
		await uploadContent(storageContext, storageUser, image, 'image/jpeg', '/image.jpg')
		await storageContext.dispose()
	})

	test.afterEach(async () => {
		await deleteAllGlobalStorages()
	})

	test.afterAll(async () => {
		for (const user of [user1, user2, storageUser]) {
			await runOcc(['user:delete', user.userId])
		}
	})

	test('Create a user storage with user credentials', async ({ page, context }) => {
		// Address the server itself can reach (not the public URL)
		const host = 'http://localhost/remote.php/dav/files/' + storageUser.userId
		await createStorageWithConfig(storageUser.userId, StorageBackend.DAV, AuthBackend.UserProvided, { host, secure: 'false' })

		await login(context.request, user1)
		const filesListPage = new FilesListPage(page)

		await page.goto('apps/files/extstoragemounts')
		await expect(filesListPage.getRowForFile(storageUser.userId)).toBeVisible()

		await setStorageCredentials(page, filesListPage, storageUser.userId, storageUser, user1)

		// Credentials are set, so the "enter credentials" action is gone
		await expect(filesListPage.getInlineActionEntryForFile(storageUser.userId, ACTION_CREDENTIALS_EXTERNAL_STORAGE)).toHaveCount(0)

		// Finally, the storage is accessible
		await expectStorageContainsImage(filesListPage, storageUser.userId)
	})

	test('Create a user storage with GLOBAL user credentials', async ({ page, context }) => {
		const host = 'http://localhost/remote.php/dav/files/' + storageUser.userId
		await createStorageWithConfig('storage1', StorageBackend.DAV, AuthBackend.UserGlobalAuth, { host, secure: 'false' })

		await login(context.request, user2)
		const filesListPage = new FilesListPage(page)

		await page.goto('apps/files/extstoragemounts')
		await expect(filesListPage.getRowForFile('storage1')).toBeVisible()

		await setStorageCredentials(page, filesListPage, 'storage1', storageUser, user2)

		await expect(filesListPage.getInlineActionEntryForFile('storage1', ACTION_CREDENTIALS_EXTERNAL_STORAGE)).toHaveCount(0)

		await expectStorageContainsImage(filesListPage, 'storage1')
	})

	test('Create another user storage while reusing GLOBAL user credentials', async ({ page, context }) => {
		const host = 'http://localhost/remote.php/dav/files/' + storageUser.userId
		await createStorageWithConfig('storage2', StorageBackend.DAV, AuthBackend.UserGlobalAuth, { host, secure: 'false' })

		await login(context.request, user2)
		const filesListPage = new FilesListPage(page)

		await page.goto('apps/files/extstoragemounts')
		await expect(filesListPage.getRowForFile('storage2')).toBeVisible()

		// user2 already has global user credentials stored, so no action is needed
		await expect(filesListPage.getInlineActionEntryForFile('storage1', ACTION_CREDENTIALS_EXTERNAL_STORAGE)).toHaveCount(0)
		await expect(filesListPage.getInlineActionEntryForFile('storage2', ACTION_CREDENTIALS_EXTERNAL_STORAGE)).toHaveCount(0)

		await expectStorageContainsImage(filesListPage, 'storage2')
	})
})

/**
 * Open an external storage mount from the files root and assert it contains the
 * backing `image.jpg`. A freshly-credentialed mount needs a moment to connect,
 * so this resets to the root and retries the open until the mount is reachable.
 *
 * @param filesListPage - The files list page object
 * @param mountName - The mount point name to open
 */
async function expectStorageContainsImage(filesListPage: FilesListPage, mountName: string): Promise<void> {
	await expect(async () => {
		await filesListPage.open()
		await filesListPage.getRowNameLinkForFile(mountName).click({ timeout: 5000 })
		await expect(filesListPage.getRowForFile('image.jpg')).toBeVisible({ timeout: 3000 })
	}).toPass({ timeout: 45000 })
}

/**
 * Enter and confirm the credentials for an external storage row: open the
 * credentials dialog through the inline action, submit the storage login, then
 * clear the password-confirmation dialog and wait for the credentials to persist.
 *
 * @param page - The Playwright page
 * @param filesListPage - The files list page object
 * @param mountName - The mount point name of the storage row
 * @param storageUser - The user owning the backing WebDAV storage (its credentials)
 * @param sessionUser - The logged-in user (for the password-confirmation dialog)
 */
async function setStorageCredentials(
	page: Page,
	filesListPage: FilesListPage,
	mountName: string,
	storageUser: User,
	sessionUser: User,
): Promise<void> {
	const credentialsSet = page.waitForResponse((response) => response.request().method() === 'PUT'
		&& response.url().includes('/apps/files_external/userglobalstorages/'))

	await filesListPage.triggerInlineActionForFile(mountName, ACTION_CREDENTIALS_EXTERNAL_STORAGE)

	const storageDialog = page.getByRole('dialog', { name: 'Storage credentials' })
	await expect(storageDialog).toBeVisible()
	await storageDialog.getByRole('textbox', { name: 'Login' }).fill(storageUser.userId)
	await storageDialog.locator('input[type="password"]').fill(storageUser.password)
	await storageDialog.getByRole('button', { name: 'Confirm' }).click()
	await expect(storageDialog).toHaveCount(0)

	// Submitting the credentials triggers a password-confirmation prompt
	await handlePasswordConfirmation(page, sessionUser.password)
	await credentialsSet
}
