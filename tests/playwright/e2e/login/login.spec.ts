/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser, login } from '@nextcloud/e2e-test-server/playwright'
import { test as baseTest, expect } from '@playwright/test'
import { AccountMenuPage } from '../../support/sections/AccountMenuPage.ts'
import { LoginPage } from '../../support/sections/LoginPage.ts'

const test = baseTest.extend<{
	user: User
	disabledUser: User
}>({
	user: async ({}, use) => {
		const user = await createRandomUser()
		await use(user)
		await runOcc(['user:delete', user.userId])
	},
	disabledUser: async ({}, use) => {
		const user = await createRandomUser()
		await runOcc(['user:disable', user.userId])
		await use(user)
		await runOcc(['user:delete', user.userId])
	},
})

test.describe('Login', () => {
	test.beforeAll(async () => {
		await runOcc(['config:system:set', 'auth.bruteforce.protection.enabled', '--value', 'false', '--type', 'bool'])
	})

	test.afterAll(async () => {
		await runOcc(['config:system:delete', 'auth.bruteforce.protection.enabled'])
	})

	test('successful login lands on the dashboard', async ({ page, user }) => {
		const loginPage = new LoginPage(page)
		await loginPage.goto()
		await loginPage.login(user.userId, user.password)

		await expect(page).toHaveURL(/apps\/dashboard(\/|$)/)
	})

	test('wrong password shows error and marks password field invalid', async ({ page, user }) => {
		const loginPage = new LoginPage(page)
		await loginPage.goto()
		await loginPage.login(user.userId, `${user.password}--wrong`)

		await expect(page).toHaveURL(/\/login/)
		await expect(page.getByText(/Wrong login or password/i)).toBeVisible()
		await expect(loginPage.passwordInput().and(page.locator(':invalid'))).toHaveCount(1)
	})

	test('wrong account name shows error and marks password field invalid', async ({ page, user }) => {
		const loginPage = new LoginPage(page)
		await loginPage.goto()
		await loginPage.login(`${user.userId}--wrong`, user.password)

		await expect(page).toHaveURL(/\/login/)
		await expect(page.getByText(/Wrong login or password/i)).toBeVisible()
		await expect(loginPage.passwordInput().and(page.locator(':invalid'))).toHaveCount(1)
	})

	test('disabled account shows disabled error', async ({ page, disabledUser }) => {
		const loginPage = new LoginPage(page)
		await loginPage.goto()
		await loginPage.login(disabledUser.userId, disabledUser.password)

		await expect(page).toHaveURL(/\/login/)
		await expect(page.getByText(/Account.*disabled/i)).toBeVisible()
		await expect(loginPage.passwordInput().and(page.locator(':invalid'))).toHaveCount(1)
	})

	test('logout redirects to the login page', async ({ page, context, user }) => {
		await login(context.request, user)
		await page.goto('/')

		const accountMenu = new AccountMenuPage(page)
		await accountMenu.open()
		await accountMenu.entry('Log out').getByRole('link').click()

		await expect(page).toHaveURL(/\/login($|\?)/)
	})
})
