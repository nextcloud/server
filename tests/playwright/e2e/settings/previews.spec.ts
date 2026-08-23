/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test as adminTest } from '../../support/fixtures/admin-session.ts'
import { test as userTest } from '../../support/fixtures/random-user-session.ts'
import { AccountMenuPage } from '../../support/sections/AccountMenuPage.ts'

adminTest.describe('Settings: Previews admin page', () => {
	adminTest('admin can open Administration → Previews and see all sections', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await expect(page.getByRole('heading', { name: 'Previews', exact: true }).first()).toBeVisible()
		await expect(page.getByRole('heading', { name: 'General' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Imaginary' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Providers' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'MIME priority' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'HTTP caching' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Failed generations' })).toBeVisible()
	})

	adminTest('enable previews and max width persist after reload', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		const enable = page.locator('[data-cy="previews-enable"] input')
		await expect(enable).toBeVisible()
		if (!(await enable.isChecked())) {
			await enable.click()
		}
		const maxX = page.locator('[data-cy="previews-max-x"] input')
		await maxX.fill('2048')
		await page.locator('[data-cy="previews-save"]').click()
		const password = page.getByLabel(/password/i)
		if (await password.isVisible({ timeout: 3000 }).catch(() => false)) {
			await password.fill('admin')
			await page.getByRole('button', { name: /confirm/i }).click()
		}
		await expect(page.getByText('Preview settings saved')).toBeVisible()
		await page.reload()
		await expect(page.locator('[data-cy="previews-enable"] input')).toBeChecked()
		await expect(page.locator('[data-cy="previews-max-x"] input')).toHaveValue('2048')
	})

	adminTest('providers list includes JPEG or Image related providers', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		const list = page.locator('[data-cy="previews-providers"]')
		await expect(list).toBeVisible()
		await expect(list).toContainText(/JPEG|PNG|Image/i)
	})

	adminTest('authenticated public cache visibility shows a warning', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await page.getByRole('heading', { name: 'HTTP caching' }).scrollIntoViewIfNeeded()
		const visibility = page.getByRole('combobox', { name: 'Visibility' }).first()
		await visibility.click()
		await page.getByRole('option', { name: 'public' }).click()
		await expect(page.getByText(/Setting authenticated previews to public/)).toBeVisible()
	})

	adminTest('failed generations empty state renders', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await expect(page.locator('[data-cy="previews-failures-empty"]')).toBeVisible()
	})
})

userTest.describe('Settings: Previews access – regular user', () => {
	userTest('does not see the Previews admin section', async ({ page }) => {
		await page.goto('/')
		const accountMenu = new AccountMenuPage(page)
		await accountMenu.open()
		await accountMenu.entry('Settings').getByRole('link').click()
		await expect(page).toHaveURL(/\/settings\/user$/)
		const appNavigation = page.locator('#app-navigation-vue')
		await expect(appNavigation.getByRole('list', { name: 'Administration' })).toHaveCount(0)
		await expect(appNavigation.getByRole('link', { name: 'Previews' })).toHaveCount(0)
	})
})
