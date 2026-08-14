/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server'
import { test } from '../../support/fixtures/files-sharing-page.ts'
import { expect } from '../../support/matchers.ts'
import { uploadContent } from '../../support/utils/dav.ts'
import {
	ALL_PERMISSIONS,
	createShare,
	SharePermission,
	ShareType,
} from '../../support/utils/sharing.ts'

const verbose = false

test.describe.configure({ mode: 'serial' })

test.describe('files_sharing/otp', () => {
	test('otp not visible without enabled OTP providers', async ({ page, user }) => {
		await runOcc(['app:disable', 'otp_provider_email'], { verbose })
		const fileId = await uploadContent(page.request, user, 'testdata', 'text/plain', '/file.txt')

		await page.goto(`/index.php/apps/files/files/${fileId}?opendetails=true`)
		await page.locator('li:has-text("Create public link") button:has(.icon-add)').click()
		await expect(page.locator('.toastify:has-text("Link share created")')).toBeVisible()

		await page.locator('li:has-text("Share link") button:has-text("View only")').click()
		await expect(page.locator('button:has-text("Custom permissions")')).toBeVisible()
		await page.locator('button:has-text("Custom permissions")').click()
		await expect(page.locator('fieldset:has-text("No password"):has-text("Password")')).toBeVisible()
		await expect(page.locator('fieldset:has-text("OTP")')).not.toBeVisible()
	})

	// happy path
	test('create and retrieve OTP protected share', async ({ page, user }) => {
		const filename = 'file.txt'
		await runOcc(['app:enable', 'otp_provider_email'], { verbose })
		const fileId = await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', `/${filename}`)

		await page.goto(`/index.php/apps/files/files/${fileId}?opendetails=true`)
		await page.locator('li:has-text("Create public link") button:has(.icon-add)').click()
		await expect(page.locator('.toastify:has-text("Link share created")')).toBeVisible()

		await page.locator('li:has-text("Share link") button:has-text("View only")').click()
		await expect(page.locator('button:has-text("Custom permissions")')).toBeVisible()
		await page.locator('button:has-text("Custom permissions")').click()
		await expect(page.locator('fieldset:has-text("No password"):has-text("Password")')).toBeVisible()
		const otpButton = page.locator('fieldset:has-text("Authentication") *:text("OTP")')
		await expect(otpButton).toBeVisible()

		await otpButton.click()

		const otpProviderSelect = page.locator('fieldset:has-text("One-Time Password") div.v-select:has-text("method") input[type=search]')
		await expect(otpProviderSelect).toBeVisible()
		await otpProviderSelect.click()
		const otpProviderOption = page.locator('ul[aria-label=Options] li:has-text("Email")')
		await expect(otpProviderOption).toBeVisible()
		await otpProviderOption.click()

		const otpRecipientInput = page.locator('fieldset:has-text("One-Time Password") div:has(label:has-text("Recipient")) input[type=text]')
		await otpRecipientInput.fill('foo@bar.xyz')

		await (page.locator('button:has-text("Update share")')).click()

		await expect(page.locator('.toastify:has-text("Share saved")')).toBeVisible({ timeout: 10_000 })

		const shareUrl = await page.locator('a[aria-label=\'Copy public link of \\"Share link\\"\']').getAttribute('href')
		expect(shareUrl).toMatch(/http.*\/s\/.*/)

		await page.goto(shareUrl!)
		const otpRequestButton = page.locator('button:has-text("Request One-Time Password")')
		await expect(otpRequestButton).toBeVisible()
	})

	test('missing otp provider shows error on retrieval', async ({ page, owner, ownerRequest }) => {
		const filename = 'file.txt'
		await runOcc(['app:enable', 'otp_provider_email'], { verbose })
		await uploadContent(ownerRequest, owner, Buffer.alloc(0), 'text/plain', `/${filename}`)
		const ocs = await createShare(ownerRequest, `/${filename}`, undefined, ALL_PERMISSIONS & ~SharePermission.CREATE & ~SharePermission.DELETE, ShareType.LINK, undefined, 'email', 'foo@bar.xyz')
		const shareUrl = ocs.data.url as string
		await runOcc(['app:disable', 'otp_provider_email'], { verbose })
		expect(shareUrl).toMatch(/http.*\/s\/.*/)

		await page.goto(shareUrl!)
		await page.waitForTimeout(5_000)
		await expect(page.locator('body'))
			.toContainText('This share requires a one-time password, but the configured one-time password provider \'email\' could not be found')
	})
})
