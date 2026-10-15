/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/random-user-session.ts'
import { UnsupportedBrowserPage } from '../../support/sections/UnsupportedBrowserPage.ts'

test.describe('core: Unsupported browser', () => {
	test('lets the user continue and remembers the choice', async ({ page }) => {
		const unsupportedBrowser = new UnsupportedBrowserPage(page)
		await unsupportedBrowser.open()

		await expect(unsupportedBrowser.heading()).toBeVisible()
		await unsupportedBrowser.continueButton().click()
		await expect(page).toHaveURL(/\/apps\/dashboard(\/|$)/)

		await unsupportedBrowser.open()
		await expect(page).toHaveURL(/\/apps\/dashboard(\/|$)/)
	})

	test('continues to the page the user was redirected from', async ({ page }) => {
		const unsupportedBrowser = new UnsupportedBrowserPage(page)
		await unsupportedBrowser.open('/apps/files/')

		await unsupportedBrowser.continueButton().click()
		await expect(page).toHaveURL(/\/apps\/files\//)
	})
})
