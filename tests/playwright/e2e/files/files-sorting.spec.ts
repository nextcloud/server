/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { mkdir, setFavorite, uploadContent } from '../../support/utils/dav.ts'

const DAY = 86400

test.describe('Files: Sorting the file list', () => {
	test('Files are sorted by name ascending by default', async ({ page, user, filesListPage }) => {
		const request = page.request
		await uploadContent(request, user, Buffer.alloc(0), 'text/plain', '/1 first.txt')
		await uploadContent(request, user, Buffer.alloc(0), 'text/plain', '/z last.txt')
		await uploadContent(request, user, Buffer.alloc(0), 'text/plain', '/A.txt')
		await uploadContent(request, user, Buffer.alloc(0), 'text/plain', '/Ä.txt')
		await mkdir(request, user, '/m')
		await mkdir(request, user, '/4')
		await filesListPage.open()

		// Folders first (4, m), then files by natural name order
		await expect.poll(() => filesListPage.getRowNames()).toEqual([
			'4',
			'm',
			'1 first.txt',
			'A.txt',
			'Ä.txt',
			'welcome.txt',
			'z last.txt',
		])
	})

	/** Regression test of https://github.com/nextcloud/server/issues/45829 */
	test('Filenames with numbers are sorted by name ascending by default', async ({ page, user, filesListPage }) => {
		const request = page.request
		await uploadContent(request, user, Buffer.alloc(0), 'text/plain', '/name.txt')
		await uploadContent(request, user, Buffer.alloc(0), 'text/plain', '/name_03.txt')
		await uploadContent(request, user, Buffer.alloc(0), 'text/plain', '/name_02.txt')
		await uploadContent(request, user, Buffer.alloc(0), 'text/plain', '/name_01.txt')
		// remove the default file so only the seeded ones are asserted
		await filesListPage.open()

		await expect.poll(() => filesListPage.getRowNames()).toEqual([
			'name.txt',
			'name_01.txt',
			'name_02.txt',
			'name_03.txt',
			'welcome.txt',
		])
	})

	test('Can sort by size', async ({ page, user, filesListPage }) => {
		const request = page.request
		await uploadContent(request, user, Buffer.alloc(0), 'text/plain', '/1 tiny.txt')
		await uploadContent(request, user, Buffer.alloc(1024, 'a'), 'text/plain', '/z big.txt')
		await uploadContent(request, user, Buffer.alloc(512, 'a'), 'text/plain', '/a medium.txt')
		await mkdir(request, user, '/folder')
		await filesListPage.open()

		await filesListPage.sortByColumn('Size')
		await expect(filesListPage.getColumnHeader('Size')).toHaveAttribute('aria-sort', 'ascending')
		await expect.poll(() => filesListPage.getRowNames()).toEqual([
			'folder',
			'1 tiny.txt',
			'welcome.txt',
			'a medium.txt',
			'z big.txt',
		])

		await filesListPage.sortByColumn('Size')
		await expect(filesListPage.getColumnHeader('Size')).toHaveAttribute('aria-sort', 'descending')
		await expect.poll(() => filesListPage.getRowNames()).toEqual([
			'folder',
			'z big.txt',
			'a medium.txt',
			'welcome.txt',
			'1 tiny.txt',
		])
	})

	test('Can sort by mtime', async ({ page, user, filesListPage }) => {
		const request = page.request
		const now = Date.now() / 1000
		await uploadContent(request, user, Buffer.alloc(0), 'text/plain', '/1.txt', now - DAY - 1000)
		await uploadContent(request, user, Buffer.alloc(1024, 'a'), 'text/plain', '/z.txt', now - DAY)
		await uploadContent(request, user, Buffer.alloc(512, 'a'), 'text/plain', '/a.txt', now - DAY - 500)
		await filesListPage.open()

		await filesListPage.sortByColumn('Modified')
		await expect(filesListPage.getColumnHeader('Modified')).toHaveAttribute('aria-sort', 'ascending')
		await expect.poll(() => filesListPage.getRowNames()).toEqual(['welcome.txt', 'z.txt', 'a.txt', '1.txt'])

		await filesListPage.sortByColumn('Modified')
		await expect(filesListPage.getColumnHeader('Modified')).toHaveAttribute('aria-sort', 'descending')
		await expect.poll(() => filesListPage.getRowNames()).toEqual(['1.txt', 'a.txt', 'z.txt', 'welcome.txt'])
	})

	test('Favorites are sorted first', async ({ page, user, filesListPage }) => {
		const request = page.request
		const now = Date.now() / 1000
		await uploadContent(request, user, Buffer.alloc(0), 'text/plain', '/1.txt', now - DAY - 1000)
		await uploadContent(request, user, Buffer.alloc(1024, 'a'), 'text/plain', '/z.txt', now - DAY)
		await uploadContent(request, user, Buffer.alloc(512, 'a'), 'text/plain', '/a.txt', now - DAY - 500)
		await setFavorite(request, user, '/a.txt')
		await filesListPage.open()

		// By name - ascending (default): favorite a.txt first
		await expect(filesListPage.getColumnHeader('Name')).toHaveAttribute('aria-sort', 'ascending')
		await expect.poll(() => filesListPage.getRowNames()).toEqual(['a.txt', '1.txt', 'welcome.txt', 'z.txt'])

		// By name - descending
		await filesListPage.sortByColumn('Name')
		await expect(filesListPage.getColumnHeader('Name')).toHaveAttribute('aria-sort', 'descending')
		await expect.poll(() => filesListPage.getRowNames()).toEqual(['a.txt', 'z.txt', 'welcome.txt', '1.txt'])

		// By size - ascending
		await filesListPage.sortByColumn('Size')
		await expect(filesListPage.getColumnHeader('Size')).toHaveAttribute('aria-sort', 'ascending')
		await expect.poll(() => filesListPage.getRowNames()).toEqual(['a.txt', '1.txt', 'welcome.txt', 'z.txt'])

		// By size - descending
		await filesListPage.sortByColumn('Size')
		await expect(filesListPage.getColumnHeader('Size')).toHaveAttribute('aria-sort', 'descending')
		await expect.poll(() => filesListPage.getRowNames()).toEqual(['a.txt', 'z.txt', 'welcome.txt', '1.txt'])

		// By mtime - ascending
		await filesListPage.sortByColumn('Modified')
		await expect(filesListPage.getColumnHeader('Modified')).toHaveAttribute('aria-sort', 'ascending')
		await expect.poll(() => filesListPage.getRowNames()).toEqual(['a.txt', 'welcome.txt', 'z.txt', '1.txt'])

		// By mtime - descending
		await filesListPage.sortByColumn('Modified')
		await expect(filesListPage.getColumnHeader('Modified')).toHaveAttribute('aria-sort', 'descending')
		await expect.poll(() => filesListPage.getRowNames()).toEqual(['a.txt', '1.txt', 'z.txt', 'welcome.txt'])
	})

	test('Sorting works after switching view twice', async ({ page, user, filesListPage, filesNavigation }) => {
		const request = page.request
		await uploadContent(request, user, Buffer.alloc(0), 'text/plain', '/1 tiny.txt')
		await uploadContent(request, user, Buffer.alloc(1024, 'a'), 'text/plain', '/z big.txt')
		await uploadContent(request, user, Buffer.alloc(512, 'a'), 'text/plain', '/a medium.txt')
		await mkdir(request, user, '/folder')
		await filesListPage.open()

		// Toggle size sort twice on the files view
		await filesListPage.sortByColumn('Size')
		await filesListPage.sortByColumn('Size')

		// Switch to personal and toggle twice again
		await filesNavigation.getNavigationItem('personal').click()
		await filesListPage.sortByColumn('Size')
		await filesListPage.sortByColumn('Size')

		// Back to files view and assert sorting still works
		await filesNavigation.getNavigationItem('files').click()

		await filesListPage.sortByColumn('Size')
		await expect(filesListPage.getColumnHeader('Size')).toHaveAttribute('aria-sort', 'ascending')
		await expect.poll(() => filesListPage.getRowNames()).toEqual([
			'folder',
			'1 tiny.txt',
			'welcome.txt',
			'a medium.txt',
			'z big.txt',
		])

		await filesListPage.sortByColumn('Size')
		await expect(filesListPage.getColumnHeader('Size')).toHaveAttribute('aria-sort', 'descending')
		await expect.poll(() => filesListPage.getRowNames()).toEqual([
			'folder',
			'z big.txt',
			'a medium.txt',
			'welcome.txt',
			'1 tiny.txt',
		])
	})
})
