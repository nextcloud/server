/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Request } from '@playwright/test'

import { expect, test } from '../../support/fixtures/files-page.ts'

const FILES = [
	'one.txt',
	'two.txt',
	'three.txt',
	'four.txt',
	'five.txt',
]

test.describe('files: Upload', () => {
	test.beforeEach(async ({ filesListPage }) => {
		await filesListPage.open()
	})

	test('fetches only the uploaded files and reloads the folder once', async ({ page, user, filesListPage }) => {
		const folderUrl = `/remote.php/dav/files/${user.userId}/`
		const isFolderListing = (request: Request) => request.method() === 'PROPFIND'
			&& request.headers().depth === '1'
			&& new URL(request.url()).pathname.endsWith(folderUrl)

		const folderListings: Request[] = []
		page.on('request', (request) => {
			if (isFolderListing(request)) {
				folderListings.push(request)
			}
		})
		const finalListing = page.waitForResponse((response) => isFolderListing(response.request()))

		const chooser = page.waitForEvent('filechooser')
		await page.locator('[data-cy-upload-picker]').first().getByRole('button', { name: 'New' }).click()
		await page.getByRole('menuitem', { name: 'Upload files' }).click()
		await (await chooser).setFiles(FILES.map((name) => ({ name, mimeType: 'text/plain', buffer: Buffer.from(name) })))

		for (const name of FILES) {
			await expect(filesListPage.getRowForFile(name)).toBeVisible()
		}
		await finalListing

		expect(folderListings).toHaveLength(1)
	})
})
