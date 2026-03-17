/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { test } from '../../support/fixtures/admin-theming-page.ts'
import { NavigationHeaderPage } from '../../support/sections/NavigationHeaderPage.ts'

test.describe('Admin theming set default apps', () => {
	// we need serial mode to reset the default app setting after each test
	// and to restore the default app to dashboard at the end of the tests.
	// Otherwise, the tests would influence each other and lead to random failures (race condition when run in parallel).
	test.describe.configure({ mode: 'serial' })

	test.beforeEach(async ({ adminThemingPage, page, context }) => {
		await runOcc(['config:system:set', 'defaultapp', '--value', 'dashboard'])
		await adminThemingPage.reset()
		await page.goto('')
	})

	test.afterAll(async () => {
		await runOcc(['config:system:set', 'defaultapp', '--value', 'dashboard'])
	})

	test('See the current default app is the dashboard', async ({ page }) => {
		const navigationHeader = new NavigationHeaderPage(page)

		await expect(page).toHaveURL(/apps\/dashboard/)
		await navigationHeader.logo().click()
		await expect(page).toHaveURL(/apps\/dashboard/)
	})

	test('Can configure and switch the default app to files', async ({ adminThemingPage }) => {
		await adminThemingPage.open()
		await expect(adminThemingPage.defaultAppSwitch()).toBeVisible()
		if (await adminThemingPage.defaultAppSwitch().isChecked()) {
			await adminThemingPage.defaultAppSwitch().uncheck({ force: true })
		}
		await expect(adminThemingPage.defaultAppSwitch()).not.toBeChecked()

		await adminThemingPage.defaultAppSwitch().check({ force: true })
		await expect(adminThemingPage.defaultAppSwitch()).toBeChecked()
		await expect(adminThemingPage.defaultAppRegion()).toBeVisible()

		await expect(adminThemingPage.defaultAppSelect().getByText('Dashboard')).toBeVisible()
		await expect(adminThemingPage.defaultAppSelect().getByText('Files')).toBeVisible()

		await expect(adminThemingPage.appOrderEntries()).toHaveCount(2)
		await expect(adminThemingPage.appOrderEntries().nth(0)).toContainText('Dashboard')
		await expect(adminThemingPage.appOrderEntries().nth(1)).toContainText('Files')

		await adminThemingPage.moveUpButton('Files').click()
		await expect(adminThemingPage.moveUpButton('Files')).toHaveCount(0)
		await expect(adminThemingPage.appOrderEntries().nth(0)).toContainText('Files')
		await expect(adminThemingPage.appOrderEntries().nth(1)).toContainText('Dashboard')

		await adminThemingPage.defaultAppSwitch().uncheck({ force: true })
		await expect(adminThemingPage.defaultAppSwitch()).not.toBeChecked()
		await expect(adminThemingPage.defaultAppRegion()).toHaveCount(0)
	})
})
