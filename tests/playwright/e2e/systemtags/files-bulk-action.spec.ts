/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { test as baseTest } from '../../support/fixtures/systemtags-files-page.ts'
import { uploadContent } from '../../support/utils/dav.ts'
import { assignTagsToFile, clearTags, createTag } from '../../support/utils/systemtags.ts'

// Extends the base fixture with per-test file IDs so tests in parallel each get
// their own isolated file IDs rather than sharing module-level mutable state.
const test = baseTest.extend<{ fileIds: [string, string] }>({
	fileIds: [async ({ page, user, filesListPage }, use) => {
		const fileId1 = await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/file1.txt')
		const fileId2 = await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/file2.txt')
		await filesListPage.open()
		await use([fileId1, fileId2])
	}, { auto: true }],
})

test.describe('Systemtags: Files bulk action', () => {
	test.afterAll(async () => await clearTags())

	// eslint-disable-next-line @typescript-eslint/no-unused-vars -- Needed to execute the upload by PlayWright
	test('Can assign tag to selection', async ({ filesListPage, fileIds }) => {
		const tag = crypto.randomUUID()

		await filesListPage.expectInlineTagsForFile('file1.txt', [])
		await filesListPage.expectInlineTagsForFile('file2.txt', [])

		await filesListPage.selectRowForFile('file1.txt')
		await filesListPage.selectRowForFile('file2.txt')

		await filesListPage.openTagPickerForSelection()
		await filesListPage.createNewTagInPicker(tag)
		await filesListPage.applyTagPicker()

		await filesListPage.expectInlineTagsForFile('file1.txt', [tag])
		await filesListPage.expectInlineTagsForFile('file2.txt', [tag])
	})

	test('Can assign multiple tags to selection', async ({ filesListPage }) => {
		const tag1 = crypto.randomUUID()
		const tag2 = crypto.randomUUID()

		await filesListPage.expectInlineTagsForFile('file1.txt', [])
		await filesListPage.expectInlineTagsForFile('file2.txt', [])

		await filesListPage.selectRowForFile('file1.txt')
		await filesListPage.selectRowForFile('file2.txt')

		await filesListPage.openTagPickerForSelection()
		await filesListPage.createNewTagInPicker(tag1)
		await filesListPage.createNewTagInPicker(tag2)
		await filesListPage.applyTagPicker()

		await filesListPage.expectInlineTagsForFile('file1.txt', [tag1, tag2])
		await filesListPage.expectInlineTagsForFile('file2.txt', [tag1, tag2])
	})

	test('Can remove tag from selection', async ({ filesListPage, page, fileIds }) => {
		const tag1 = crypto.randomUUID()
		const tag2 = crypto.randomUUID()
		await assignTagsToFile(fileIds[0], [tag1, tag2])
		await assignTagsToFile(fileIds[1], [tag1, tag2])
		await page.reload()

		await filesListPage.expectInlineTagsForFile('file1.txt', [tag1, tag2])
		await filesListPage.expectInlineTagsForFile('file2.txt', [tag1, tag2])

		await filesListPage.selectRowForFile('file1.txt')
		await filesListPage.selectRowForFile('file2.txt')

		await filesListPage.openTagPickerForSelection()
		await filesListPage.unselectTagInPicker(tag2)
		await filesListPage.applyTagPicker()

		await filesListPage.expectInlineTagsForFile('file1.txt', [tag1])
		await filesListPage.expectInlineTagsForFile('file2.txt', [tag1])
	})

	test('Can remove multiple tags from selection', async ({ filesListPage, page, fileIds }) => {
		const tag1 = crypto.randomUUID()
		const tag2 = crypto.randomUUID()
		const tag3 = crypto.randomUUID()
		await assignTagsToFile(fileIds[0], [tag1, tag2, tag3])
		await assignTagsToFile(fileIds[1], [tag1, tag2, tag3])
		await page.reload()

		await filesListPage.expectInlineTagsForFile('file1.txt', [tag1, tag2, tag3])
		await filesListPage.expectInlineTagsForFile('file2.txt', [tag1, tag2, tag3])

		await filesListPage.selectRowForFile('file1.txt')
		await filesListPage.selectRowForFile('file2.txt')

		await filesListPage.openTagPickerForSelection()
		await filesListPage.unselectTagInPicker(tag2)
		await filesListPage.unselectTagInPicker(tag3)
		await filesListPage.applyTagPicker()

		await filesListPage.expectInlineTagsForFile('file1.txt', [tag1])
		await filesListPage.expectInlineTagsForFile('file2.txt', [tag1])
	})

	test('Can assign and remove multiple tags', async ({ filesListPage, page, fileIds }) => {
		const tag1 = crypto.randomUUID()
		const tag2 = crypto.randomUUID()
		const tag3 = crypto.randomUUID()
		await assignTagsToFile(fileIds[0], [tag1, tag2])
		await assignTagsToFile(fileIds[1], [tag1, tag2])
		await page.reload()

		await filesListPage.expectInlineTagsForFile('file1.txt', [tag1, tag2])
		await filesListPage.expectInlineTagsForFile('file2.txt', [tag1, tag2])

		await filesListPage.selectRowForFile('file1.txt')
		await filesListPage.selectRowForFile('file2.txt')

		await filesListPage.openTagPickerForSelection()
		await filesListPage.unselectTagInPicker(tag2)
		await filesListPage.createNewTagInPicker(tag3)
		await filesListPage.applyTagPicker()

		await filesListPage.expectInlineTagsForFile('file1.txt', [tag1, tag3])
		await filesListPage.expectInlineTagsForFile('file2.txt', [tag1, tag3])
	})

	test('Can search for tags with insensitive case', async ({ filesListPage }) => {
		const tag = crypto.randomUUID().toLowerCase()
		await createTag(tag, 'public')

		await filesListPage.expectInlineTagsForFile('file1.txt', [])
		await filesListPage.expectInlineTagsForFile('file2.txt', [])

		await filesListPage.selectRowForFile('file1.txt')
		await filesListPage.selectRowForFile('file2.txt')

		await filesListPage.openTagPickerForSelection()
		await filesListPage.selectTagInPicker(tag.toUpperCase())
		await filesListPage.applyTagPicker()

		await filesListPage.expectInlineTagsForFile('file1.txt', [tag])
		await filesListPage.expectInlineTagsForFile('file2.txt', [tag])
	})
})
