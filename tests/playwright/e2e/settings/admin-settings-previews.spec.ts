/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-previews-page.ts'
import { awaitPasswordGuardedRequest } from '../../support/utils/password-confirmation.ts'

const KEYS = ['enable_previews', 'enabledPreviewProviders', 'preview_max_x']

/**
 * Run an action and wait until the change it triggers is stored
 *
 * @param page - The Playwright page object
 * @param endpoint - The last path segment of the API, `settings` or `providers`
 * @param action - The user action triggering the request
 */
async function saving(page: Page, endpoint: 'settings' | 'providers', action: () => Promise<void>): Promise<void> {
	const response = page.waitForResponse((r) => r.url().includes(`/api/admin/previews/${endpoint}`) && r.request().method() !== 'GET')
	await action()
	expect((await awaitPasswordGuardedRequest(page, response)).ok()).toBe(true)
}

test.describe('Admin previews settings', () => {
	test.beforeEach(async ({ adminPreviewsPage }) => {
		for (const key of KEYS) {
			await runOcc(['config:system:delete', key], { failOnError: false })
		}
		await adminPreviewsPage.open()
	})

	test.afterAll(async () => {
		for (const key of KEYS) {
			await runOcc(['config:system:delete', key], { failOnError: false })
		}
	})

	test('enabling a provider keeps it after a reload', async ({ adminPreviewsPage, page }) => {
		await expect(adminPreviewsPage.providerSwitch('MP3')).not.toBeChecked()

		await saving(page, 'providers', () => adminPreviewsPage.providerSwitch('MP3').check({ force: true }))
		await expect(adminPreviewsPage.providerSwitch('MP3')).toBeChecked()

		await page.reload()
		await expect(adminPreviewsPage.providerSwitch('MP3')).toBeChecked()
	})

	test('moving a provider changes its try-order', async ({ adminPreviewsPage, page }) => {
		await saving(page, 'providers', () => adminPreviewsPage.providerSwitch('MP3').check({ force: true }))
		const before = await adminPreviewsPage.providerIndex('MP3')

		await saving(page, 'providers', () => adminPreviewsPage.moveEarlierButton('MP3').click())
		await expect.poll(() => adminPreviewsPage.providerIndex('MP3')).toBe(before - 1)

		await page.reload()
		await expect.poll(() => adminPreviewsPage.providerIndex('MP3')).toBe(before - 1)
	})

	test('resetting goes back to the default providers', async ({ adminPreviewsPage, page }) => {
		await expect(adminPreviewsPage.resetProvidersButton()).toBeDisabled()
		await saving(page, 'providers', () => adminPreviewsPage.providerSwitch('MP3').check({ force: true }))
		await expect(adminPreviewsPage.resetProvidersButton()).toBeEnabled()

		await saving(page, 'providers', () => adminPreviewsPage.resetProvidersButton().click())

		await expect(adminPreviewsPage.providerSwitch('MP3')).not.toBeChecked()
		await expect(adminPreviewsPage.resetProvidersButton()).toBeDisabled()
	})

	test('a limit is saved when leaving the field', async ({ adminPreviewsPage, page }) => {
		await expect(adminPreviewsPage.maxWidthField()).toHaveValue('')

		await adminPreviewsPage.maxWidthField().fill('2048')
		await saving(page, 'settings', () => adminPreviewsPage.maxWidthField().blur())

		await page.reload()
		await expect(adminPreviewsPage.maxWidthField()).toHaveValue('2048')
	})

	test('disabling previews hides the other settings', async ({ adminPreviewsPage, page }) => {
		await expect(adminPreviewsPage.providersTable()).toBeVisible()

		await saving(page, 'settings', () => adminPreviewsPage.enablePreviewsSwitch().uncheck({ force: true }))
		await expect(adminPreviewsPage.providersTable()).toBeHidden()

		await page.reload()
		await expect(adminPreviewsPage.enablePreviewsSwitch()).not.toBeChecked()
		await expect(adminPreviewsPage.providersTable()).toBeHidden()
	})
})
