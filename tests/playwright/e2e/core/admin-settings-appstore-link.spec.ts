/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server'
import { expect } from '@playwright/test'
import { test as userTest } from '../../support/fixtures/random-user-session.ts'
import { NavigationHeaderPage } from '../../support/sections/NavigationHeaderPage.ts'

/**
 * Set the app config key that decides whether the app store tile is offered.
 *
 * @param shown - Whether the tile should be offered
 */
async function setAppStoreLinkShown(shown: boolean): Promise<void> {
	await runOcc(['config:app:set', 'core', 'appstore_link_shown', '--value', String(shown), '--type', 'boolean'])
}

// The `admin-settings-` prefix puts this in the serial project: it changes
// instance-wide config. Both states are set explicitly because the test server
// runs with `appstoreenabled` disabled, which hides the tile when the key is unset.
userTest.describe('core: app store link visibility', () => {
	userTest.afterAll(async () => {
		await runOcc(['config:app:delete', 'core', 'appstore_link_shown'])
	})

	userTest('offers the "App store" tile only while an admin allows it', async ({ page }) => {
		const navigationHeader = new NavigationHeaderPage(page)
		const appStoreTile = () => navigationHeader.navigationEntries().filter({ hasText: 'App store' })

		await setAppStoreLinkShown(true)
		await page.goto('/')
		await navigationHeader.openMenu()
		await expect(appStoreTile()).toBeVisible()

		await setAppStoreLinkShown(false)
		await page.goto('/')
		await navigationHeader.openMenu()
		await expect(navigationHeader.navigationEntries()).not.toHaveCount(0)
		await expect(appStoreTile()).toHaveCount(0)
	})
})
