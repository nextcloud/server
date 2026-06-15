/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'
import { test, expect } from '../../support/fixtures/files-page.ts'
import { mkdir, rm, uploadContent } from '../../support/utils/dav.ts'

/**
 * Run an action that toggles a favorite and wait for the server to store it.
 * Toggling hits POST /apps/files/api/v1/files/<path>; the listener is registered
 * before the action and awaited after, so later assertions see the stored state.
 */
async function toggleFavorite(page: Page, path: string, action: () => Promise<void>): Promise<void> {
	const encoded = path.split('/').map(encodeURIComponent).join('/')
	const response = page.waitForResponse(
		(r) => r.url().includes(`/apps/files/api/v1/files/${encoded}`)
			&& r.request().method() === 'POST',
	)
	await action()
	await response
}

test.describe('Files: Favorites', () => {
	test.beforeEach(async ({ page, user, filesListPage }) => {
		// New users get welcome.txt — remove it so the list contains only our test files
		await rm(page.request, user, '/welcome.txt')
		await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/file.txt')
		await mkdir(page.request, user, '/new folder')
		await filesListPage.open()
	})

	test('marks a file as favorite from the row actions', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		const menu = await filesListPage.openActionsMenuForFile('file.txt')
		const favoriteAction = filesListPage.getActionButtonInMenu(menu, 'favorite')
		await expect(favoriteAction).toContainText('Add to favorites')

		await toggleFavorite(page, 'file.txt', () => favoriteAction.click())

		await expect(filesListPage.getFavoriteIconForFile('file.txt')).toBeVisible()
	})

	test('un-marks a file as favorite from the row actions', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		// Favorite it first
		await toggleFavorite(page, 'file.txt', () => filesListPage.triggerActionForFile('file.txt', 'favorite'))
		await expect(filesListPage.getFavoriteIconForFile('file.txt')).toBeVisible()

		// Re-open the menu — the action now offers to remove the favorite
		const menu = await filesListPage.openActionsMenuForFile('file.txt')
		const favoriteAction = filesListPage.getActionButtonInMenu(menu, 'favorite')
		await expect(favoriteAction).toContainText('Remove from favorites')

		await toggleFavorite(page, 'file.txt', () => favoriteAction.click())

		await expect(filesListPage.getFavoriteIconForFile('file.txt')).toHaveCount(0)
	})

	test('shows favorite folders in the navigation', async ({ page, filesListPage, filesNavigation }) => {
		const favoritesNav = filesNavigation.getNavigationItem('favorites')
		const favoriteEntry = favoritesNav.getByRole('link', { name: 'new folder' })

		await expect(favoritesNav).toBeVisible()
		await expect(favoriteEntry).toHaveCount(0)

		// Favorite the folder — it appears as a (collapsed) child of the favorites view
		await toggleFavorite(page, 'new folder', () => filesListPage.triggerActionForFile('new folder', 'favorite'))
		await filesNavigation.expandNavigationItem('favorites')
		await expect(favoriteEntry).toBeVisible()

		// Un-favorite — it disappears again
		await toggleFavorite(page, 'new folder', () => filesListPage.triggerActionForFile('new folder', 'favorite'))
		await expect(favoriteEntry).toHaveCount(0)
	})

	test('marks a folder as favorite from the sidebar', async ({ page, filesListPage, filesNavigation, filesSidebar }) => {
		await expect(filesListPage.getRowForFile('new folder')).toBeVisible()

		const favoriteEntry = filesNavigation.getNavigationItem('favorites').getByRole('link', { name: 'new folder' })
		await expect(favoriteEntry).toHaveCount(0)

		// Open the sidebar for the folder
		await filesListPage.triggerActionForFile('new folder', 'details')
		await expect(filesSidebar.sidebar()).toBeVisible()

		await toggleFavorite(page, 'new folder', () => filesSidebar.triggerAction('Favorite'))

		await filesSidebar.close()
		await expect(filesSidebar.sidebar()).not.toBeVisible()
		await expect(filesListPage.getFavoriteIconForFile('new folder')).toBeVisible()

		// Favorite survives a reload
		await page.reload()
		await expect(filesListPage.getRowForFile('new folder')).toBeVisible()
		await expect(filesListPage.getFavoriteIconForFile('new folder')).toBeVisible()

		// Un-favorite again from the sidebar
		await filesListPage.triggerActionForFile('new folder', 'details')
		await expect(filesSidebar.sidebar()).toBeVisible()

		await toggleFavorite(page, 'new folder', () => filesSidebar.triggerAction('Unfavorite'))

		await filesSidebar.close()
		await expect(filesSidebar.sidebar()).not.toBeVisible()
		await expect(filesListPage.getFavoriteIconForFile('new folder')).toHaveCount(0)
	})
})
