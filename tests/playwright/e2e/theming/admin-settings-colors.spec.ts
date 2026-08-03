/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-theming-page.ts'
import { pickColor } from '../../support/utils/theming.ts'

test.beforeEach(async ({ adminThemingPage }) => {
	await adminThemingPage.reset()
	await adminThemingPage.open()
})

test('Change the primary color and reset it', async ({ adminThemingPage, page }) => {
	await page.getByRole('heading', { name: 'Background and color' }).scrollIntoViewIfNeeded()

	const primaryColorButton = page.getByRole('button', { name: /Primary color/ })
	const updateStylesheetResponse = page.waitForResponse((response) => {
		return response.url().includes('/apps/theming/ajax/updateStylesheet')
			&& response.request().method() === 'POST'
	})
	await pickColor(page, primaryColorButton, 3)
	expect(await updateStylesheetResponse).toBeTruthy()

	await page.goto('settings/admin/theming')
	await adminThemingPage.reset()
	await page.goto('settings/admin/theming')
	await expect(page.getByRole('heading', { name: 'Background and color' })).toBeVisible()
})
