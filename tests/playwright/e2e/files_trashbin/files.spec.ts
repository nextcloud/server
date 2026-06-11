/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'
import { readFile } from 'node:fs/promises'
import { expect, test } from '../../support/fixtures/files-trashbin-page.ts'
import { mkdir, rm, uploadContent } from '../../support/utils/dav.ts'
import { ALL_PERMISSIONS, ShareType, createShare } from '../../support/utils/sharing.ts'
import { setUserDisplayName } from '../../support/utils/users.ts'

// Trashbin custom columns, keyed by their product-owned column ids
const ORIGINAL_LOCATION = '[data-cy-files-list-row-column-custom="files_trashbin--original-location"]'
const DELETED_BY = '[data-cy-files-list-row-column-custom="files_trashbin--deleted-by"]'
const DELETED_AT = '[data-cy-files-list-row-column-custom="files_trashbin--deleted"]'

/** Run `trigger`, then assert it downloaded `file.txt` with the expected content. */
async function expectFileDownload(page: Page, trigger: () => Promise<void>) {
	const downloadPromise = page.waitForEvent('download')
	await trigger()
	const download = await downloadPromise
	expect(download.suggestedFilename()).toBe('file.txt')
	expect(await readFile(await download.path(), 'utf-8')).toBe('<content>')
}

/** Assert a trashbin row's name and custom columns (the deleted time is always recent). */
async function expectTrashbinRow(row: Locator, name: string, location: string, deletedBy: string) {
	await expect(row).toBeVisible()
	// Name and extension render as separate spans, so the composed text has a space
	await expect(row.locator('[data-cy-files-list-row-name]')).toHaveText(name)
	await expect(row.locator(ORIGINAL_LOCATION)).toHaveText(location)
	await expect(row.locator(DELETED_BY)).toHaveText(deletedBy)
	await expect(row.locator(DELETED_AT)).toHaveText('few seconds ago')
}

test.describe('files_trashbin: download files', () => {
	let fileIds: [number, number]

	test.beforeEach(async ({ page, user, filesListPage }) => {
		const first = await uploadContent(page.request, user, '<content>', 'text/plain', '/file.txt')
		await rm(page.request, user, '/file.txt')
		const second = await uploadContent(page.request, user, '<content>', 'text/plain', '/other-file.txt')
		await rm(page.request, user, '/other-file.txt')
		fileIds = [Number(first), Number(second)]

		await filesListPage.open('trashbin')
	})

	test('can download a file', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFileId(fileIds[0])).toBeVisible()
		await expect(filesListPage.getRowForFileId(fileIds[1])).toBeVisible()

		await expectFileDownload(page, () => filesListPage.triggerActionForFileId(fileIds[0], 'download'))
	})

	test('can download a file using the default action', async ({ page, filesListPage }) => {
		await expectFileDownload(page, () =>
			// The inline "Download" button is the row's default action; force past the sticky header
			filesListPage.getRowForFileId(fileIds[0])
				.getByRole('button', { name: 'Download' })
				.click({ force: true }),
		)
	})

	// Trashbin has no bulk download: the webdav zip-folder plugin does not work for
	// the trashbin (and never did with the legacy ajax download either).
	test('does not offer bulk download', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowCheckboxes()).toHaveCount(2)
		await filesListPage.selectAll()
		await expect(page.getByText('2 selected')).toBeVisible()

		await expect(filesListPage.getSelectionActionEntry('restore')).toBeVisible()
		await expect(filesListPage.getSelectionActionEntry('download')).toHaveCount(0)
	})
})

test.describe('files_trashbin: file row', () => {
	test('shows data for a file deleted by the owner', async ({ user, aliceRequest, filesListPage }) => {
		const fileId = Number(await uploadContent(aliceRequest, user, '<content>', 'text/plain', '/test-file.txt'))
		await rm(aliceRequest, user, '/test-file.txt')

		await filesListPage.open('trashbin')

		// The owner's own deletions render as "You" regardless of display name
		await expectTrashbinRow(filesListPage.getRowForFileId(fileId), 'test-file .txt', 'All files', 'You')
	})

	test('shows data for a file deleted by a sharee in a group share', async ({ user, aliceRequest, bob, bobRequest, group, filesListPage }) => {
		await setUserDisplayName(bobRequest, bob.userId, 'Bob')
		await mkdir(aliceRequest, user, '/Shared')
		await createShare(aliceRequest, '/Shared', group, ALL_PERMISSIONS, ShareType.GROUP)

		const fileId = Number(await uploadContent(aliceRequest, user, '<content>', 'text/plain', '/Shared/test-file.txt'))
		// Bob (the sharee) deletes the file from his view of the shared folder
		await rm(bobRequest, bob, '/Shared/test-file.txt')

		await filesListPage.open('trashbin')

		await expectTrashbinRow(filesListPage.getRowForFileId(fileId), 'test-file .txt', 'Shared', 'Bob')
	})
})
