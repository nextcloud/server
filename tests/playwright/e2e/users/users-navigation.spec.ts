/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { APIRequestContext } from '@playwright/test'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-with-user.ts'
import { SettingsUsersPage } from '../../support/sections/SettingsUsersPage.ts'

const navigationTest = test.extend<{ settingsPage: SettingsUsersPage, testGroup: string }>({
	async settingsPage({ page }, use) {
		await use(new SettingsUsersPage(page))
	},

	async testGroup({ user }, use) {
		// Spaces, & and % need encoding in the URL
		const testGroup = `Team & Co 50% ${crypto.randomUUID().slice(0, 8)}`
		await runOcc(['group:add', testGroup])
		await runOcc(['group:adduser', testGroup, user.userId])
		await use(testGroup)
		await runOcc(['group:delete', testGroup]).catch(() => {})
	},
})

navigationTest.describe('Settings: Account management navigation', () => {
	navigationTest('filters the accounts by the picked group', async ({ page, settingsPage, testGroup, user }) => {
		await settingsPage.open()
		await expect(settingsPage.userRow(user.userId)).toBeVisible()

		await settingsPage.navigateTo('Admins')
		await expect(page).toHaveURL(/settings\/users\/admin$/)
		await expect(settingsPage.userRow('admin')).toBeVisible()
		await expect(settingsPage.userRow(user.userId)).toHaveCount(0)

		await settingsPage.groupListItem(testGroup).getByRole('link').click()
		await expect(settingsPage.userRow(user.userId)).toBeVisible()
		await expect(settingsPage.userRow('admin')).toHaveCount(0)

		await settingsPage.navigateTo('All accounts')
		await expect(page).toHaveURL(/settings\/users$/)
		await expect(settingsPage.userRow('admin')).toBeVisible()
		await expect(settingsPage.userRow(user.userId)).toBeVisible()
	})

	navigationTest('opens a group from its URL', async ({ page, settingsPage, testGroup, user }) => {
		await settingsPage.open()
		await settingsPage.groupListItem(testGroup).getByRole('link').click()
		await expect(settingsPage.userRow('admin')).toHaveCount(0)

		await page.reload()

		await expect(settingsPage.userRow(user.userId)).toBeVisible()
		await expect(settingsPage.userRow('admin')).toHaveCount(0)
		await expect(settingsPage.groupListItem(testGroup).getByRole('link')).toHaveAttribute('aria-current', 'page')
	})

	navigationTest('goes back to the previous view', async ({ page, settingsPage, testGroup, user }) => {
		await settingsPage.open()
		await settingsPage.navigateTo('Admins')
		await expect(settingsPage.userRow(user.userId)).toHaveCount(0)
		await settingsPage.groupListItem(testGroup).getByRole('link').click()
		await expect(settingsPage.userRow('admin')).toHaveCount(0)

		await page.goBack()

		await expect(page).toHaveURL(/settings\/users\/admin$/)
		await expect(settingsPage.userRow('admin')).toBeVisible()
		await expect(settingsPage.userRow(user.userId)).toHaveCount(0)
	})

	navigationTest('shows disabled accounts', async ({ settingsPage, user }) => {
		await runOcc(['user:disable', user.userId])
		await settingsPage.open()

		await settingsPage.navigateTo('Disabled accounts')

		await expect(settingsPage.userRow(user.userId)).toBeVisible()
		await expect(settingsPage.userRow('admin')).toHaveCount(0)
	})
})

test.describe('Settings: Load more groups', () => {
	const prefix = `lazy-${crypto.randomUUID().slice(0, 8)}`
	// One more page than the 25 groups loaded at once
	const groups = Array.from({ length: 30 }, (_, i) => `${prefix}-${String(i).padStart(2, '0')}`)

	test.beforeAll(async ({ playwright, baseURL }) => {
		const request = await playwright.request.newContext({ baseURL })
		for (const group of groups) {
			await addGroup(request, group)
		}
		await request.dispose()
	})

	test.afterAll(async () => {
		for (const group of groups) {
			await runOcc(['group:delete', group]).catch(() => {})
		}
	})

	test('loads the next groups when scrolling to the end of the list', async ({ page }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()
		await page.getByRole('searchbox', { name: 'Search accounts and groups…' }).fill(prefix)
		const items = settingsPage.customGroupsList().getByRole('listitem').filter({ hasText: prefix })
		await expect(items).toHaveCount(25)

		await items.last().scrollIntoViewIfNeeded()

		await expect(items).toHaveCount(30)
		await expect(settingsPage.groupListItem(groups.at(-1)!)).toBeVisible()
	})
})

/**
 * Create a group through the provisioning API, faster than one occ call per group
 *
 * @param request - Request context without a session
 * @param groupId - Id of the group to create
 */
async function addGroup(request: APIRequestContext, groupId: string): Promise<void> {
	const response = await request.post('/ocs/v2.php/cloud/groups', {
		headers: {
			Accept: 'application/json',
			'OCS-APIRequest': 'true',
			Authorization: 'Basic ' + Buffer.from('admin:admin').toString('base64'),
		},
		form: { groupid: groupId },
	})
	expect(response.ok()).toBe(true)
}
