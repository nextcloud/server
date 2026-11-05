/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'
import { test as adminTest } from '../../support/fixtures/admin-session.ts'

// Auto fixture: an uncaught page error fails the test
const test = adminTest.extend<{ pageErrors: void }>({
	pageErrors: [async ({ page }, use) => {
		const errors: string[] = []
		page.on('pageerror', (error) => errors.push(error.message))
		await use()
		expect(errors).toEqual([])
	}, { auto: true }],
})

/**
 * @param app - App id
 * @param key - Config key
 */
async function getAppConfig(app: string, key: string): Promise<string> {
	const { stdout } = await runOcc(['config:app:get', app, key])
	return stdout.trim()
}

test.describe('Settings: admin sections', () => {
	test('overview shows the setup checks', async ({ page }) => {
		await page.goto('settings/admin/overview')
		await expect(page.getByRole('heading', { name: 'Security & setup warnings' })).toBeVisible()
	})

	test('server page shows the email server and profile sections', async ({ page }) => {
		await page.goto('settings/admin/server')
		await expect(page.getByRole('heading', { name: 'Email server' })).toBeVisible()
		await expect(page.getByText('Send mode')).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Profile', exact: true })).toBeVisible()
	})

	test('background jobs mode is saved', async ({ page }) => {
		const initialMode = await getAppConfig('core', 'backgroundjobs_mode')
		await page.goto('settings/admin/server')

		try {
			const mode = initialMode === 'webcron' ? 'AJAX' : 'Webcron'
			await page.getByRole('radio', { name: mode }).check({ force: true })
			await expect.poll(() => getAppConfig('core', 'backgroundjobs_mode')).toBe(mode.toLowerCase())
		} finally {
			await runOcc(['config:app:set', 'core', 'backgroundjobs_mode', '--value', initialMode || 'ajax'])
		}
	})

	test('background jobs mode is reset when saving fails', async ({ page }) => {
		await page.route('**/config/apps/core/backgroundjobs_mode', (route) => route.fulfill({ status: 500 }))
		await page.goto('settings/admin/server')

		// the page has other radio groups, the input name scopes to this one
		const checked = page.locator('input[name="backgroundJobsMode"]:checked')
		const initial = await checked.getAttribute('value')
		const other = page.getByRole('radio', { name: initial === 'webcron' ? 'AJAX' : 'Webcron' })
		await other.check({ force: true })

		await expect(page.getByText('Unable to update background job mode')).toBeVisible()
		await expect(other).not.toBeChecked()
		await expect(checked).toHaveAttribute('value', initial!)
	})

	test('profile setting is reset when saving fails', async ({ page }) => {
		await page.route('**/config/apps/settings/profile_picker_enabled', (route) => route.fulfill({ status: 500 }))
		await page.goto('settings/admin/server')

		const picker = page.getByRole('switch', { name: 'Enable the profile picker' })
		await expect(picker).toBeChecked()
		try {
			await picker.uncheck({ force: true })

			await expect(page.getByText('Unable to update profile picker setting')).toBeVisible()
			await expect(picker).toBeChecked()
		} finally {
			await runOcc(['config:app:delete', 'settings', 'profile_picker_enabled'])
		}
	})

	test('delegation page renders', async ({ page }) => {
		await page.goto('settings/admin/admindelegation')
		await expect(page.getByRole('heading', { level: 2, name: /^Administration privileges/ })).toBeVisible()
	})

	test('presets page renders', async ({ page }) => {
		await page.goto('settings/admin/presets')
		await expect(page.getByRole('heading', { level: 2, name: 'Quick presets' })).toBeVisible()
	})

	test('sharing setting is saved', async ({ page }) => {
		await page.goto('settings/admin/sharing')

		const resharing = page.getByRole('checkbox', { name: 'Allow resharing' })
		await expect(resharing).toBeChecked()
		try {
			await resharing.uncheck({ force: true })
			await expect.poll(() => getAppConfig('core', 'shareapi_allow_resharing')).toBe('no')
		} finally {
			await runOcc(['config:app:delete', 'core', 'shareapi_allow_resharing'])
		}
	})
})
