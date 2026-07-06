/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { uploadContent } from '../../support/utils/dav.ts'

// Names sort ascending, so the on-screen order is:
// archive.zip, audio.mp3, document.pdf, image.jpg, readme.md, video.mp4, welcome.txt
const files: Record<string, string> = {
	'image.jpg': 'image/jpeg',
	'document.pdf': 'application/pdf',
	'archive.zip': 'application/zip',
	'audio.mp3': 'audio/mpeg',
	'video.mp4': 'video/mp4',
	'readme.md': 'text/markdown',
	'welcome.txt': 'text/plain',
}
const filesCount = Object.keys(files).length

test.describe('Files: Select files', () => {
	test.beforeEach(async ({ page, user, filesListPage }) => {
		// Uploading welcome.txt overwrites the auto-created one, so the list holds exactly these files
		for (const [name, mime] of Object.entries(files)) {
			await uploadContent(page.request, user, Buffer.alloc(0), mime, `/${name}`)
		}
		await filesListPage.open()
	})

	test('selects and deselects all files', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRows()).toHaveCount(filesCount)
		await expect(filesListPage.getRowCheckboxes()).toHaveCount(filesCount)

		await filesListPage.selectAll()
		await expect(page.getByText(`${filesCount} selected`)).toBeVisible()
		await expect(filesListPage.getSelectedRowCheckboxes()).toHaveCount(filesCount)

		await filesListPage.deselectAll()
		await expect(page.getByText(/\d+ selected/)).toHaveCount(0)
		await expect(filesListPage.getSelectedRowCheckboxes()).toHaveCount(0)
	})

	test('selects an arbitrary subset of files', async ({ page, filesListPage }) => {
		const subset = ['image.jpg', 'document.pdf', 'audio.mp3', 'readme.md']

		for (const name of subset) {
			await filesListPage.selectRowForFile(name)
		}

		await expect(page.getByText(`${subset.length} selected`)).toBeVisible()
		await expect(filesListPage.getSelectedRowCheckboxes()).toHaveCount(subset.length)
	})

	test('selects a range of files with the shift key', async ({ page, filesListPage }) => {
		// audio.mp3 -> readme.md spans audio.mp3, document.pdf, image.jpg, readme.md
		await filesListPage.selectRowForFile('audio.mp3')
		await filesListPage.selectRowForFile('readme.md', { shift: true })

		await expect(page.getByText('4 selected')).toBeVisible()
		await expect(filesListPage.getSelectedRowCheckboxes()).toHaveCount(4)
	})
})
