/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/systemtags-files-page.ts'
import { uploadContent } from '../../support/utils/dav.ts'
import { clearTags } from '../../support/utils/systemtags.ts'

test.describe('Systemtags: Files integration', () => {
	test.afterAll(async () => await clearTags())

	test.beforeEach(async ({ page, user, filesListPage }) => {
		await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/file.txt')
		await filesListPage.open()
	})

	test('See first assigned tag in the file list', async ({ page, filesListPage }) => {
		const tag = crypto.randomUUID()

		await filesListPage.openTagPickerForFile('file.txt')
		await filesListPage.createNewTagInPicker(tag)
		await filesListPage.applyTagPicker()
		await page.reload()

		const tagList = filesListPage.getInlineTagsForFile('file.txt')
		await expect(tagList.getByRole('listitem')).toHaveCount(1)
		await expect(tagList.getByRole('listitem')).toBeVisible()
		await expect(tagList.getByRole('listitem')).toContainText(tag)
	})

	test('See two assigned tags are also shown in the file list', async ({ page, filesListPage }) => {
		const tag1 = crypto.randomUUID()
		const tag2 = crypto.randomUUID()

		await filesListPage.openTagPickerForFile('file.txt')
		await filesListPage.createNewTagInPicker(tag1)
		await filesListPage.createNewTagInPicker(tag2)
		await filesListPage.applyTagPicker()
		await page.reload()

		const tagList = filesListPage.getInlineTagsForFile('file.txt')
		// 2 tags, no overflow — both li elements are visible
		await expect(tagList.locator('li')).toHaveCount(2)
		await expect(tagList).toContainText(tag1)
		await expect(tagList).toContainText(tag2)
	})

	test('See three assigned tags result in overflow entry', async ({ page, filesListPage }) => {
		const tag1 = crypto.randomUUID()
		const tag2 = crypto.randomUUID()
		const tag3 = crypto.randomUUID()

		await filesListPage.openTagPickerForFile('file.txt')
		await filesListPage.createNewTagInPicker(tag1)
		await filesListPage.createNewTagInPicker(tag2)
		await filesListPage.createNewTagInPicker(tag3)
		await filesListPage.applyTagPicker()
		await page.reload()

		const tagList = filesListPage.getInlineTagsForFile('file.txt')
		// 3 tags with overflow: 1 visible + "+2" (aria-hidden, role=presentation) + 2 hidden-visually = 4 li elements
		await expect(tagList.locator('li')).toHaveCount(4)

		// First li is the visible tag; second li is the aria-hidden overflow indicator
		await expect(tagList.locator('li').first()).toBeVisible()
		await expect(tagList.locator('li').nth(1)).toContainText('+2')

		// All 3 tag names are present in the list (1 visible, 2 hidden-visually)
		await expect(tagList).toContainText(tag1)
		await expect(tagList).toContainText(tag2)
		await expect(tagList).toContainText(tag3)
	})
})
