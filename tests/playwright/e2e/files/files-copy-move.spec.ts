/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'

const EMPTY = Buffer.alloc(0)

test.describe('Files: Move or copy files', () => {
	test('can copy a file to a new folder', async ({ page, user, filesListPage, copyMoveDialog }) => {
		await uploadContent(page.request, user, EMPTY, 'text/plain', '/original.txt')
		await mkdir(page.request, user, '/new-folder')
		await filesListPage.open()

		await filesListPage.triggerActionForFile('original.txt', 'move-copy')
		await copyMoveDialog.copyToFolder('new-folder')

		await filesListPage.navigateToFolder('new-folder')
		await expect(page).toHaveURL(/dir=\/new-folder/)
		await expect(filesListPage.getRowForFile('original.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('new-folder')).toHaveCount(0)
	})

	test('can move a file to a new folder', async ({ page, user, filesListPage, copyMoveDialog }) => {
		await uploadContent(page.request, user, EMPTY, 'text/plain', '/original.txt')
		await mkdir(page.request, user, '/new-folder')
		await filesListPage.open()

		await filesListPage.triggerActionForFile('original.txt', 'move-copy')
		await copyMoveDialog.moveToFolder('new-folder')

		// Moved out of the current folder
		await expect(filesListPage.getRowForFile('new-folder')).toBeVisible()
		await expect(filesListPage.getRowForFile('original.txt')).toHaveCount(0)

		await filesListPage.navigateToFolder('new-folder')
		await expect(page).toHaveURL(/dir=\/new-folder/)
		await expect(filesListPage.getRowForFile('original.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('new-folder')).toHaveCount(0)
	})

	/** Regression: https://github.com/nextcloud/server/issues/41768 */
	test('can move a file to a folder with a similar name', async ({ page, user, filesListPage, copyMoveDialog }) => {
		await uploadContent(page.request, user, EMPTY, 'text/plain', '/original')
		await mkdir(page.request, user, '/original folder')
		await filesListPage.open()

		await filesListPage.triggerActionForFile('original', 'move-copy')
		await copyMoveDialog.moveToFolder('original folder')

		await expect(filesListPage.getRowForFile('original folder')).toBeVisible()
		await expect(filesListPage.getRowForFile('original')).toHaveCount(0)

		await filesListPage.navigateToFolder('original folder')
		await expect(page).toHaveURL(/dir=\/original%20folder/)
		await expect(filesListPage.getRowForFile('original')).toBeVisible()
		await expect(filesListPage.getRowForFile('original folder')).toHaveCount(0)
	})

	test('can move a file to its parent folder', async ({ page, user, filesListPage, copyMoveDialog }) => {
		await mkdir(page.request, user, '/new-folder')
		await uploadContent(page.request, user, EMPTY, 'text/plain', '/new-folder/original.txt')
		await filesListPage.open()

		await filesListPage.navigateToFolder('new-folder')
		await expect(page).toHaveURL(/dir=\/new-folder/)

		await filesListPage.triggerActionForFile('original.txt', 'move-copy')
		await copyMoveDialog.goToAllFiles()
		await copyMoveDialog.moveToCurrentFolder()

		// The folder is now empty and the file is gone from it
		await expect(page.getByText('No files in here')).toBeVisible()
		await expect(filesListPage.getRowForFile('original.txt')).toHaveCount(0)

		// Back at the root the file lives next to its former parent
		await filesListPage.open()
		await expect(filesListPage.getRowForFile('new-folder')).toBeVisible()
		await expect(filesListPage.getRowForFile('original.txt')).toBeVisible()
	})

	test('can copy a file to the same folder', async ({ page, user, filesListPage, copyMoveDialog }) => {
		await uploadContent(page.request, user, EMPTY, 'text/plain', '/original.txt')
		await filesListPage.open()

		await filesListPage.triggerActionForFile('original.txt', 'move-copy')
		await copyMoveDialog.copyToCurrentFolder()

		await expect(filesListPage.getRowForFile('original.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('original (1).txt')).toBeVisible()
	})

	test('can copy a file multiple times to the same folder', async ({ page, user, filesListPage, copyMoveDialog }) => {
		await uploadContent(page.request, user, EMPTY, 'text/plain', '/original.txt')
		await uploadContent(page.request, user, EMPTY, 'text/plain', '/original (1).txt')
		await filesListPage.open()

		await filesListPage.triggerActionForFile('original.txt', 'move-copy')
		await copyMoveDialog.copyToCurrentFolder()

		await expect(filesListPage.getRowForFile('original.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('original (2).txt')).toBeVisible()
	})

	/**
	 * Regression: https://github.com/nextcloud/server/issues/43843
	 * A copied folder with a dot must be renamed correctly ("foo.bar" -> "foo.bar (1)").
	 */
	test('can copy a folder to the same folder', async ({ page, user, filesListPage, copyMoveDialog }) => {
		await mkdir(page.request, user, '/foo.bar')
		await filesListPage.open()

		await filesListPage.triggerActionForFile('foo.bar', 'move-copy')
		await copyMoveDialog.copyToCurrentFolder()

		await expect(filesListPage.getRowForFile('foo.bar')).toBeVisible()
		await expect(filesListPage.getRowForFile('foo.bar (1)')).toBeVisible()
	})

	/** Regression: https://github.com/nextcloud/server/issues/43329 */
	test.describe('escaping file and folder names', () => {
		test('can handle files with special characters', async ({ page, user, filesListPage, copyMoveDialog }) => {
			await uploadContent(page.request, user, EMPTY, 'text/plain', '/original.txt')
			await mkdir(page.request, user, "/can't say")
			await filesListPage.open()

			await filesListPage.triggerActionForFile('original.txt', 'move-copy')
			await copyMoveDialog.copyToFolder("can't say")

			await filesListPage.navigateToFolder("can't say")
			await expect(page).toHaveURL(/dir=\/can%27t%20say/)
			await expect(filesListPage.getRowForFile('original.txt')).toBeVisible()
			await expect(filesListPage.getRowForFile("can't say")).toHaveCount(0)
		})

		/**
		 * Folder names like '<a href="#">foo' must render as text, not be sanitized
		 * into markup — Vue already escapes via v-text.
		 */
		test('does not incorrectly sanitize file names', async ({ page, user, filesListPage, copyMoveDialog }) => {
			await uploadContent(page.request, user, EMPTY, 'text/plain', '/original.txt')
			await mkdir(page.request, user, '/<a href="#">foo')
			await filesListPage.open()

			await filesListPage.triggerActionForFile('original.txt', 'move-copy')
			await copyMoveDialog.copyToFolder('<a href="#">foo')

			await filesListPage.navigateToFolder('<a href="#">foo')
			await expect(page).toHaveURL(/dir=\/%3Ca%20href%3D%22%23%22%3Efoo/)
			await expect(filesListPage.getRowForFile('original.txt')).toBeVisible()
			await expect(filesListPage.getRowForFile('<a href="#">foo')).toHaveCount(0)
		})
	})
})
