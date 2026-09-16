/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-appstore-page.ts'
import { handlePasswordConfirmation } from '../../support/utils/password-confirmation.ts'

test.describe('Settings: App management', () => {
	test.afterAll(async () => {
		// 'Limit app usage to group' deselects the admin group without unchecking
		// the group-limit checkbox, leaving Dashboard with an empty allow-list and
		// hiding it from non-admin accounts. Re-enabling rewrites the app's
		// `enabled` flag back to `yes`, which restores the `/` redirect to the
		// dashboard for subsequent specs.
		await runOcc(['app:enable', 'dashboard'], { failOnError: false })
	})

	test.beforeEach(async ({ appstorePage }) => {
		// Disable QA testing app if already enabled
		await runOcc(['app:disable', 'testing'], { failOnError: false })
		// Enable update notification app if disabled
		await runOcc(['app:enable', 'updatenotification'], { failOnError: false })

		// Open the installed apps page
		await appstorePage.openInstalledApps()

		// Wait for the apps table to load
		await appstorePage.appsTable().waitFor({ state: 'visible', timeout: 10000 })
	})

	test('Can enable an installed app', async ({ page, appstorePage }) => {
		// Intercept the enable app request
		const enableRequest = page.waitForResponse((response) => response.url().includes('/settings/apps/enable'))

		// Find and click the enable button for the QA testing app
		await expect(appstorePage.appsTable()).toBeVisible()
		const qaTestingRow = appstorePage.appRow('QA testing')
		await expect(qaTestingRow).toBeVisible({ timeout: 10000 })

		await appstorePage.enableButton('QA testing').click({ force: true })

		// Handle password confirmation if needed
		await handlePasswordConfirmation(page, 'admin')

		// Wait for the API request
		await enableRequest

		// Wait until we see the disable button for the app
		await expect(appstorePage.appsTable()).toBeVisible()
		await expect(appstorePage.appRow('QA testing')).toBeVisible()
		await expect(appstorePage.disableButton('QA testing')).toBeVisible()

		// Change to enabled apps view
		await appstorePage.openEnabledApps()

		// Verify the app appears in the enabled list
		await expect(appstorePage.appRow('QA testing')).toBeVisible()
	})

	test('Can disable an installed app', async ({ page, appstorePage }) => {
		// Intercept the disable app request
		const disableRequest = page.waitForResponse((response) => response.url().includes('/settings/apps/disable'))

		// Find and click the disable button for the Update notification app
		await expect(appstorePage.appsTable()).toBeVisible()
		const updateRow = appstorePage.appRow('Update notification')
		await expect(updateRow).toBeVisible({ timeout: 10000 })

		await appstorePage.disableButton('Update notification').click({ force: true })

		// Handle password confirmation if needed
		await handlePasswordConfirmation(page, 'admin')

		// Wait for the API request
		await disableRequest

		// Wait until we see the enable button for the app
		await expect(appstorePage.appsTable()).toBeVisible()
		await expect(appstorePage.appRow('Update notification')).toBeVisible()
		await expect(appstorePage.enableButton('Update notification')).toBeVisible()

		// Change to disabled apps view
		await appstorePage.openDisabledApps()

		// Verify the app appears in the disabled list
		await expect(appstorePage.appRow('Update notification')).toBeVisible()
	})

	test('Browse enabled apps', async ({ appstorePage }) => {
		// Open the "Active apps" section
		await appstorePage.openEnabledApps()

		// Verify that there are only enabled apps (all have "Disable" button, no "Enable" button)
		await expect(appstorePage.appsTable()).toBeVisible()

		// Get all rows and verify each has a disable button and no enable button
		const rows = appstorePage.appsTable().locator('tr')
		const rowCount = await rows.count()

		for (let i = 1; i < rowCount; i++) { // Skip header row
			const row = rows.nth(i)
			const enableButton = row.getByRole('button', { name: 'Enable' })

			// Enabled apps should not have an "Enable" button
			await expect(enableButton).not.toBeVisible()
		}
	})

	test('Browse disabled apps', async ({ appstorePage }) => {
		// Open the "Disabled apps" section
		await appstorePage.openDisabledApps()

		// Verify that there are only disabled apps (all have "Enable" button, no "Disable" button)
		await expect(appstorePage.appsTable()).toBeVisible()

		// Get all rows and verify each has an enable button and no disable button
		const rows = appstorePage.appsTable().locator('tr')
		const rowCount = await rows.count()

		for (let i = 1; i < rowCount; i++) { // Skip header row
			const row = rows.nth(i)
			const disableButton = row.getByRole('button', { name: 'Disable' })

			// Disabled apps should not have a "Disable" button
			await expect(disableButton).not.toBeVisible()
		}
	})

	test('Browse app bundles', async ({ appstorePage }) => {
		// Open the "App bundles" section
		await appstorePage.openBundles()

		// Verify we see the app bundles
		await expect(appstorePage.bundleHeader('Enterprise bundle')).toBeVisible()
		await expect(appstorePage.bundleHeader('Education bundle')).toBeVisible()

		// The "Enterprise bundle" is not installed yet
		await expect(
			appstorePage.bundleHeader('Enterprise bundle').getByRole('button', { name: 'Download and enable all' }),
		).toBeVisible()
	})

	test('View app details', async ({ appstorePage }) => {
		// Click on the "QA testing" app
		await appstorePage.appLink('QA testing').click({ force: true })

		// Verify the app details sidebar is shown
		const sidebar = appstorePage.appSidebar()
		await expect(sidebar).toBeVisible()
		await expect(appstorePage.appSidebarHeader()).toContainText('QA testing')

		// Verify the sidebar contains expected elements
		await expect(appstorePage.viewInStoreLink()).toBeVisible()
		await expect(appstorePage.appSidebarEnableButton()).toBeVisible()
		await expect(appstorePage.removeButton()).toBeVisible()

		// Verify version information is displayed
		await expect(appstorePage.versionText()).toBeVisible()
	})

	test('Limit app usage to group', async ({ appstorePage, page }) => {
		// Open the "Active apps" section
		await appstorePage.openEnabledApps()

		// Select the dashboard app
		await appstorePage.appLink('Dashboard').scrollIntoViewIfNeeded()
		await appstorePage.appLink('Dashboard').click()
		await expect(appstorePage.appSidebar()).toBeVisible()

		// Enable the group limitation
		await appstorePage.limitToGroupsLabel('dashboard').click()
		await expect(appstorePage.limitToGroupsCheckbox('dashboard')).toBeChecked()

		// Select the admin group
		await appstorePage.groupSearchInput().fill('admin')
		await appstorePage.groupOption('admin').click()

		// Handle password confirmation
		await handlePasswordConfirmation(page, 'admin')

		// Verify the group is now selected
		await expect(appstorePage.deselectGroupButton('admin')).toBeVisible()

		// Now remove the group limitation again
		await appstorePage.deselectGroupButton('admin').click()

		// Handle password confirmation
		await handlePasswordConfirmation(page, 'admin')

		await expect(appstorePage.deselectGroupButton('admin')).toHaveCount(0)
	})
})
