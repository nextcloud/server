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
		await expect(page.getByRole('heading', { name: 'Providers' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'General' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Preview quality' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Performance' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Imaginary' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Movie' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Office' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Failed generations' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Retention' })).toBeVisible()
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

	adminTest('providers table shows preview format and footnotes', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await page.getByRole('heading', { name: 'Providers' }).scrollIntoViewIfNeeded()
		await expect(page.locator('[data-cy="previews-providers"] .previews-admin__provider--header')).toContainText('Preview format')
		const jpegRow = page.locator('[data-cy="previews-providers"] .previews-admin__provider').filter({ hasText: 'OC\\Preview\\JPEG' })
		const webpRow = page.locator('[data-cy="previews-providers"] .previews-admin__provider').filter({ hasText: 'OC\\Preview\\WebP' })
		const pngRow = page.locator('[data-cy="previews-providers"] .previews-admin__provider').filter({ hasText: 'OC\\Preview\\PNG' })
		await expect(jpegRow.locator('[data-cy="previews-provider-format"]')).toHaveText('JPEG')
		await expect(jpegRow.locator('[data-cy="previews-provider-availability"]')).toContainText('Available')
		await expect(webpRow.locator('[data-cy="previews-provider-format"]')).toHaveText('WebP')
		await expect(pngRow.locator('[data-cy="previews-provider-format"]')).toHaveText('PNG')
		const imaginaryRow = page.locator('[data-cy="previews-providers"] .previews-admin__provider').filter({ has: page.locator('code', { hasText: /^OC\\Preview\\Imaginary$/ }) })
		await expect(imaginaryRow.locator('[data-cy="previews-provider-format"]')).toHaveText('JPEG2')
		await expect(imaginaryRow.locator('[data-cy="previews-provider-format"] sup')).toHaveText('2')
		const mp3Row = page.locator('[data-cy="previews-providers"] .previews-admin__provider').filter({ hasText: 'OC\\Preview\\MP3' })
		await expect(mp3Row.locator('[data-cy="previews-provider-availability"]')).toContainText('Unsupported')
		await expect(page.getByText(/Disabled by default due to security and performance concerns/)).toBeVisible()
		await expect(page.getByText(/When Preview output format is JPEG, Imaginary still writes PNG/)).toBeVisible()
		await expect(page.getByText(/MP3 previews use the artwork embedded in the file/)).toBeVisible()

		const format = page.getByRole('combobox', { name: 'Preview output format' })
		await format.click()
		await page.getByRole('option', { name: 'WebP' }).click()
		await expect(imaginaryRow.locator('[data-cy="previews-provider-format"]')).toHaveText('WebP')
		await expect(page.getByText(/When Preview output format is JPEG, Imaginary still writes PNG/)).toHaveCount(0)
	})

	adminTest('source MIME filter shows providers that handle HEIC', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await page.getByRole('heading', { name: 'Providers' }).scrollIntoViewIfNeeded()
		const mimeFilter = page.getByRole('combobox', { name: 'Source MIME type' })
		await mimeFilter.click()
		await page.getByRole('option', { name: 'image/heic' }).click()
		const list = page.locator('[data-cy="previews-providers"]')
		await expect(list.locator('code', { hasText: /^OC\\Preview\\HEIC$/ })).toBeVisible()
		await expect(list.locator('code', { hasText: /^OC\\Preview\\Imaginary$/ })).toBeVisible()
		await expect(list.locator('code', { hasText: /^OC\\Preview\\JPEG$/ })).toHaveCount(0)
	})

	adminTest('status filter can show unsupported providers', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await page.getByRole('heading', { name: 'Providers' }).scrollIntoViewIfNeeded()
		const statusFilter = page.getByRole('combobox', { name: 'Status' })
		await statusFilter.click()
		await page.getByRole('option', { name: 'Unsupported', exact: true }).click()
		const list = page.locator('[data-cy="previews-providers"]')
		await expect(list.locator('code', { hasText: /^OC\\Preview\\MP3$/ })).toBeVisible()
		await expect(list.locator('code', { hasText: /^OC\\Preview\\Movie$/ })).toBeVisible()
		await expect(list.locator('code', { hasText: /^OC\\Preview\\JPEG$/ })).toHaveCount(0)
	})

	adminTest('reset to defaults restores provider order', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await page.getByRole('heading', { name: 'Providers' }).scrollIntoViewIfNeeded()
		const rows = page.locator('[data-cy="previews-providers"] .previews-admin__provider:not(.previews-admin__provider--header)')
		const pngRow = rows.filter({ has: page.locator('code', { hasText: /^OC\\Preview\\PNG$/ }) })
		await pngRow.getByRole('button', { name: 'Move down' }).click()
		const moved = await rows.locator('code').allTextContents()
		expect(moved.indexOf('OC\\Preview\\PNG')).toBeGreaterThan(moved.indexOf('OC\\Preview\\JPEG'))
		await page.locator('[data-cy="previews-reset-providers"]').click()
		const reset = await rows.locator('code').allTextContents()
		expect(reset.indexOf('OC\\Preview\\PNG')).toBeLessThan(reset.indexOf('OC\\Preview\\JPEG'))
	})

	adminTest('requires availability chips link to the matching section', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await page.getByRole('heading', { name: 'Providers' }).scrollIntoViewIfNeeded()
		const ffmpegChip = page.locator('[data-cy="previews-provider-availability"]').filter({ hasText: 'Requires ffmpeg' })
		if (await ffmpegChip.count()) {
			await expect(ffmpegChip.locator('a')).toHaveAttribute('href', '#previews-section-movie')
		}
		const officeChip = page.locator('[data-cy="previews-provider-availability"]').filter({ hasText: 'Requires LibreOffice' })
		if (await officeChip.count()) {
			await expect(officeChip.locator('a')).toHaveAttribute('href', '#previews-section-office')
		}
		const imaginaryChip = page.locator('[data-cy="previews-provider-availability"]').filter({ hasText: 'Requires Imaginary URL' })
		if (await imaginaryChip.count()) {
			await expect(imaginaryChip.locator('a')).toHaveAttribute('href', '#previews-section-imaginary')
		}
	})

	adminTest('performance concurrency warnings', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await page.getByRole('heading', { name: 'Performance' }).scrollIntoViewIfNeeded()
		const cpuDetected = await page.getByText(/This server reports \d+ CPU cores/).isVisible()
		await page.locator('[data-cy="previews-concurrency-new"] input').fill('9999')
		await page.locator('[data-cy="previews-concurrency-all"] input').fill('1')
		if (cpuDetected) {
			await expect(page.locator('[data-cy="previews-concurrency-new-warning"]')).toBeVisible()
			await expect(page.locator('[data-cy="previews-concurrency-all-warning"]')).toBeVisible()
		} else {
			await expect(page.locator('[data-cy="previews-concurrency-new-warning"]')).toHaveCount(0)
			await expect(page.locator('[data-cy="previews-concurrency-all-warning"]')).toHaveCount(0)
		}
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
