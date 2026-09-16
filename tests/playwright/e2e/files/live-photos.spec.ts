/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { LivePhoto } from '../../support/utils/live-photos.ts'

import { expect, test } from '../../support/fixtures/files-page.ts'
import { uploadContent } from '../../support/utils/dav.ts'
import { setupLivePhotos } from '../../support/utils/live-photos.ts'

test.describe('Files: Live photos', () => {
	let livePhoto: LivePhoto

	test.beforeEach(async ({ page, user, filesListPage }) => {
		livePhoto = await setupLivePhotos(page.request, user)
		await filesListPage.open()
	})

	test('Only renders the .jpg file', async ({ filesListPage }) => {
		await expect(filesListPage.getRowForFileId(livePhoto.jpgFileId)).toHaveCount(1)
		await expect(filesListPage.getRowForFileId(livePhoto.movFileId)).toHaveCount(0)
	})

	test.describe("'Show hidden files' is enabled", () => {
		test.beforeEach(async ({ filesNavigation, filesListPage }) => {
			await filesNavigation.setShowHiddenFiles(true)
			// The .mov becomes visible once hidden files are shown
			await expect(filesListPage.getRowForFileId(livePhoto.movFileId)).toBeVisible()
		})

		test("Shows both files when 'Show hidden files' is enabled", async ({ filesListPage }) => {
			await expect(filesListPage.getRowForFileId(livePhoto.jpgFileId))
				.toHaveAttribute('data-cy-files-list-row-name', `${livePhoto.fileName}.jpg`)
			await expect(filesListPage.getRowForFileId(livePhoto.movFileId))
				.toHaveAttribute('data-cy-files-list-row-name', `${livePhoto.fileName}.mov`)
		})

		test('Copies both files when copying the .jpg', async ({ filesListPage, copyMoveDialog }) => {
			await filesListPage.triggerActionForFile(`${livePhoto.fileName}.jpg`, 'move-copy')
			await copyMoveDialog.copyToCurrentFolder()
			await filesListPage.reloadCurrentFolder()

			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.jpg`)).toHaveCount(1)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.mov`)).toHaveCount(1)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName} (1).jpg`)).toHaveCount(1)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName} (1).mov`)).toHaveCount(1)
		})

		test('Copies both files when copying the .mov', async ({ filesListPage, copyMoveDialog }) => {
			await filesListPage.triggerActionForFile(`${livePhoto.fileName}.mov`, 'move-copy')
			await copyMoveDialog.copyToCurrentFolder()
			await filesListPage.reloadCurrentFolder()

			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.mov`)).toHaveCount(1)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName} (1).jpg`)).toHaveCount(1)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName} (1).mov`)).toHaveCount(1)
		})

		test('Keeps live photo link when copying folder', async ({ filesNavigation, filesListPage, copyMoveDialog }) => {
			await filesListPage.createFolder('folder')

			// Move the pair into the folder (the .mov follows the .jpg)
			await filesListPage.triggerActionForFile(`${livePhoto.fileName}.jpg`, 'move-copy')
			await copyMoveDialog.moveToFolder('folder')
			// The linked .mov is moved server-side without a client event, so it lingers
			// as a stale row and the folder shows a transient "pending" state. Reload for
			// a settled listing before acting on the folder (else its menu won't open).
			await filesListPage.reloadCurrentFolder()

			// Copy the folder itself into the current directory → "folder (1)"
			await filesListPage.triggerActionForFile('folder', 'move-copy')
			await copyMoveDialog.copyToCurrentFolder()

			await filesListPage.navigateToFolder('folder (1)')
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.jpg`)).toHaveCount(1)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.mov`)).toHaveCount(1)

			// With the link intact, hiding hidden files hides the .mov again
			await filesNavigation.setShowHiddenFiles(false)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.jpg`)).toHaveCount(1)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.mov`)).toHaveCount(0)
		})

		test('Blocks copying live photo into a folder with a colliding .mov', async ({ page, user, filesListPage, copyMoveDialog }) => {
			await filesListPage.createFolder('folder')
			await uploadContent(page.request, user, 'mov file', 'video/mov', `/folder/${livePhoto.fileName}.mov`)
			// Reload so the pre-seeded .mov is in the store before the copy
			await filesListPage.reloadCurrentFolder()

			await filesListPage.triggerActionForFile(`${livePhoto.fileName}.jpg`, 'move-copy')
			await copyMoveDialog.copyToFolder('folder')

			await filesListPage.navigateToFolder('folder')
			// The copy is rejected because the .mov would collide: only the
			// pre-existing .mov remains, neither the .jpg nor a "(1)" copy appears
			await expect(filesListPage.getRows()).toHaveCount(1)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.mov`)).toHaveCount(1)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.jpg`)).toHaveCount(0)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName} (1).jpg`)).toHaveCount(0)
		})

		test('Moves both files when renaming the .jpg', async ({ filesListPage }) => {
			await filesListPage.renameFile(`${livePhoto.fileName}.jpg`, `${livePhoto.fileName}_moved.jpg`)
			await filesListPage.reloadCurrentFolder()

			await expect(filesListPage.getRowForFileId(livePhoto.jpgFileId))
				.toHaveAttribute('data-cy-files-list-row-name', `${livePhoto.fileName}_moved.jpg`)
			await expect(filesListPage.getRowForFileId(livePhoto.movFileId))
				.toHaveAttribute('data-cy-files-list-row-name', `${livePhoto.fileName}_moved.mov`)
		})

		test('Moves both files when renaming the .mov', async ({ filesListPage }) => {
			await filesListPage.renameFile(`${livePhoto.fileName}.mov`, `${livePhoto.fileName}_moved.mov`)
			await filesListPage.reloadCurrentFolder()

			await expect(filesListPage.getRowForFileId(livePhoto.jpgFileId))
				.toHaveAttribute('data-cy-files-list-row-name', `${livePhoto.fileName}_moved.jpg`)
			await expect(filesListPage.getRowForFileId(livePhoto.movFileId))
				.toHaveAttribute('data-cy-files-list-row-name', `${livePhoto.fileName}_moved.mov`)
		})

		test('Deletes both files when deleting the .jpg', async ({ filesListPage }) => {
			await filesListPage.triggerActionForFile(`${livePhoto.fileName}.jpg`, 'delete')
			// The clicked file leaves the list reactively; the linked .mov is deleted
			// server-side, so reload to see the cascaded removal
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.jpg`)).toHaveCount(0)
			await filesListPage.reloadCurrentFolder()
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.jpg`)).toHaveCount(0)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.mov`)).toHaveCount(0)

			await filesListPage.open('trashbin')
			await expect(filesListPage.getRowForFileId(livePhoto.jpgFileId))
				.toHaveAttribute('data-cy-files-list-row-name', new RegExp(`^${livePhoto.fileName}\\.jpg\\.d[0-9]+$`))
			await expect(filesListPage.getRowForFileId(livePhoto.movFileId))
				.toHaveAttribute('data-cy-files-list-row-name', new RegExp(`^${livePhoto.fileName}\\.mov\\.d[0-9]+$`))
		})

		test('Blocks deletion when deleting the .mov', async ({ filesListPage }) => {
			await filesListPage.triggerActionForFile(`${livePhoto.fileName}.mov`, 'delete')
			await filesListPage.reloadCurrentFolder()

			// Deletion of the video alone is not allowed: both files stay
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.jpg`)).toHaveCount(1)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.mov`)).toHaveCount(1)

			await filesListPage.open('trashbin')
			await expect(filesListPage.getRowForFileId(livePhoto.jpgFileId)).toHaveCount(0)
			await expect(filesListPage.getRowForFileId(livePhoto.movFileId)).toHaveCount(0)
		})

		test('Restores both files when restoring the .jpg', async ({ filesListPage }) => {
			await filesListPage.triggerActionForFile(`${livePhoto.fileName}.jpg`, 'delete')
			await filesListPage.open('trashbin')

			await filesListPage.triggerInlineActionForFileId(livePhoto.jpgFileId, 'restore')
			// The clicked file leaves the trashbin reactively; the linked .mov is
			// restored server-side, so reload to see the cascaded removal
			await expect(filesListPage.getRowForFileId(livePhoto.jpgFileId)).toHaveCount(0)
			await filesListPage.open('trashbin')
			await expect(filesListPage.getRowForFileId(livePhoto.jpgFileId)).toHaveCount(0)
			await expect(filesListPage.getRowForFileId(livePhoto.movFileId)).toHaveCount(0)

			await filesListPage.open()
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.jpg`)).toHaveCount(1)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.mov`)).toHaveCount(1)
		})

		test('Blocks restoration when restoring the .mov', async ({ filesListPage }) => {
			await filesListPage.triggerActionForFile(`${livePhoto.fileName}.jpg`, 'delete')
			await filesListPage.open('trashbin')

			await filesListPage.triggerInlineActionForFileId(livePhoto.movFileId, 'restore')
			await filesListPage.reloadCurrentFolder()

			// Restoring the video alone is not allowed: both stay in the trashbin
			await expect(filesListPage.getRowForFileId(livePhoto.jpgFileId)).toHaveCount(1)
			await expect(filesListPage.getRowForFileId(livePhoto.movFileId)).toHaveCount(1)

			await filesListPage.open()
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.jpg`)).toHaveCount(0)
			await expect(filesListPage.getRowForFile(`${livePhoto.fileName}.mov`)).toHaveCount(0)
		})
	})
})
