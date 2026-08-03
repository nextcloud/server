/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'

test.describe('files: Set default view', () => {
	test('Defaults to the "files" view', async ({ page, filesListPage, filesNavigation }) => {
		await filesListPage.open()

		await expect(page).toHaveURL(/\/apps\/files\/files/)
		await expect(filesListPage.getBreadcrumbs().getByRole('button').first()).toHaveText('All files')

		const dialog = await filesNavigation.openSettings()
		await expect(dialog.getByRole('group', { name: 'Default view' }).getByRole('radio', { name: 'All files' })).toBeChecked()
	})

	test('Can set it to personal files', async ({ page, filesListPage, filesNavigation }) => {
		await filesListPage.open()

		const dialog = await filesNavigation.openSettings()
		// The next page load only honors the new default once the config PUT has
		// been persisted, so wait for it before re-navigating.
		const saved = page.waitForResponse((r) => r.url().includes('/apps/files/api/v1/config/default_view'))
		// The radio input is `hidden-visually` and can sit below the dialog fold, so
		// clicking its visible label is more reliable than checking the input.
		await dialog.getByRole('group', { name: 'Default view' })
			.getByText('Personal files', { exact: true })
			.click()
		await saved
		await expect(dialog.getByRole('group', { name: 'Default view' }).getByRole('radio', { name: 'Personal files' })).toBeChecked()
		await filesNavigation.closeSettings()

		await filesListPage.open()
		await expect(page).toHaveURL(/\/apps\/files\/personal/)
		await expect(filesListPage.getBreadcrumbs().getByRole('button').first()).toHaveText('Personal files')
	})
})

test.describe('files: Hide or show hidden files', () => {
	// Seed a hidden file, a visible file and a hidden folder for the acting user.
	test.beforeEach(async ({ page, user }) => {
		await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/.file')
		await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/visible-file')
		await mkdir(page.request, user, '/.folder')
	})

	for (const { view, viewId } of [
		{ view: 'All files', viewId: '' },
		{ view: 'Personal files', viewId: 'personal' },
	]) {
		test.describe(`view: ${view}`, () => {
			test('hides dot-files by default', async ({ filesListPage }) => {
				await filesListPage.open(viewId || undefined)

				await expect(filesListPage.getRowForFile('visible-file')).toBeVisible()
				await expect(filesListPage.getRowForFile('.file')).toHaveCount(0)
				await expect(filesListPage.getRowForFile('.folder')).toHaveCount(0)
			})

			test('can show hidden files', async ({ filesListPage, filesNavigation }) => {
				await filesListPage.open(viewId || undefined)
				await filesNavigation.setShowHiddenFiles(true)

				await expect(filesListPage.getRowForFile('.file')).toBeVisible()
				await expect(filesListPage.getRowForFile('.folder')).toBeVisible()
			})
		})
	}

	test.describe('view: Recent files', () => {
		// Recent also surfaces files nested in a hidden folder
		test.beforeEach(async ({ page, user }) => {
			await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/.folder/other-file')
		})

		test('hides dot-files by default', async ({ filesListPage }) => {
			await filesListPage.open('recent')

			await expect(filesListPage.getRowForFile('visible-file')).toBeVisible()
			await expect(filesListPage.getRowForFile('.file')).toHaveCount(0)
			await expect(filesListPage.getRowForFile('.folder')).toHaveCount(0)
			await expect(filesListPage.getRowForFile('other-file')).toHaveCount(0)
		})

		test('can show hidden files', async ({ filesListPage, filesNavigation }) => {
			await filesListPage.open('recent')
			await filesNavigation.setShowHiddenFiles(true)

			await expect(filesListPage.getRowForFile('visible-file')).toBeVisible()
			await expect(filesListPage.getRowForFile('.file')).toBeVisible()
			await expect(filesListPage.getRowForFile('.folder')).toBeVisible()
			await expect(filesListPage.getRowForFile('other-file')).toBeVisible()
		})
	})
})
