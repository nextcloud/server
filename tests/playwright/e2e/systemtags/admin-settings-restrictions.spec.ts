/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/systemtags-files-page.ts'
import { uploadContent } from '../../support/utils/dav.ts'
import { clearTags, createTag } from '../../support/utils/systemtags.ts'

test.beforeAll(async () => await runOcc(['config:app:set', 'systemtags', 'restrict_creation_to_admin', '--value', '1']))
test.afterAll(async () => await runOcc(['config:app:delete', 'systemtags', 'restrict_creation_to_admin']))
test.afterAll(async () => await clearTags())

test.beforeEach(async ({ filesListPage, page, user }) => {
	await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/file1.txt')
	await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/file2.txt')
	await filesListPage.open()
})

test('Cannot create tag if restriction is in place', async ({ filesListPage }) => {
	const tag = crypto.randomUUID()
	await createTag(tag, 'public')

	await filesListPage.expectInlineTagsForFile('file1.txt', [])
	await filesListPage.selectAll()
	const picker = await filesListPage.openTagPickerForSelection()

	// When restricted, the input label changes and create/color buttons are absent
	await expect(picker.getByLabel('Search or create tag')).toHaveCount(0)
	await expect(picker.getByLabel('Search tag')).toBeVisible()

	await picker.getByLabel('Search tag').fill(crypto.randomUUID())
	await expect(picker.getByRole('button', { name: /Create new tag/i })).toHaveCount(0)

	await picker.getByLabel('Search tag').clear()
	await picker.getByLabel('Search tag').fill(tag)

	await expect(picker.getByRole('checkbox')).toHaveCount(1)
	await expect(picker.getByRole('button', { name: /Create new tag/i })).toHaveCount(0)
	await expect(picker.getByRole('button', { name: 'Change tag color' })).toHaveCount(0)

	// Can still assign the existing admin-created tag
	await picker.getByRole('checkbox', { name: tag }).click({ force: true })
	await filesListPage.applyTagPicker()
	await filesListPage.expectInlineTagsForFile('file1.txt', [tag])
})
