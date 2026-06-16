/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { test } from '../../support/fixtures/admin-session.ts'
import { createTag, deleteTag, listTags } from '../../support/utils/systemtags.ts'

const tagName = 'foo'
const updatedTagName = 'bar'

test.describe('System tags admin settings', () => {
	test.beforeEach(async () => {
		const tags = await listTags()
		for (const tag of tags) {
			await deleteTag(tag.id)
		}
	})

	test('Can create a tag', async ({ page }) => {
		await page.goto('settings/admin/server')

		// Scroll the collaborative tags section into view — the admin settings page is long
		await page.getByRole('heading', { name: 'Collaborative tags' }).scrollIntoViewIfNeeded()

		const tagNameInput = page.getByLabel('Tag name')
		await expect(tagNameInput).toHaveValue('')

		// Create the tag and intercept the DAV POST
		const createResponse = page.waitForResponse(
			(r) => r.url().includes('/remote.php/dav/systemtags') && r.request().method() === 'POST',
		)
		await tagNameInput.fill(tagName)
		await page.getByRole('button', { name: 'Create' }).click()
		expect((await createResponse).status()).toBe(201)

		// The form resets after creation — verify the tag now appears in the selection dropdown
		await page.getByRole('combobox', { name: 'Search for a tag to edit' }).click()
		await expect(page.getByRole('option', { name: tagName })).toBeVisible()
	})

	test('Can update a tag', async ({ page }) => {
		const tag = await createTag(tagName)

		await page.goto('settings/admin/server')
		await page.getByRole('heading', { name: 'Collaborative tags' }).scrollIntoViewIfNeeded()

		// Select the tag to edit
		await page.getByRole('combobox', { name: 'Search for a tag to edit' }).click()
		await page.getByRole('option', { name: tagName }).click()

		// Verify the form reflects the selected tag
		await expect(page.getByLabel('Tag name')).toHaveValue(tagName)
		// NcSelect single-select: selected level appears inline in .vs__selected
		await expect(page.locator('.system-tag-form__group:has(#system-tag-level) .vs__selected')).toContainText('Public')

		// Update the name
		await page.getByLabel('Tag name').fill(updatedTagName)

		// Change the level — click opens the teleported VueSelect dropdown
		await page.locator('#system-tag-level').click()
		await page.getByRole('option', { name: 'Invisible' }).click()

		const updateResponse = page.waitForResponse(
			(r) => r.url().includes('/remote.php/dav/systemtags/') && r.request().method() === 'PROPPATCH',
		)
		await page.getByRole('button', { name: 'Update' }).click()
		expect((await updateResponse).status()).toBe(207)

		await page.getByRole('combobox', { name: 'Search for a tag to edit' }).click()
		// NcEllipsisedOption splits names ≥ 10 chars across two spans, breaking the accessible name.
		// "bar (invisible)" (15 chars) splits at position 8 → accessible name "bar (inv isible)".
		// Use filter({ hasText }) to match on text content instead of the exact accessible name.
		await expect(page.getByRole('option').filter({ hasText: updatedTagName })).toBeVisible()
	})

	test('Can delete a tag', async ({ page }) => {
		await createTag(tagName)

		await page.goto('settings/admin/server')
		await page.getByRole('heading', { name: 'Collaborative tags' }).scrollIntoViewIfNeeded()

		// Select the invisible tag to delete
		await page.getByRole('combobox', { name: 'Search for a tag to edit' }).click()
		await page.getByRole('option').filter({ hasText: tagName }).click()

		// Verify the form reflects the selected tag
		await expect(page.getByLabel('Tag name')).toHaveValue(tagName)

		const deleteResponse = page.waitForResponse(
			(r) => r.url().includes('/remote.php/dav/systemtags/') && r.request().method() === 'DELETE',
		)
		await page.locator('.system-tag-form__row').getByRole('button', { name: 'Delete' }).click()
		expect((await deleteResponse).status()).toBe(204)

		// Verify the tag is gone from the dropdown
		await page.getByRole('combobox', { name: 'Search for a tag to edit' }).click()
		await expect(page.getByRole('option').filter({ hasText: tagName })).not.toBeVisible()
	})
})
