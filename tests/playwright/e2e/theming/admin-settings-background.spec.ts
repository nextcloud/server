/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createRandomUser, login } from '@nextcloud/e2e-test-server/playwright'
import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-theming-page.ts'
import { resolve } from 'node:path'
import { defaultBackground, defaultPrimary, getBodyThemingSnapshot, pickColor } from '../../support/utils/theming.ts'

test.describe('Admin theming background settings', () => {
	test.describe.configure({ mode: 'serial' })

	test.beforeEach(async ({ adminThemingPage, page }) => {
		await adminThemingPage.reset()
		await adminThemingPage.open()
		if (await adminThemingPage.disableUserThemingCheckbox().isChecked()) {
			await Promise.all([
				page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/updateStylesheet') && response.request().method() === 'POST'),
				adminThemingPage.disableUserThemingCheckbox().uncheck({ force: true }),
			])
		}
	})

	test('Remove default background and restore it', async ({ adminThemingPage, page, context }) => {
		await expect(adminThemingPage.backgroundAndColorHeading()).toBeVisible()
		if (await adminThemingPage.removeBackgroundImageCheckbox().isChecked()) {
			await Promise.all([
				page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/updateStylesheet') && response.request().method() === 'POST'),
				adminThemingPage.removeBackgroundImageCheckbox().uncheck({ force: true }),
			])
		}

		await Promise.all([
			page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/updateStylesheet') && response.request().method() === 'POST'),
			adminThemingPage.removeBackgroundImageCheckbox().check({ force: true }),
		])

		await page.goto('/index.php/logout')
		await page.goto('/index.php/login')
		await expect.poll(async () => (await getBodyThemingSnapshot(page)).backgroundImage).toBe('none')

		await adminThemingPage.reset()
		await page.goto('settings/admin/theming')
		await expect(adminThemingPage.backgroundAndColorHeading()).toBeVisible()
	})

	test('Disable user theming', async ({ adminThemingPage, page, context }) => {
		await expect(adminThemingPage.disableUserThemingCheckbox()).not.toBeChecked()
		await Promise.all([
			page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/updateStylesheet') && response.request().method() === 'POST'),
			adminThemingPage.disableUserThemingCheckbox().check({ force: true }),
		])

		const user = await createRandomUser()
		try {
			await login(context.request, user)
			await page.goto('settings/user/theming')
			await expect(page.getByText('Customization has been disabled by your administrator')).toBeVisible()
		} finally {
			await runOcc(['user:delete', user.userId])
		}
	})

	test('Remove default background with custom color', async ({ adminThemingPage, page, context }) => {
		await expect(adminThemingPage.backgroundAndColorHeading()).toBeVisible()
		const backgroundColorButton = page.getByRole('button', { name: /Background color/ })
		const selectedColor = await pickColor(page, backgroundColorButton, 2)
		expect(selectedColor).toBeTruthy()

		await Promise.all([
			page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/updateStylesheet') && response.request().method() === 'POST'),
			adminThemingPage.removeBackgroundImageCheckbox().check({ force: true }),
		])

		await page.goto('/index.php/logout')
		await page.goto('/index.php/login')
		await expect.poll(async () => (await getBodyThemingSnapshot(page)).backgroundImage).toBe('none')
	})

	test('User default background reflects admin custom background and color', async ({ adminThemingPage, page, context }) => {
		const imagePath = resolve(process.cwd(), 'cypress/fixtures/image.jpg')

		await page.locator('input[type="file"][name="background"]').setInputFiles(imagePath)
		await page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/uploadImage') && response.request().method() === 'POST')

		const backgroundColorButton = page.getByRole('button', { name: /Background color/ })
		await pickColor(page, backgroundColorButton, 1)
		await page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/updateStylesheet') && response.request().method() === 'POST')

		await page.goto('/index.php/logout')
		const user = await createRandomUser()
		try {
			await login(context.request, user)
			await page.goto('settings/user/theming')
			await expect(page.getByRole('button', { name: 'Default background' })).toHaveAttribute('aria-pressed', 'true')
			const snapshot = await getBodyThemingSnapshot(page)
			expect(snapshot.backgroundImage).toContain('/apps/theming/image/background?v=')
		} finally {
			await runOcc(['user:delete', user.userId])
		}
	})

	test('User default background reflects admin removed background', async ({ adminThemingPage, page, context }) => {
		await Promise.all([
			page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/updateStylesheet') && response.request().method() === 'POST'),
			adminThemingPage.removeBackgroundImageCheckbox().check({ force: true }),
		])

		await page.goto('/index.php/logout')
		const user = await createRandomUser()
		try {
			await login(context.request, user)
			await page.goto('settings/user/theming')
			await expect(page.getByRole('button', { name: 'Default background' })).toHaveAttribute('aria-pressed', 'true')
			await expect.poll(async () => (await getBodyThemingSnapshot(page)).backgroundImage).toBe('none')
		} finally {
			await runOcc(['user:delete', user.userId])
		}
	})
})
