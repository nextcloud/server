/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../../support/fixtures/viewer-page.ts'

test.describe('Viewer image editor action', () => {
	test.beforeEach(async ({ filesListPage, uploadMedia }) => {
		await uploadMedia('image1.jpg', 'image1.jpg', 'image/jpeg')
		await filesListPage.open()
		await expect(filesListPage.getRowForFile('image1.jpg')).toBeVisible()
	})

	// The Edit action opens @nextcloud/image-editor; its Cancel closes it.
	test('opens the image editor and closes it on cancel', async ({ page, openFile, viewerPage }) => {
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()

		await viewerPage.runAction('Edit')
		const editor = page.locator('.viewer__image-editor.image-editor')
		await expect(editor).toBeVisible()
		expect(page.url()).toContain('editing=true')

		await page.locator('[data-test="cancel"], [data-test="cancel-icon"]').first().click()
		await expect(editor).toBeHidden()
		expect(page.url()).not.toContain('editing=true')
	})

	// Saving overwrites the file and shows the edited image from its local blob,
	// without a server refetch.
	test('saves and shows the edited image without a refetch', async ({ page, openFile, viewerPage }) => {
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()
		await viewerPage.runAction('Edit')
		await expect(page.locator('.viewer__image-editor.image-editor')).toBeVisible()

		await page.locator('[data-test="save"]').click()

		await expect(page.locator('.viewer__image-editor.image-editor')).toBeHidden()
		await expect(page.locator('oca-viewer-image img')).toHaveAttribute('src', /^blob:/)
	})

	// The `editing=true` URL param reopens the editor on refresh.
	test('reopens the editor from an editing=true URL', async ({ page, openFile, viewerPage }) => {
		await openFile('image1.jpg')
		await viewerPage.waitForOpen()
		await viewerPage.runAction('Edit')
		await expect(page.locator('.viewer__image-editor.image-editor')).toBeVisible()

		await page.reload()
		await expect(page.locator('.viewer__image-editor.image-editor')).toBeVisible()
	})
})
