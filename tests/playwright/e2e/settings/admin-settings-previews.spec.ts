/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

import { expect } from '@playwright/test'
import { test as adminTest } from '../../support/fixtures/admin-session.ts'
import { test as userTest } from '../../support/fixtures/random-user-session.ts'
import { AccountMenuPage } from '../../support/sections/AccountMenuPage.ts'
import { handlePasswordConfirmation } from '../../support/utils/password-confirmation.ts'
import { pickSelectOption } from '../../support/utils/select.ts'

function providerRow(page: Page, className: string) {
	return page.getByRole('row').filter({ has: page.locator('code', { hasText: new RegExp(`^${className.replace(/\\/g, '\\\\')}$`) }) })
}

adminTest.describe('Settings: Previews admin page', () => {
	adminTest('admin can open Administration → Previews and see all sections', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		const previewsNav = page.getByRole('navigation').getByRole('link', { name: 'Previews' })
		await expect(previewsNav).toBeVisible()
		const previewsNavIcon = previewsNav.locator('img')
		await expect(previewsNavIcon).toBeVisible()
		await expect(previewsNavIcon).toHaveAttribute('src', /\/apps\/settings\/img\/previews\.svg/)
		await expect(page.getByRole('heading', { name: /^Previews/ }).first()).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Providers' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'General' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Preview quality' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Performance' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Imaginary' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Movie' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Office' })).toBeVisible()
	})

	adminTest('enable previews and max width persist after reload', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		const enable = page.getByRole('switch', { name: 'Enable previews' })
		await expect(enable).toBeVisible()
		if (!(await enable.isChecked())) {
			await enable.click()
			await handlePasswordConfirmation(page)
		}
		const maxX = page.getByRole('spinbutton', { name: 'Maximum preview width (pixels)' })
		await maxX.fill('2048')
		await maxX.blur()
		await handlePasswordConfirmation(page)
		await page.reload()
		await expect(page.getByRole('switch', { name: 'Enable previews' })).toBeChecked()
		await expect(page.getByRole('spinbutton', { name: 'Maximum preview width (pixels)' })).toHaveValue('2048')
	})

	adminTest('providers list includes JPEG or Image related providers', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		const table = page.getByRole('table', { name: 'Preview providers' })
		await expect(table).toBeVisible()
		await expect(table).toContainText(/JPEG|PNG|Image/i)
	})

	adminTest('providers table shows preview format and footnotes', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await page.getByRole('heading', { name: 'Providers' }).scrollIntoViewIfNeeded()
		const table = page.getByRole('table', { name: 'Preview providers' })
		await expect(table.getByRole('columnheader', { name: 'Preview format' })).toBeVisible()
		const jpegRow = providerRow(page, 'OC\\Preview\\JPEG')
		const webpRow = providerRow(page, 'OC\\Preview\\WebP')
		const pngRow = providerRow(page, 'OC\\Preview\\PNG')
		await expect(jpegRow).toContainText('JPEG')
		await expect(jpegRow).toContainText('Available')
		await expect(webpRow).toContainText('WebP')
		await expect(pngRow).toContainText('PNG')
		const imaginaryRow = providerRow(page, 'OC\\Preview\\Imaginary')
		await expect(imaginaryRow).toContainText(/JPEG|WebP/)
		const mp3Row = providerRow(page, 'OC\\Preview\\MP3')
		await expect(mp3Row).toContainText('Unsupported')
		await expect(page.getByText(/Disabled by default due to security and performance concerns/)).toBeVisible()
		await expect(page.getByText(/MP3 previews use the artwork embedded in the file/)).toBeVisible()

		const format = page.getByRole('combobox', { name: 'Preview output format' })
		await pickSelectOption(page, format, 'WebP')
		await handlePasswordConfirmation(page)
		await expect(imaginaryRow).toContainText('WebP')
		await expect(page.getByText(/When (the )?preview output format is JPEG, Imaginary still writes PNG/i)).toHaveCount(0)
	})

	adminTest('source MIME filter shows providers that handle HEIC', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await page.getByRole('heading', { name: 'Providers' }).scrollIntoViewIfNeeded()
		const mimeFilter = page.getByRole('combobox', { name: 'Source MIME type' })
		await pickSelectOption(page, mimeFilter, 'image/heic')
		const table = page.getByRole('table', { name: 'Preview providers' })
		await expect(table.locator('code', { hasText: /^OC\\Preview\\HEIC$/ })).toBeVisible()
		await expect(table.locator('code', { hasText: /^OC\\Preview\\Imaginary$/ })).toBeVisible()
		await expect(table.locator('code', { hasText: /^OC\\Preview\\JPEG$/ })).toHaveCount(0)
	})

	adminTest('status filter can show unsupported providers', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await page.getByRole('heading', { name: 'Providers' }).scrollIntoViewIfNeeded()
		const statusFilter = page.getByRole('combobox', { name: 'Status' })
		await pickSelectOption(page, statusFilter, 'Unsupported')
		const table = page.getByRole('table', { name: 'Preview providers' })
		await expect(table.locator('code', { hasText: /^OC\\Preview\\MP3$/ })).toBeVisible()
		await expect(table.locator('code', { hasText: /^OC\\Preview\\Movie$/ })).toBeVisible()
		await expect(table.locator('code', { hasText: /^OC\\Preview\\JPEG$/ })).toHaveCount(0)
	})

	adminTest('reset to defaults restores provider order', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await page.getByRole('heading', { name: 'Providers' }).scrollIntoViewIfNeeded()
		const table = page.getByRole('table', { name: 'Preview providers' })
		const pngRow = providerRow(page, 'OC\\Preview\\PNG')
		await pngRow.getByRole('button', { name: 'Move down' }).click()
		await handlePasswordConfirmation(page)
		const moved = await table.locator('tbody code').allTextContents()
		expect(moved.indexOf('OC\\Preview\\PNG')).toBeGreaterThan(moved.indexOf('OC\\Preview\\JPEG'))
		await page.getByRole('button', { name: 'Reset to defaults' }).click()
		await handlePasswordConfirmation(page)
		const reset = await table.locator('tbody code').allTextContents()
		expect(reset.indexOf('OC\\Preview\\PNG')).toBeLessThan(reset.indexOf('OC\\Preview\\JPEG'))
	})

	adminTest('unavailable providers cannot be enabled until their requirement is met', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await page.getByRole('heading', { name: 'Providers' }).scrollIntoViewIfNeeded()
		const imaginaryRow = providerRow(page, 'OC\\Preview\\Imaginary')
		const imaginarySwitch = imaginaryRow.getByRole('switch')
		if (await imaginaryRow.getByText('Requires Imaginary URL').count()) {
			await expect(imaginarySwitch).toBeDisabled()
			await page.getByRole('textbox', { name: 'Imaginary URL' }).fill('http://127.0.0.1:9000')
			await page.getByRole('textbox', { name: 'Imaginary URL' }).blur()
			await handlePasswordConfirmation(page)
			await expect(imaginaryRow.getByText('Requires Imaginary URL')).toHaveCount(0)
			await expect(imaginaryRow).toContainText('Available')
			await expect(imaginarySwitch).toBeEnabled()
		}

		const movieRow = providerRow(page, 'OC\\Preview\\Movie')
		const movieSwitch = movieRow.getByRole('switch')
		if (await movieRow.getByText('Requires ffmpeg').count()) {
			await expect(movieSwitch).toBeDisabled()
			await page.getByRole('textbox', { name: 'ffmpeg path' }).fill('/usr/bin/ffmpeg')
			await page.getByRole('textbox', { name: 'ffmpeg path' }).blur()
			await handlePasswordConfirmation(page)
			await expect(movieRow.getByText('Requires ffmpeg')).toHaveCount(0)
			await expect(movieRow).toContainText(/Available|Unsupported/)
			await expect(movieSwitch).toBeEnabled()
		}
	})

	adminTest('requires availability chips link to the matching section', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await page.getByRole('heading', { name: 'Providers' }).scrollIntoViewIfNeeded()
		const ffmpegLink = page.getByRole('link', { name: 'Go to the Movie section to configure ffmpeg' })
		if (await ffmpegLink.count()) {
			await expect(ffmpegLink.first()).toHaveAttribute('href', '#previews-section-movie')
		}
		const officeLink = page.getByRole('link', { name: 'Go to the Office section to configure LibreOffice or OpenOffice' })
		if (await officeLink.count()) {
			await expect(officeLink.first()).toHaveAttribute('href', '#previews-section-office')
		}
		const imaginaryLink = page.getByRole('link', { name: 'Go to the Imaginary section to set the URL' })
		if (await imaginaryLink.count()) {
			await expect(imaginaryLink.first()).toHaveAttribute('href', '#previews-section-imaginary')
		}
	})

	adminTest('performance concurrency warnings', async ({ page }) => {
		await page.goto('/index.php/settings/admin/previews')
		await page.getByRole('heading', { name: 'Performance' }).scrollIntoViewIfNeeded()
		const cpuDetected = await page.getByText(/This server reports \d+ CPU cores/).isVisible()
		const newConcurrency = page.getByRole('spinbutton', { name: 'New preview concurrency' })
		const totalConcurrency = page.getByRole('spinbutton', { name: 'Total preview concurrency' })
		await newConcurrency.fill('9999')
		await newConcurrency.blur()
		await handlePasswordConfirmation(page)
		await totalConcurrency.fill('1')
		await totalConcurrency.blur()
		await handlePasswordConfirmation(page)
		if (cpuDetected) {
			await expect(page.getByText(/New preview concurrency is higher than the \d+ CPU cores/)).toBeVisible()
			await expect(page.getByText(/Total preview concurrency should be greater than or equal to new preview concurrency/)).toBeVisible()
		} else {
			await expect(page.getByText(/New preview concurrency is higher than the \d+ CPU cores/)).toHaveCount(0)
			await expect(page.getByText(/Total preview concurrency should be greater than or equal to new preview concurrency/)).toHaveCount(0)
		}
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
