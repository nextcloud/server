/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect, test } from '../../support/fixtures/files-page.ts'
import { AuthBackend, createStorageWithConfig, StorageBackend, verifyStorage } from '../../support/utils/files_external.ts'

// Each test creates a *personal* (`--user`) storage, mounted only for its own
// random user, so these run fully in parallel without interfering with each
// other or with any other files test.
test.beforeAll(async () => {
	await runOcc(['app:enable', 'files_external'])
})

const invalidHost = 'http://cloud.domain.com/remote.php/dav/files/abcdef123456'

test('Create a failed user storage with invalid url', async ({ page, user, filesListPage }) => {
	const id = await createStorageWithConfig(
		'Storage1',
		StorageBackend.DAV,
		AuthBackend.LoginCredentials,
		{ host: invalidHost, secure: 'false' },
		user,
	)
	await verifyStorage(id)

	await filesListPage.open()

	// The mount may not be in the first PROPFIND; reload once if it is missing yet
	const row = filesListPage.getRowForFile('Storage1')
	if (!await row.isVisible()) {
		await page.reload()
	}

	await expect(row).toBeVisible()
	await expect(filesListPage.getRowNameLinkForFile('Storage1'))
		.toHaveAttribute('title', 'This node is unavailable')

	// Clicking an unavailable storage must not open it (location stays the same)
	const url = page.url()
	await filesListPage.getRowNameLinkForFile('Storage1').click()
	expect(page.url()).toBe(url)
})

test('Create a failed user storage with invalid login credentials', async ({ page, user, filesListPage }) => {
	const id = await createStorageWithConfig(
		'Storage2',
		StorageBackend.DAV,
		AuthBackend.Password,
		{
			host: invalidHost,
			user: 'invaliduser',
			password: 'invalidpassword',
			secure: 'false',
		},
		user,
	)
	await verifyStorage(id)

	await filesListPage.open()

	const row = filesListPage.getRowForFile('Storage2')
	if (!await row.isVisible()) {
		await page.reload()
	}

	await expect(row).toBeVisible()
	await expect(filesListPage.getRowNameLinkForFile('Storage2'))
		.toHaveAttribute('title', 'This node is unavailable')

	const url = page.url()
	await filesListPage.getRowNameLinkForFile('Storage2').click()
	expect(page.url()).toBe(url)
})
