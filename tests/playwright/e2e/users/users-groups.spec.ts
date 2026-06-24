/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser } from '@nextcloud/e2e-test-server/playwright'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-session.ts'
import { SettingsUsersPage } from '../../support/sections/SettingsUsersPage.ts'
import { handlePasswordConfirmation } from '../../support/utils/password-confirmation.ts'

// ── Create group ──────────────────────────────────────────────────────────────

test('Account Management: Can create a group', async ({ page }) => {
	const groupName = crypto.randomUUID()
	const settingsPage = new SettingsUsersPage(page)
	await settingsPage.open()

	try {
		const createGroupsResponsePromise = page.waitForResponse(/ocs\/v2\.php\/cloud\/groups($|\?)/)

		await page.getByRole('button', { name: 'Create group' }).click()
		await page.getByLabel('Group name').fill(groupName)
		await page.getByLabel('Group name').press('Enter')

		await handlePasswordConfirmation(page)
		await createGroupsResponsePromise

		await expect(settingsPage.customGroupsList()).toContainText(groupName)
	} finally {
		await runOcc(['group:delete', groupName]).catch(() => {})
	}
})

// ── Assign user to group ──────────────────────────────────────────────────────

const userGroupTest = test.extend<{ testUser: User, testGroup: string }>({
	async testUser({}, use) {
		const testUser = await createRandomUser()
		await use(testUser)
		await runOcc(['user:delete', testUser.userId])
	},
	async testGroup({}, use) {
		const testGroup = crypto.randomUUID()
		await runOcc(['group:add', testGroup])
		await use(testGroup)
		await runOcc(['group:delete', testGroup])
	},
})

userGroupTest('Account Management: Assign user to a group', async ({ page, testGroup, testUser }) => {
	const settingsPage = new SettingsUsersPage(page)
	await settingsPage.open()

	// group is in the list with no members
	await expect(settingsPage.groupListItem(testGroup)).toBeVisible()
	// Counter bubble is absent when member count is 0
	await expect(settingsPage.groupListItem(testGroup).locator('.counter-bubble__counter')).toHaveCount(0)
	// user is in the list
	await expect(settingsPage.userRow(testUser.userId)).toBeVisible()

	// can assign the group via the edit dialog
	await settingsPage.openEditDialog(testUser.userId)
	const dialog = settingsPage.editUserDialog()
	const groupsCombobox = dialog.getByRole('combobox', { name: /Member of the following groups/i })
	const searchRequest = page.waitForResponse((r) => r.request().url().match(new RegExp('/ocs/v2\\.php/cloud/groups/details\\?(.+&|)search=' + testGroup.slice(0, 5))) !== null)
	await groupsCombobox.fill(testGroup.slice(0, 5))
	await searchRequest

	await page.getByRole('option', { name: new RegExp(testGroup.slice(0, 8)) }).click()

	await handlePasswordConfirmation(page)
	await settingsPage.saveEditDialog()
	await expect(page.getByText(/Account updated/i)).toBeVisible()

	// user is now group now shows 1 member
	await expect(settingsPage.groupListItem(testGroup).locator('.counter-bubble__counter')).toHaveText('1')
	// backend confirms the user is in the group
	const info = JSON.parse(await runOcc(['user:info', '--output=json', testUser.userId]))
	expect(info?.groups).toContain(testGroup)
})

// ── Delete an empty group ─────────────────────────────────────────────────────

test.describe('Settings: Delete an empty group', () => {
	const groupName = crypto.randomUUID()

	test.beforeAll(async () => {
		await runOcc(['group:add', groupName])
	})

	test.afterAll(async () => {
		await runOcc(['group:delete', groupName]).catch(() => {})
	})

	test('can delete an empty group', async ({ page }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		const groupItem = settingsPage.groupListItem(groupName)
		await expect(groupItem).toBeVisible()

		// Open the group's actions menu
		await groupItem.hover()
		await expect(groupItem.getByRole('button', { name: /Actions/i })).toBeVisible()
		await groupItem.getByRole('button', { name: /Actions/i }).click()

		// and delete the group
		await page.getByRole('button', { name: 'Delete group' }).click()
		await page.getByRole('dialog').getByRole('button', { name: 'Confirm' }).click()
		await handlePasswordConfirmation(page)

		// Group must be gone from the UI
		await expect(settingsPage.groupListItem(groupName)).toHaveCount(0)

		// Verify backend
		const groups: Record<string, unknown> = JSON.parse(await runOcc(['group:list', '--output=json']))
		expect(Object.keys(groups)).not.toContain(groupName)
	})
})

// ── Delete a non-empty group ──────────────────────────────────────────────────

test.describe('Settings: Delete a non-empty group', () => {
	const groupName = crypto.randomUUID()
	let testUser: User

	test.beforeAll(async () => {
		testUser = await createRandomUser()
		await runOcc(['group:add', groupName])
		await runOcc(['group:adduser', groupName, testUser.userId])
	})

	test.afterAll(async () => {
		await runOcc(['user:delete', testUser.userId])
		await runOcc(['group:delete', groupName]).catch(() => {})
	})

	test('can delete a non-empty group', async ({ page }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		const groupItem = settingsPage.groupListItem(groupName)
		await expect(groupItem).toBeVisible()

		// Open the group's actions menu
		await groupItem.hover()
		expect(groupItem.getByRole('button', { name: /Actions/i })).toBeVisible()
		await groupItem.getByRole('button', { name: /Actions/i }).click()

		// and delete the group
		await page.getByRole('button', { name: 'Delete group' }).click()
		await page.getByRole('dialog').getByRole('button', { name: 'Confirm' }).click()
		await handlePasswordConfirmation(page)

		await expect(settingsPage.groupListItem(groupName)).toHaveCount(0)

		const groups: Record<string, unknown> = JSON.parse(await runOcc(['group:list', '--output=json']))
		expect(Object.keys(groups)).not.toContain(groupName)
	})
})

// ── Sort groups ───────────────────────────────────────────────────────────────
const sortGroupsTest = test.extend<{ testUser: User, testGroups: [string, string] }>({
	async testGroups({ testUser }, use) {
		const suffix = crypto.randomUUID().slice(0, 8)
		const groupA = `A-${suffix}`
		const groupB = `B-${suffix}`

		await runOcc(['group:add', groupA])
		await runOcc(['group:add', groupB])
		await runOcc(['group:adduser', groupB, testUser.userId])
		await use([groupA, groupB])
		await runOcc(['group:delete', groupA]).catch(() => {})
		await runOcc(['group:delete', groupB]).catch(() => {})
	},
	testUser: async ({}, use) => {
		const testUser = await createRandomUser()
		await use(testUser)
		await runOcc(['user:delete', testUser.userId])
	},
})

sortGroupsTest('Settings: Sort groups by member count and then by name', async ({ page, testGroups }) => {
	const settingsPage = new SettingsUsersPage(page)
	await settingsPage.open()

	// ── sort by member count ──
	await settingsPage.openSettingsDialog()
	await settingsPage.settingsDialog()
		.getByRole('radio', { name: 'By member count' })
		.check({ force: true })
	await settingsPage.closeSettingsDialog()

	// B (1 member) must come before A (0 members)
	await checkGroupOrder([testGroups[1], testGroups[0]], settingsPage)

	// Reload to confirm persistence
	await page.reload()
	await checkGroupOrder([testGroups[1], testGroups[0]], settingsPage)

	// ── sort by name ──
	await settingsPage.openSettingsDialog()
	await settingsPage.settingsDialog().getByRole('radio', { name: 'By name' }).check({ force: true })
	await settingsPage.closeSettingsDialog()

	// A comes before B alphabetically
	await checkGroupOrder([testGroups[0], testGroups[1]], settingsPage)

	// Reload to confirm persistence
	await page.reload()
	await checkGroupOrder([testGroups[0], testGroups[1]], settingsPage)
})

/**
 * Check that the groups are in the expected order in the UI.
 *
 * @param order - The expected group order
 * @param settingsPage - The settings page
 */
async function checkGroupOrder(order: string[], settingsPage: SettingsUsersPage) {
	// B (1 member) must come before A (0 members)
	const listItems = settingsPage.customGroupsList().getByRole('listitem')
	for (const group of order) {
		await expect(listItems.filter({ hasText: group })).toHaveCount(1)
	}

	const contents = (await listItems.allTextContents())
		.map((text) => text.trim().replaceAll(/\s+.*/g, '')) // trim and remove member count
		.filter((text) => order.includes(text)) // filter out other groups that might be in the list
	expect(contents).toEqual(order)
}
