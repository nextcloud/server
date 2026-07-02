/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { BrowserContext } from '@playwright/test'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser, login } from '@nextcloud/e2e-test-server/playwright'
import { expect, test } from '@playwright/test'
import { handlePasswordConfirmation } from '../../support/utils/password-confirmation.ts'

test.describe('Login: WebAuthn', () => {
	test.skip(({ browserName }) => browserName !== 'chromium', 'WebAuthn emulator only is supported in Chromium-based browsers')

	let user: User
	let cdpSession: Awaited<ReturnType<BrowserContext['newCDPSession']>>
	let authenticatorId: string

	test.beforeEach(async ({ page, context }) => {
		user = await createRandomUser()
		await login(context.request, user)

		cdpSession = await page.context().newCDPSession(page)
		await cdpSession.send('WebAuthn.enable', { enableUI: false })
		const result = await cdpSession.send('WebAuthn.addVirtualAuthenticator', {
			options: {
				protocol: 'ctap2',
				ctap2Version: 'ctap2_1',
				hasUserVerification: true,
				transport: 'usb',
				automaticPresenceSimulation: true,
				isUserVerified: true,
			},
		})
		authenticatorId = result.authenticatorId
	})

	test.afterEach(async () => {
		await cdpSession.send('WebAuthn.removeVirtualAuthenticator', { authenticatorId })
		await runOcc(['user:delete', user.userId])
	})

	test('add and delete a WebAuthn device', async ({ page }) => {
		const registrationChallenge = page.waitForResponse((r) => r.url().includes('/settings/api/personal/webauthn/registration'))
		await page.goto('/settings/user/security')

		const securitySection = page.locator('#security-webauthn')
		await expect(securitySection.getByRole('note').filter({ hasText: /No devices configured/i })).toBeVisible()

		await page.getByRole('button', { name: /Add WebAuthn device/i }).click()
		await handlePasswordConfirmation(page, user.password)
		await registrationChallenge

		const deviceNameInput = page.getByLabel('Device name')
		await expect(deviceNameInput).toBeVisible()

		const registrationComplete = page.waitForResponse((r) => r.url().includes('/settings/api/personal/webauthn/registration'))
		await deviceNameInput.fill('test device')
		await deviceNameInput.press('Enter')
		await registrationComplete

		const deviceList = page.getByRole('list', { name: /following devices/i })
		await expect(deviceList).toBeVisible()
		const deviceItem = deviceList.getByRole('listitem').filter({ hasText: 'test device' })
		await expect(deviceItem).toBeVisible()

		await deviceItem.getByRole('button', { name: 'Actions' }).click()
		await handlePasswordConfirmation(page, user.password)
		await page.getByRole('menuitem', { name: 'Delete' }).click()
		await handlePasswordConfirmation(page, user.password)

		await expect(securitySection.getByRole('note').filter({ hasText: /No devices configured/i })).toBeVisible()
		await expect(deviceList).toHaveCount(0)

		await page.reload()
		await expect(securitySection.getByRole('note').filter({ hasText: /No devices configured/i })).toBeVisible()
	})

	test('add a WebAuthn device and use it to log in', async ({ page, context }) => {
		const registrationChallenge = page.waitForResponse((r) => r.url().includes('/settings/api/personal/webauthn/registration') && r.request().method() === 'GET')
		await page.goto('/settings/user/security')

		await page.getByRole('button', { name: /Add WebAuthn device/i }).click()
		await handlePasswordConfirmation(page, user.password)
		await registrationChallenge

		const registrationComplete = page.waitForResponse((r) => r.url().includes('/settings/api/personal/webauthn/registration') && r.request().method() === 'POST')
		const deviceNameInput = page.getByLabel('Device name')
		await deviceNameInput.fill('test device')
		await deviceNameInput.press('Enter')
		await registrationComplete

		const deviceList = page.getByRole('list', { name: /following devices/i })
		await expect(deviceList.getByRole('listitem').filter({ hasText: 'test device' })).toBeVisible()

		// Log out and return to the login page
		await context.clearCookies()
		await page.goto('/login')

		// Switch to passwordless login form
		await page.getByRole('button', { name: /Log in with a device/i }).click()

		const passwordlessForm = page.getByRole('form', { name: /Log in with a device/i })
		await expect(passwordlessForm).toBeVisible()

		await passwordlessForm.getByLabel('Login or email').fill(user.userId)

		const webauthnLogin = page.waitForResponse((r) => r.url().includes('/login/webauthn/start') && r.request().method() === 'POST')
		await page.getByRole('button', { name: 'Log in' }).click()
		await webauthnLogin

		await expect(page).toHaveURL(/apps\/dashboard(\/|$)/)
	})
})
