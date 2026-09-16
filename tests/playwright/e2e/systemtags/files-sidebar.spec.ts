/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/systemtags-files-page.ts'
import { uploadContent } from '../../support/utils/dav.ts'
import { clearTags } from '../../support/utils/systemtags.ts'

test.describe('Systemtags: Files sidebar integration', () => {
	test.afterAll(async () => await clearTags())

	test.beforeEach(async ({ page, user, filesListPage }) => {
		await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/file.txt')
		await filesListPage.open()
	})

	test('Can assign tags using the sidebar', async ({ filesListPage, filesSidebar }) => {
		const tag = crypto.randomUUID()

		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		// Open the file details sidebar
		await filesListPage.triggerActionForFile('file.txt', 'details')
		await expect(filesSidebar.sidebar()).toBeVisible()

		// Open the sidebar's Actions menu and click "Add tags"
		await filesSidebar.triggerAction('Add tags')

		// Create and apply the new tag via the picker
		await expect(filesListPage.getTagPicker()).toBeVisible()
		await filesListPage.createNewTagInPicker(tag)
		await filesListPage.applyTagPicker()
	})
})
