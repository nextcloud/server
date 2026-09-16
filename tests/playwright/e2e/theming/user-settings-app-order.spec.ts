/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/random-user-session.ts'
import { NavigationHeaderPage } from '../../support/sections/NavigationHeaderPage.ts'
import { UserThemingPage } from '../../support/sections/UserThemingPage.ts'

test('User can change personal app order', async ({ page }) => {
	const userThemingPage = new UserThemingPage(page)
	const navigationHeader = new NavigationHeaderPage(page)

	await userThemingPage.open()

	await expect(userThemingPage.appOrderEntries()).toHaveCount(2)
	await expect(userThemingPage.appOrderEntries().nth(0)).toContainText('Dashboard')
	await expect(userThemingPage.appOrderEntries().nth(1)).toContainText('Files')

	await expect(navigationHeader.navigationEntries().nth(0)).toContainText('Dashboard')
	await expect(navigationHeader.navigationEntries().nth(1)).toContainText('Files')

	const initialFirstEntry = await userThemingPage.appOrderEntries().nth(0).innerText()
	if (/Dashboard/i.test(initialFirstEntry)) {
		const moveUpButton = userThemingPage.appEntry('Files').locator('button[aria-label="Move up"]').first()
		if (await moveUpButton.count() > 0) {
			await moveUpButton.evaluate((element) => {
				(element as HTMLButtonElement).click()
			})
		}
	}

	const currentOrder = (await userThemingPage.appOrderEntries().allInnerTexts()).map((entry) => entry.trim())
	expect(currentOrder).toContain('Dashboard')
	expect(currentOrder).toContain('Files')

	await page.reload()
	const reloadedOrder = (await userThemingPage.appOrderEntries().allInnerTexts()).map((entry) => entry.trim())
	expect(reloadedOrder).toContain('Dashboard')
	expect(reloadedOrder).toContain('Files')
	await expect(navigationHeader.navigationEntries().nth(0)).toContainText(reloadedOrder[0]!)
	await expect(navigationHeader.navigationEntries().nth(1)).toContainText(reloadedOrder[1]!)
})
