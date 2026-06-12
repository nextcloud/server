/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '@playwright/test'
import { User } from '@nextcloud/e2e-test-server'
import { createRandomUser, login } from '@nextcloud/e2e-test-server/playwright'
import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { LoginPage } from '../../support/sections/LoginPage.ts'

test.describe('Login: Redirect', () => {
	let user: User

	test.beforeAll(async () => {
		user = await createRandomUser()
	})

	test.afterAll(async () => {
		await runOcc(['user:delete', user.userId])
	})

	test('redirects to login with redirect_url when session expires', async ({ page, context }) => {
		await login(context.request, user)
		await page.goto('/settings/user#profile')

		// Wait for the profile settings checkbox to confirm the page has loaded
		await expect(page.getByRole('checkbox', { name: /Enable profile/i })).toBeVisible()

		// Simulate session expiry by clearing all cookies
		await context.clearCookies()

		// Clicking the checkbox triggers an authenticated request that returns 302 to login
		await page.getByRole('checkbox', { name: /Enable profile/i }).click({ force: true })

		await expect(page).toHaveURL(/\/login/i)
		await expect(page).toHaveURL(/redirect_url=/)
	})

	test('redirect_url parameter redirects to the original page after login', async ({ page }) => {
		const redirectTarget = 'settings/user#profile'
		await page.goto(redirectTarget)
		await expect(page).toHaveURL(new RegExp(`/login\\?redirect_url=(\/index.php\/)?${redirectTarget}`))

		const loginPage = new LoginPage(page)
		await expect(loginPage.usernameInput()).toBeVisible()
		await loginPage.login(user.userId, user.password)

		await expect(page).toHaveURL(/\/settings\/user/)
		await expect(page.getByRole('checkbox', { name: /Enable profile/i })).toBeVisible()
	})
})
