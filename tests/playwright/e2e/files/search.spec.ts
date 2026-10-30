/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'

test.describe('files: search', () => {
	// Seed the same file tree for each test's own user (read-only tests → isolated
	// and parallel-safe).
	test.beforeEach(async ({ page, user, filesListPage }) => {
		const request = page.request
		await mkdir(request, user, '/some folder')
		await mkdir(request, user, '/some folder/nested folder')
		await mkdir(request, user, '/other folder')
		await mkdir(request, user, '/12345')
		await uploadContent(request, user, 'content', 'text/plain', '/file.txt')
		await uploadContent(request, user, 'content', 'text/plain', '/some folder/a file.txt')
		await uploadContent(request, user, 'content', 'text/plain', '/some folder/a second file.txt')
		await uploadContent(request, user, 'content', 'text/plain', '/some folder/nested folder/deep file.txt')
		await uploadContent(request, user, 'content', 'text/plain', '/other folder/another file.txt')
		await filesListPage.open()
	})

	test('updates the query on the URL', async ({ page, filesNavigation }) => {
		await filesNavigation.searchEverywhere()
		await filesNavigation.searchInput().fill('file')
		await expect(page).toHaveURL(/query=file($|&)/)
	})

	test('can search globally', async ({ filesNavigation, filesListPage }) => {
		await filesNavigation.searchEverywhere()
		await filesNavigation.searchInput().fill('file')

		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('a file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('a second file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('another file.txt')).toBeVisible()
	})

	test('filter does also search locally', async ({ filesNavigation, filesListPage }) => {
		await filesListPage.navigateToFolder('some folder')
		await expect(filesListPage.getRowForFile('a file.txt')).toBeVisible()

		await filesNavigation.searchInput().fill('file')

		await expect(filesListPage.getRowForFile('a file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('a second file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('deep file.txt')).toBeVisible()
		await expect(filesListPage.getRows()).toHaveCount(3)
	})

	test('See "search everywhere" button', async ({ filesNavigation, filesListPage }) => {
		await expect(filesListPage.getSearchEverywhereButton()).toHaveCount(0)

		await filesNavigation.searchInput().fill('file')
		await expect(filesListPage.getSearchEverywhereButton()).toBeVisible()

		await filesNavigation.searchClearButton().click()
		await expect(filesListPage.getSearchEverywhereButton()).toHaveCount(0)
	})

	test('can make local search a global search', async ({ filesNavigation, filesListPage }) => {
		await filesListPage.navigateToFolder('some folder')
		await expect(filesListPage.getRowForFile('a file.txt')).toBeVisible()

		await filesNavigation.searchInput().fill('file')

		// local results
		await expect(filesListPage.getRowForFile('a file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('a second file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('deep file.txt')).toBeVisible()
		await expect(filesListPage.getRows()).toHaveCount(3)

		await filesListPage.getSearchEverywhereButton().click()

		// global results
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('a file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('deep file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('a second file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('another file.txt')).toBeVisible()
	})

	test('shows empty content when there are no results', async ({ page, filesNavigation, filesListPage }) => {
		await filesListPage.navigateToFolder('some folder')
		await expect(filesListPage.getRowForFile('a file.txt')).toBeVisible()

		await filesNavigation.searchEverywhere()
		await filesNavigation.searchInput().fill('xyz')

		const note = page.getByRole('note').filter({ hasText: /No search results for .xyz./ })
		await expect(note).toBeVisible()
		await expect(note.getByRole('searchbox', { name: /search for files/i })).toHaveValue('xyz')
	})

	test('can alter search', async ({ filesNavigation, filesListPage }) => {
		await filesNavigation.searchEverywhere()
		await filesNavigation.searchInput().fill('other')

		await expect(filesListPage.getRowForFile('another file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('other folder')).toBeVisible()
		await expect(filesListPage.getRows()).toHaveCount(2)

		await filesNavigation.searchInput().fill('other file')
		await expect(filesNavigation.searchInput()).toHaveValue('other file')
		await expect(filesListPage.getRowForFile('another file.txt')).toBeVisible()
		await expect(filesListPage.getRows()).toHaveCount(1)
	})

	test('returns to file list if search is cleared', async ({ filesNavigation, filesListPage }) => {
		await filesNavigation.searchEverywhere()
		await filesNavigation.searchInput().fill('other')

		await expect(filesListPage.getRowForFile('another file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('other folder')).toBeVisible()
		await expect(filesListPage.getRows()).toHaveCount(2)

		await filesNavigation.searchClearButton().click()
		await expect(filesNavigation.searchInput()).toHaveValue('')
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()
		await expect(filesListPage.getRows()).toHaveCount(5)
	})

	/**
	 * Regression: refreshing the search view (via the breadcrumb reload) must keep
	 * the `query` in the URL — guarded by a navigation guard.
	 */
	test('keeps the query in the URL', async ({ page, filesNavigation, filesListPage }) => {
		await filesNavigation.searchEverywhere()
		await filesNavigation.searchInput().fill('file')

		await expect(filesListPage.getRowForFile('a file.txt')).toBeVisible()
		await expect(page).toHaveURL(/query=file($|&)/)

		const search = page.waitForResponse((r) => r.request().method() === 'SEARCH' && r.url().includes('/remote.php/dav/'))
		await filesListPage.reloadCurrentFolder()
		await search

		await expect(filesListPage.getRowForFile('a file.txt')).toBeVisible()
		await expect(page).toHaveURL(/query=file($|&)/)
	})
})
