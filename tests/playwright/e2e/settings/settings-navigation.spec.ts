/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

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
 * @param page - The settings page
 * @param name - Name of the admin section
 */
function adminSection(page: Page, name: string): Locator {
	return page.getByRole('list', { name: 'Administration' }).getByRole('link', { name })
}

test.describe('Settings: navigation', () => {
	test('marks the current section', async ({ page }) => {
		await page.goto('settings/admin/server')

		await expect(adminSection(page, 'Basic settings')).toHaveAttribute('aria-current', 'page')
		await expect(adminSection(page, 'Sharing')).not.toHaveAttribute('aria-current')
	})

	test('shows the section content next to the navigation', async ({ page }) => {
		await page.goto('settings/admin/server')

		await expect(page.getByRole('main').getByRole('heading', { name: 'Background jobs' })).toBeInViewport()
		await expect(page.getByRole('heading', { level: 1 })).toHaveText('Administration settings: Basic settings')
	})

	test('opens another section', async ({ page }) => {
		await page.goto('settings/admin/server')

		await adminSection(page, 'Sharing').click()

		await expect(page).toHaveURL(/\/settings\/admin\/sharing$/)
		await expect(adminSection(page, 'Sharing')).toHaveAttribute('aria-current', 'page')
	})
})
