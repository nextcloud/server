/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { Page } from '@playwright/test'
import type { FilesListPage } from '../../support/sections/FilesListPage.ts'
import type { SharingTab } from '../../support/sections/SharingTab.ts'

import { runOcc } from '@nextcloud/e2e-test-server'
import { expect, test } from '../../support/fixtures/sharing-page.ts'
import { mkdir } from '../../support/utils/dav.ts'
import { createShare, openSharingPanel } from '../../support/utils/sharing.ts'

/** The fixtures a test hands to {@link shareAndOpenEditor}. */
interface SharingContext {
	page: Page
	user: User
	recipient: User
	filesListPage: FilesListPage
	sharingTab: SharingTab
}

/** The instance-wide default: internal shares expire two days after creation. */
const EXPIRE_AFTER_DAYS = 2

/** `YYYY-MM-DD` of today plus `days`, the format the date input holds. */
function dateInDays(days: number): string {
	const date = new Date()
	date.setDate(date.getDate() + days)
	return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

test.describe('files_sharing: Expiry date of internal shares', () => {
	test.beforeAll(async () => {
		await runOcc(['config:app:set', '--value', 'yes', 'core', 'shareapi_default_internal_expire_date'])
		await runOcc(['config:app:set', '--value', String(EXPIRE_AFTER_DAYS), 'core', 'shareapi_internal_expire_after_n_days'])
	})

	test.afterAll(async () => {
		await runOcc(['config:app:delete', 'core', 'shareapi_default_internal_expire_date'])
		await runOcc(['config:app:delete', 'core', 'shareapi_internal_expire_after_n_days'])
		await runOcc(['config:app:delete', 'core', 'shareapi_enforce_internal_expire_date'])
	})

	test.beforeEach(async () => {
		await runOcc(['config:app:delete', 'core', 'shareapi_enforce_internal_expire_date'])
	})

	/**
	 * Share `folder` with the recipient and open the share editor's advanced
	 * section, where the expiration date lives.
	 *
	 * @param folder - The folder to create and share
	 * @param context - The fixtures to drive
	 */
	async function shareAndOpenEditor(
		folder: string,
		{ page, user, recipient, filesListPage, sharingTab }: SharingContext,
	): Promise<void> {
		await mkdir(page.request, user, `/${folder}`)
		await createShare(page.request, `/${folder}`, recipient.userId)
		await filesListPage.open()
		await openSharingPanel(filesListPage, sharingTab, folder)
		await sharingTab.openShareDetails()
		await sharingTab.openAdvancedSettings()
	}

	test('applies the default expiry date and enforces it', async ({ page, user, recipient, filesListPage, sharingTab }) => {
		await runOcc(['config:app:set', '--value', 'yes', 'core', 'shareapi_enforce_internal_expire_date'])
		await shareAndOpenEditor('default-expiry-enforced', { page, user, recipient, filesListPage, sharingTab })

		await expect(sharingTab.expirationDateInput()).toHaveValue(dateInDays(EXPIRE_AFTER_DAYS))
		// Enforced means the recipient's share cannot outlive the policy
		await expect(sharingTab.checkbox(/expiration date/i)).toBeChecked()
		await expect(sharingTab.checkbox(/expiration date/i)).toBeDisabled()
	})

	test('applies the default expiry date without enforcing it', async ({ page, user, recipient, filesListPage, sharingTab }) => {
		await shareAndOpenEditor('default-expiry', { page, user, recipient, filesListPage, sharingTab })

		await expect(sharingTab.expirationDateInput()).toHaveValue(dateInDays(EXPIRE_AFTER_DAYS))
		await expect(sharingTab.checkbox(/expiration date/i)).toBeChecked()
		await expect(sharingTab.checkbox(/expiration date/i)).toBeEnabled()
	})

	test('can be set to a custom date', async ({ page, user, recipient, filesListPage, sharingTab }) => {
		await shareAndOpenEditor('custom-expiry', { page, user, recipient, filesListPage, sharingTab })

		await sharingTab.expirationDateInput().fill(dateInDays(14))
		await sharingTab.save()

		await sharingTab.openShareDetails()
		await sharingTab.openAdvancedSettings()
		await expect(sharingTab.expirationDateInput()).toHaveValue(dateInDays(14))
	})

	test('keeps a custom date across a reload', async ({ page, user, recipient, filesListPage, sharingTab }) => {
		await shareAndOpenEditor('custom-expiry-reload', { page, user, recipient, filesListPage, sharingTab })

		await sharingTab.expirationDateInput().fill(dateInDays(14))
		await sharingTab.save()

		await page.reload()
		await openSharingPanel(filesListPage, sharingTab, 'custom-expiry-reload')
		await sharingTab.openShareDetails()
		await sharingTab.openAdvancedSettings()

		await expect(sharingTab.expirationDateInput()).toHaveValue(dateInDays(14))
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/pull/50192: an
	 * unrelated update must not reset the expiry date to the admin default.
	 */
	test('keeps a custom date when an unrelated field is updated', async ({ page, user, recipient, filesListPage, sharingTab }) => {
		await shareAndOpenEditor('custom-expiry-unrelated', { page, user, recipient, filesListPage, sharingTab })

		await sharingTab.expirationDateInput().fill(dateInDays(14))
		await sharingTab.save()

		// Change only the note …
		await sharingTab.openShareDetails()
		await sharingTab.openAdvancedSettings()
		await sharingTab.setCheckbox('Note to recipient', true)
		await sharingTab.noteInput().fill('Only the note changed')
		await sharingTab.save()

		// … and the date is still the custom one
		await sharingTab.openShareDetails()
		await sharingTab.openAdvancedSettings()
		await expect(sharingTab.expirationDateInput()).toHaveValue(dateInDays(14))
	})
})
