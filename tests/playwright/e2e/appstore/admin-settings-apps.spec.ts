/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-appstore-page.ts'
import { handlePasswordConfirmation } from '../../support/utils/password-confirmation.ts'

test.describe('Settings: App management', () => {
	test.beforeEach(async ({ appstorePage }) => {
		// Disable QA testing app if already enabled
		expect(await runOcc(['app:disable', 'testing']))
			.toMatch(/(No such app enabled|testing .+ disabled)/)
		// Enable update notification app if disabled
		expect(await runOcc(['app:enable', 'updatenotification']))
			.toMatch(/(updatenotification already enabled|updatenotification .+ enabled)/)

		// Open the installed apps page
		await appstorePage.openInstalledApps()

		// Wait for the apps table to load
		await appstorePage.appsTable().waitFor({ state: 'visible', timeout: 10000 })
	})

	test('Can enable an installed app', async ({ page, appstorePage }) => {
		// Intercept the enable app request
		const enableRequest = page.waitForResponse((response) => response.url().includes('/ocs/v2.php/apps/appstore/api/v1/apps/enable'))

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
		const disableRequest = page.waitForResponse((response) => response.url().includes('/ocs/v2.php/apps/appstore/api/v1/apps/disable'))

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

		// Verify the URL is correct
		await expect(appstorePage.navigationLink('Active apps')).toHaveAttribute('aria-current', 'page')

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

		// Verify the current section is "Disabled apps"
		await expect(appstorePage.navigationLink('Disabled apps')).toHaveAttribute('aria-current', 'page')

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

		// Verify the current section is "App bundles"
		await expect(appstorePage.navigationLink('App bundles')).toHaveAttribute('aria-current', 'page')

		// Verify we see the app bundles
		await expect(appstorePage.enterpriseBundleHeading()).toBeVisible()
		await expect(appstorePage.educationBundleHeading()).toBeVisible()
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

		// Select the updatenotification app
		await appstorePage.appLink('Update Notification').scrollIntoViewIfNeeded()
		await appstorePage.appLink('Update Notification').click()

		// Click the "Limit to groups" button
		await appstorePage.limitToGroupsButton().click()

		// The dialog should be visible
		const dialog = appstorePage.groupDialog()
		await expect(dialog).toBeVisible()

		// Type "admin" in the search field
		const searchInput = appstorePage.groupSearchInput()
		await searchInput.fill('admin')

		// Select the admin option from the dropdown
		await appstorePage.groupOption('admin').click()

		// Click the Save button
		await appstorePage.dialogSaveButton().click()

		// Handle password confirmation
		await handlePasswordConfirmation(page, 'admin')

		// Verify the group is now in the "Limited to groups" list
		const limitedList = appstorePage.limitedToGroupsList()
		await expect(limitedList).toBeVisible()
		await expect(limitedList.getByRole('listitem', { name: /admin/ })).toBeVisible()

		// Now disable the group limitation
		await appstorePage.limitToGroupsButton().click()

		// The dialog should be visible again
		await expect(dialog).toBeVisible()

		// Click the deselect button for the admin group
		await appstorePage.deselectGroupButton('admin').click()

		// Click Save
		await appstorePage.dialogSaveButton().click()

		// Handle password confirmation
		await handlePasswordConfirmation(page, 'admin')

		// Verify the "Limited to groups" list is no longer visible
		await expect(appstorePage.limitedToGroupsList()).toHaveCount(0)
	})
})
