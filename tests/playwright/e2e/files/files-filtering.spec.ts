/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'

// A wide viewport keeps the filter categories as inline buttons (rather than
// collapsing into a "Filters" menu), so the interactions are deterministic.
test.use({ viewport: { width: 1920, height: 1080 } })

test.describe('files: Filter in files list', () => {
	test.beforeEach(async ({ page, user, filesListPage }) => {
		const request = page.request
		await mkdir(request, user, '/folder')
		await uploadContent(request, user, Buffer.alloc(0), 'text/plain', '/file.txt')
		await uploadContent(request, user, Buffer.alloc(0), 'text/csv', '/spreadsheet.csv')
		await uploadContent(request, user, Buffer.alloc(0), 'text/plain', '/folder/text.txt')
		await filesListPage.open()
	})

	test('filters current view by name', async ({ filesNavigation, filesListPage }) => {
		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		await filesNavigation.searchInput().fill('folder')

		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt')).toHaveCount(0)
		await expect(filesListPage.getRowForFile('spreadsheet.csv')).toHaveCount(0)
	})

	test('can reset name filter', async ({ filesNavigation, filesListPage }) => {
		await filesNavigation.searchInput().fill('folder')
		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt')).toHaveCount(0)

		await expect(filesNavigation.searchInput()).toHaveValue('folder')
		await filesNavigation.searchClearButton().click()
		await expect(filesNavigation.searchInput()).toHaveValue('')

		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()
	})

	test('filters current view by type', async ({ filesFilter, filesListPage }) => {
		await expect(filesListPage.getRowForFile('spreadsheet.csv')).toBeVisible()

		await filesFilter.openFilter('Type')
		const spreadsheets = filesFilter.filterOption('Spreadsheets')
		await expect(spreadsheets).toHaveAttribute('aria-pressed', 'false')
		await spreadsheets.click()
		await expect(spreadsheets).toHaveAttribute('aria-pressed', 'true')
		await filesFilter.closeFilterMenu()

		await expect(filesListPage.getRowForFile('spreadsheet.csv')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt')).toHaveCount(0)
		await expect(filesListPage.getRowForFile('folder')).toHaveCount(0)
	})

	test('can reset filter by type', async ({ filesFilter, filesListPage }) => {
		await filesFilter.openFilter('Type')
		await filesFilter.filterOption('Spreadsheets').click()
		await expect(filesFilter.filterOption('Spreadsheets')).toHaveAttribute('aria-pressed', 'true')
		await filesFilter.closeFilterMenu()

		await expect(filesListPage.getRowForFile('folder')).toHaveCount(0)

		await filesFilter.openFilter('Type')
		await filesFilter.filterOption('Spreadsheets').click()
		await expect(filesFilter.filterOption('Spreadsheets')).toHaveAttribute('aria-pressed', 'false')
		await filesFilter.closeFilterMenu()

		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
	})

	test('can reset filter by clicking chip button', async ({ filesFilter, filesListPage }) => {
		await filesFilter.openFilter('Type')
		await filesFilter.filterOption('Spreadsheets').click()
		await expect(filesFilter.filterOption('Spreadsheets')).toHaveAttribute('aria-pressed', 'true')
		await filesFilter.closeFilterMenu()

		await expect(filesListPage.getRowForFile('folder')).toHaveCount(0)

		await filesFilter.removeFilter('Spreadsheets')

		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
	})

	test('keeps type filter when changing the directory', async ({ filesFilter, filesListPage }) => {
		await filesFilter.openFilter('Type')
		await filesFilter.filterOption('Folders').click()
		await expect(filesFilter.filterOption('Folders')).toHaveAttribute('aria-pressed', 'true')
		await filesFilter.closeFilterMenu()

		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt')).toHaveCount(0)
		await expect(filesFilter.activeFilters().filter({ hasText: /Folder/ })).toBeVisible()

		await filesListPage.navigateToFolder('folder')

		await expect(filesFilter.activeFilters().filter({ hasText: /Folder/ })).toBeVisible()
		await expect(filesListPage.getRowForFile('text.txt')).toHaveCount(0)
	})

	/** Regression test of https://github.com/nextcloud/server/issues/47251 */
	test('keeps filter state when changing the directory', async ({ filesFilter, filesListPage }) => {
		await filesFilter.openFilter('Type')
		await filesFilter.filterOption('Folders').click()
		await expect(filesFilter.filterOption('Folders')).toHaveAttribute('aria-pressed', 'true')
		await filesFilter.closeFilterMenu()

		await expect(filesFilter.activeFilters()).toHaveCount(1)
		await expect(filesFilter.activeFilters().filter({ hasText: /Folder/ })).toBeVisible()
		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt')).toHaveCount(0)

		await filesListPage.navigateToFolder('folder')
		await expect(filesListPage.getRowForFile('folder')).toHaveCount(0)

		await expect(filesFilter.activeFilters()).toHaveCount(1)
		await expect(filesFilter.activeFilters().filter({ hasText: /Folder/ })).toBeVisible()

		// The Folders toggle should still be pressed
		await filesFilter.openFilter('Type')
		await expect(filesFilter.filterOption('Folders')).toHaveAttribute('aria-pressed', 'true')
		await filesFilter.closeFilterMenu()
	})

	/** Regression test of https://github.com/nextcloud/server/issues/53038 */
	test('resets name filter when changing the directory', async ({ filesNavigation, filesListPage }) => {
		await filesNavigation.searchInput().fill('folder')
		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt')).toHaveCount(0)

		await filesListPage.navigateToFolder('folder')

		await expect(filesNavigation.searchInput()).toHaveValue('')
		await expect(filesListPage.getRowForFile('text.txt')).toBeVisible()
	})

	test('resets filter when changing the view', async ({ page, filesNavigation, filesListPage }) => {
		await filesNavigation.searchInput().fill('folder')
		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt')).toHaveCount(0)

		await filesNavigation.getNavigationItem('personal').click()
		await expect(page).toHaveURL(/apps\/files\/personal/)

		await expect(filesListPage.getRowForFile('folder')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()
		await expect(filesNavigation.searchInput()).toHaveValue('')
	})
})
