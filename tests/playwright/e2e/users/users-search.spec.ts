/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { type User } from '@nextcloud/e2e-test-server'
import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser } from '@nextcloud/e2e-test-server/playwright'
import { expect } from '@playwright/test'
import { test as adminTest } from '../../support/fixtures/admin-session.ts'
import { SettingsUsersPage } from '../../support/sections/SettingsUsersPage.ts'

adminTest.describe.configure({ mode: 'serial' })

adminTest.describe('Settings: Unified search for accounts and groups', () => {
	// Stable, searchable prefix so we can match the group independently of the random suffix
	const matchingGroup = `zzz-match-${crypto.randomUUID().slice(0, 5)}`
	const otherGroup = `aaa-other-${crypto.randomUUID().slice(0, 5)}`
	let alice: User
	let bob: User

	adminTest.beforeAll(async () => {
		alice = await createRandomUser()
		bob = await createRandomUser()
		await runOcc(['group:add', matchingGroup])
		await runOcc(['group:add', otherGroup])
	})

	adminTest.afterAll(async () => {
		await runOcc(['user:delete', alice.userId])
		await runOcc(['user:delete', bob.userId])
		await runOcc(['group:delete', matchingGroup])
		await runOcc(['group:delete', otherGroup])
	})

	adminTest('shows the search input in the navigation sidebar', async ({ page }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		const searchbox = settingsPage.navigation().getByRole('searchbox', { name: /search accounts and groups/i })
		await expect(searchbox).toBeVisible()
		await expect(searchbox).toHaveValue('')
	})

	adminTest('dispatches the query to both the users and groups API', async ({ page }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		const searchbox = settingsPage.navigation().getByRole('searchbox', { name: /search accounts and groups/i })

		const usersRespPromise = page.waitForResponse(/ocs\/v2\.php\/cloud\/users\/details/)
		const groupsRespPromise = page.waitForResponse(/ocs\/v2\.php\/cloud\/groups\/details/)
		await searchbox.fill(alice.userId)
		const usersResp = await usersRespPromise
		const groupsResp = await groupsRespPromise

		expect(new URL(usersResp.url()).searchParams.get('search')).toBe(alice.userId)
		expect(new URL(groupsResp.url()).searchParams.get('search')).toBe(alice.userId)

		// User list reflects the filtered result
		await expect(settingsPage.userRow(alice.userId)).toBeVisible()
		await expect(settingsPage.userList()).not.toContainText(bob.userId)
	})

	adminTest('filters the group list when the query matches a group name', async ({ page }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		const searchbox = settingsPage.navigation().getByRole('searchbox', { name: /search accounts and groups/i })

		const groupsRespPromise = page.waitForResponse(/ocs\/v2\.php\/cloud\/groups\/details/)
		await searchbox.fill(matchingGroup)
		const groupsResp = await groupsRespPromise

		expect(new URL(groupsResp.url()).searchParams.get('search')).toBe(matchingGroup)

		await expect(settingsPage.customGroupsList()).toContainText(matchingGroup)
		await expect(settingsPage.customGroupsList()).not.toContainText(otherGroup)
	})

	adminTest('resets both lists when the clear button is clicked', async ({ page }) => {
		const settingsPage = new SettingsUsersPage(page)
		await settingsPage.open()

		const searchbox = settingsPage.navigation().getByRole('searchbox', { name: /search accounts and groups/i })

		// Prime the search box with a term first
		const primeUsersPromise = page.waitForResponse(/ocs\/v2\.php\/cloud\/users\/details/)
		const primeGroupsPromise = page.waitForResponse(/ocs\/v2\.php\/cloud\/groups\/details/)
		await searchbox.fill(alice.userId)
		await primeUsersPromise
		await primeGroupsPromise

		// Now clear
		const usersRespPromise = page.waitForResponse(/ocs\/v2\.php\/cloud\/users\/details/)
		const groupsRespPromise = page.waitForResponse(/ocs\/v2\.php\/cloud\/groups\/details/)
		await settingsPage.navigation().getByRole('button', { name: /clear search/i }).click()
		await usersRespPromise
		await groupsRespPromise

		await expect(searchbox).toHaveValue('')
		// Both users and both groups must be visible again
		await expect(settingsPage.userRow(alice.userId)).toBeVisible()
		await expect(settingsPage.userRow(bob.userId)).toBeVisible()
		await expect(settingsPage.customGroupsList()).toContainText(matchingGroup)
		await expect(settingsPage.customGroupsList()).toContainText(otherGroup)
	})
})
