/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server'
import { addUser, runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/random-user-session.ts'

test.describe('Calendar: Availability', () => {
	test('User can see the availability section in settings', async ({ page }) => {
		await page.goto('settings/user')

		// The settings sidebar lists an "Availability" navigation link
		await page.getByRole('link', { name: /Availability/i }).first().click()

		await expect(page).toHaveURL(/settings\/user\/availability$/)
		await expect(page.getByRole('heading', { name: /Availability/i, level: 2 })).toBeVisible()
	})

	test('Users can set their availability status', async ({ page }) => {
		await page.goto('settings/user/availability')

		// CalendarAvailability renders listitems without an accessible name; filter by text content
		const fridayItem = page.locator('#availability').getByRole('listitem').filter({ hasText: 'Friday' })
		await expect(fridayItem).toBeVisible()
		await expect(fridayItem).toContainText('No working hours set')

		// Add a time slot for Friday
		await fridayItem.getByRole('button', { name: 'Add slot' }).click()

		// Fill start and end times — labels are visually hidden but accessible
		await fridayItem.getByLabel('Pick a start time for Friday').fill('09:00')
		await fridayItem.getByLabel('Pick a end time for Friday').fill('18:00')

		// Wait for the PROPPATCH save request before clicking
		const saveResponse = page.waitForResponse((r) => r.url().includes('/remote.php/dav/calendars/') && r.url().includes('/inbox') && r.request().method() === 'PROPPATCH')
		await page.locator('#availability').getByRole('button', { name: 'Save' }).click()
		await saveResponse

		await page.reload()

		// After reload Friday should have a slot (no longer shows "No working hours set")
		await expect(page.locator('#availability').getByRole('listitem').filter({ hasText: 'Friday' })).not.toContainText('No working hours set')
	})

	test('Users can set their absence', async ({ page }) => {
		// Create a specific replacement user
		const replacementUser = new User('replacement-user', 'password')
		await runOcc(['user:delete', replacementUser.userId]).catch(() => {})
		await addUser(replacementUser)

		try {
			await page.goto('settings/user/availability')

			await page.getByRole('heading', { name: /absence/i }).scrollIntoViewIfNeeded()

			const absenceSection = page.locator('#absence')

			// Fill date fields (NcDateTimePickerNative with type="date")
			await absenceSection.getByLabel('First day').fill('2024-12-24')
			await absenceSection.getByLabel(/Last day/i).fill('2024-12-28')

			// Fill text fields
			await absenceSection.getByRole('textbox', { name: /Short absence/i }).fill('Vacation')
			await absenceSection.getByRole('textbox', { name: /Long absence/i }).fill('Happy holidays!')

			// Search for the replacement user via NcSelectUsers
			const userSearchInput = absenceSection.getByLabel('Out of office replacement (optional)')
			const searchResponse = page.waitForResponse((r) => r.url().includes('/apps/files_sharing/api/v1/sharees') && r.url().includes('search=replacement'))
			await userSearchInput.click()
			await userSearchInput.fill('replacement')
			await searchResponse

			await page.getByRole('option', { name: 'replacement-user' }).click()

			// Save and wait for the OCS POST
			const saveResponse = page.waitForResponse((r) => r.url().includes('/apps/dav/api/v1/outOfOffice/') && r.request().method() === 'POST')
			await absenceSection.getByRole('button', { name: 'Save' }).click()
			await saveResponse

			await page.reload()

			// Verify all fields are persisted after reload
			await expect(absenceSection.getByLabel('First day')).toHaveValue('2024-12-24')
			await expect(absenceSection.getByLabel(/Last day/i)).toHaveValue('2024-12-28')
			await expect(absenceSection.getByRole('textbox', { name: /Short absence/i })).toHaveValue('Vacation')
			await expect(absenceSection.getByRole('textbox', { name: /Long absence/i })).toHaveValue('Happy holidays!')
			// NcSelectUsers (single-select) shows the selected user in .vs__selected and a "Clear selected" button
			await expect(absenceSection.locator('.vs__selected')).toContainText('replacement-user')
		} finally {
			await runOcc(['user:delete', replacementUser.userId])
		}
	})
})
