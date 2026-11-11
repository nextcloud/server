/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'
import { test as adminTest } from '../../support/fixtures/admin-session.ts'
import { awaitPasswordGuardedRequest } from '../../support/utils/password-confirmation.ts'
import { pickSelectOption } from '../../support/utils/select.ts'

const test = adminTest.extend<{ pageErrors: void }>({
	pageErrors: [async ({ page }, use) => {
		const errors: string[] = []
		page.on('pageerror', (error) => errors.push(error.message))
		await use()
		expect(errors).toEqual([])
	}, { auto: true }],
})

/**
 * @param args - Arguments for occ
 */
async function occOutput(args: string[]): Promise<string> {
	const { stdout } = await runOcc(args)
	return stdout.trim()
}

test.describe('Settings: artificial intelligence', () => {
	// The testing app registers fake providers for every AI API
	test.beforeAll(async () => {
		await runOcc(['app:enable', 'testing'])
	})

	test.afterAll(async () => {
		await runOcc(['app:disable', 'testing'])
	})

	test('guest usage setting is saved', async ({ page }) => {
		await page.goto('settings/admin/ai')
		await expect(page.getByRole('heading', { name: 'Unified task processing' })).toBeVisible()

		try {
			await page.getByRole('switch', { name: 'Allow AI usage for guest users' }).check({ force: true })
			await expect.poll(() => occOutput(['config:app:get', 'core', 'ai.taskprocessing_guests', '--default-value=false'])).toBe('true')
		} finally {
			await runOcc(['config:app:delete', 'core', 'ai.taskprocessing_guests'])
		}
	})

	test('guest usage setting is reset when saving fails', async ({ page }) => {
		await page.route('**/settings/api/admin/ai', (route) => route.fulfill({ status: 500 }))
		await page.goto('settings/admin/ai')

		const guests = page.getByRole('switch', { name: 'Allow AI usage for guest users' })
		await expect(guests).not.toBeChecked()
		// check() would fail once the rollback unchecks the switch again
		await guests.click({ force: true })

		await expect(page.getByText('Could not save the AI settings')).toBeVisible()
		await expect(guests).not.toBeChecked()
	})

	test('legacy image generation provider is saved', async ({ page }) => {
		await page.goto('settings/admin/ai')
		await page.getByRole('button', { name: 'Show legacy provider settings' }).click()
		await expect(page.getByRole('heading', { name: 'Machine translation (Legacy)' })).toBeVisible()
		await expect(page.getByText('Fake translation')).toBeVisible()

		try {
			await page.getByRole('radio', { name: 'Fake Text2Image provider' }).check({ force: true })
			await expect.poll(() => occOutput(['config:app:get', 'core', 'ai.text2image_provider', '--default-value='])).toBe('"testing-fake-text2image-provider"')
		} finally {
			await runOcc(['config:app:delete', 'core', 'ai.text2image_provider'])
		}
	})
})

test.describe('Settings: security', () => {
	const group = 'e2e-2fa-enforced'

	test.beforeAll(async () => {
		await runOcc(['group:add', group])
	})

	test.afterAll(async () => {
		await runOcc(['twofactorauth:enforce', '--off'])
		await runOcc(['group:delete', group])
	})

	test('two-factor enforcement for a group is saved', async ({ page }) => {
		await page.goto('settings/admin/security')
		await page.getByRole('switch', { name: 'Enforce two-factor authentication' }).check({ force: true })

		await pickSelectOption(page, page.getByRole('combobox', { name: 'Enforced groups' }), group, group)

		const saved = page.waitForResponse((r) => r.url().includes('/settings/api/admin/twofactorauth') && r.request().method() === 'PUT')
		await page.getByRole('button', { name: 'Save changes' }).click()
		await awaitPasswordGuardedRequest(page, saved)

		expect(await occOutput(['config:system:get', 'twofactor_enforced'])).toBe('true')
		expect(await occOutput(['config:system:get', 'twofactor_enforced_groups'])).toBe(group)
	})

	test('cancelling the encryption warning keeps encryption off', async ({ page }) => {
		await page.goto('settings/admin/security')
		// the switch only turns on after the dialog is confirmed
		await page.getByRole('switch', { name: 'Enable server-side encryption' }).click({ force: true })

		const dialog = page.getByRole('dialog', { name: 'Confirm enabling encryption' })
		await expect(dialog).toBeVisible()
		await dialog.getByRole('button', { name: 'Cancel encryption' }).click()

		await expect(dialog).toHaveCount(0)
		await expect(page.getByRole('switch', { name: 'Enable server-side encryption' })).not.toBeChecked()
		expect(await occOutput(['config:app:get', 'core', 'encryption_enabled', '--default-value=no'])).not.toBe('yes')
	})
})
