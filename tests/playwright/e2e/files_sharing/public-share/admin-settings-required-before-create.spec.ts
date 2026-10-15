/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { Page } from '@playwright/test'
import type { FilesListPage } from '../../../support/sections/FilesListPage.ts'
import type { SharingTab } from '../../../support/sections/SharingTab.ts'

import { runOcc } from '@nextcloud/e2e-test-server'
import { expect, test } from '../../../support/fixtures/sharing-page.ts'
import { openSharingPanel, seedSharedFolder } from '../../../support/utils/sharing.ts'

/**
 * The instance-wide link-share defaults. Each is either off, defaulted or
 * enforced, and the share editor has to ask for whatever is missing *before* it
 * creates the share.
 */
interface LinkShareDefaults {
	/** `shareapi_enable_link_password_by_default` — offer a password field up front. */
	askForPassword?: boolean
	/** `shareapi_enforce_links_password` — a password is mandatory. */
	enforcePassword?: boolean
	/** `shareapi_default_expire_date` (+ `shareapi_expire_after_n_days`). */
	defaultExpirationDate?: boolean
	/** `shareapi_enforce_expire_date` — the expiration date cannot be removed. */
	enforceExpirationDate?: boolean
}

/** The page objects a test hands to {@link createLinkShareWithDefaults}. */
interface SharingContext {
	page: Page
	user: User
	filesListPage: FilesListPage
	sharingTab: SharingTab
}

/** The number of days the default expiration date is configured to. */
const EXPIRE_AFTER_DAYS = 2

/** `YYYY-MM-DD` of today plus `days`, i.e. what the editor should pre-fill. */
function dateInDays(days: number): string {
	const date = new Date()
	date.setDate(date.getDate() + days)
	return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

async function applyDefaults(defaults: LinkShareDefaults): Promise<void> {
	const flag = (value?: boolean) => value ? 'yes' : 'no'
	await runOcc(['config:app:set', '--value', flag(defaults.askForPassword), 'core', 'shareapi_enable_link_password_by_default'])
	await runOcc(['config:app:set', '--value', flag(defaults.enforcePassword), 'core', 'shareapi_enforce_links_password'])
	await runOcc(['config:app:set', '--value', flag(defaults.defaultExpirationDate), 'core', 'shareapi_default_expire_date'])
	await runOcc(['config:app:set', '--value', flag(defaults.enforceExpirationDate), 'core', 'shareapi_enforce_expire_date'])
	if (defaults.defaultExpirationDate) {
		await runOcc(['config:app:set', '--value', String(EXPIRE_AFTER_DAYS), 'core', 'shareapi_expire_after_n_days'])
	}
}

/**
 * Create a link share through the editor under the given defaults, asserting on
 * the way that the editor asks for exactly what the configuration demands, and
 * return the created share's URL.
 *
 * @param defaults - The instance defaults to apply first
 * @param shareName - The folder to share
 * @param context - The page objects to drive
 */
async function createLinkShareWithDefaults(
	defaults: LinkShareDefaults,
	shareName: string,
	{ page, user, filesListPage, sharingTab }: SharingContext,
): Promise<string> {
	await applyDefaults(defaults)
	await seedSharedFolder(page.request, user, shareName)
	await filesListPage.open()
	await openSharingPanel(filesListPage, sharingTab, shareName)

	// With something still missing, the button opens the "required information"
	// dialog instead of creating the share right away.
	await sharingTab.panel().getByRole('button', { name: 'Create a new share link' }).click()
	const pending = sharingTab.pendingShareDialog()

	if (defaults.enforcePassword) {
		await expect(pending.getByRole('checkbox', { name: 'Password protection (enforced)' })).toBeVisible()
	} else if (defaults.askForPassword) {
		await expect(pending.getByRole('checkbox', { name: 'Password protection' })).toBeVisible()
	}
	const password = pending.getByRole('textbox', { name: 'Enter a password' })
	await expect(password).toBeVisible()
	await expect(password).toBeEnabled()
	if (defaults.enforcePassword) {
		// An enforced password has to be filled in before the share can be created
		await password.fill(`s3cret-${shareName}`)
	}

	if (defaults.enforceExpirationDate) {
		await expect(pending.getByRole('checkbox', { name: 'Enable link expiration (enforced)' })).toBeVisible()
	} else if (defaults.defaultExpirationDate) {
		await expect(pending.getByRole('checkbox', { name: 'Enable link expiration' })).toBeVisible()
	}
	if (defaults.defaultExpirationDate || defaults.enforceExpirationDate) {
		// The date comes pre-filled with the configured default
		await expect(sharingTab.pendingExpirationDateInput()).toHaveValue(dateInDays(EXPIRE_AFTER_DAYS))
	}

	return sharingTab.confirmPendingLinkShare()
}

/**
 * The Cypress original ran ten cases, but only these five configurations are
 * actually distinct — the others repeat one of them with the same effective
 * settings (e.g. "not enforced" spelled out rather than left off).
 */
test.describe('files_sharing: Link share defaults asked for before creating', () => {
	test.afterEach(async () => {
		await runOcc(['config:app:delete', 'core', 'shareapi_enable_link_password_by_default'])
		await runOcc(['config:app:delete', 'core', 'shareapi_enforce_links_password'])
		await runOcc(['config:app:delete', 'core', 'shareapi_default_expire_date'])
		await runOcc(['config:app:delete', 'core', 'shareapi_enforce_expire_date'])
		await runOcc(['config:app:delete', 'core', 'shareapi_expire_after_n_days'])
	})

	test('password and expiration date both enforced', async ({ page, user, filesListPage, sharingTab }) => {
		const url = await createLinkShareWithDefaults({
			askForPassword: true,
			enforcePassword: true,
			defaultExpirationDate: true,
			enforceExpirationDate: true,
		}, 'password-and-expire-enforced', { page, user, filesListPage, sharingTab })

		expect(url).toMatch(/\/s\//)
	})

	test('password enforced, expiration date defaulted', async ({ page, user, filesListPage, sharingTab }) => {
		const url = await createLinkShareWithDefaults({
			askForPassword: true,
			enforcePassword: true,
			defaultExpirationDate: true,
		}, 'password-enforced-default-expire', { page, user, filesListPage, sharingTab })

		expect(url).toMatch(/\/s\//)
	})

	test('password optional, expiration date enforced', async ({ page, user, filesListPage, sharingTab }) => {
		const url = await createLinkShareWithDefaults({
			askForPassword: true,
			defaultExpirationDate: true,
			enforceExpirationDate: true,
		}, 'default-password-expire-enforced', { page, user, filesListPage, sharingTab })

		expect(url).toMatch(/\/s\//)
	})

	test('password and expiration date both only defaulted', async ({ page, user, filesListPage, sharingTab }) => {
		const url = await createLinkShareWithDefaults({
			askForPassword: true,
			defaultExpirationDate: true,
		}, 'default-password-and-expire', { page, user, filesListPage, sharingTab })

		expect(url).toMatch(/\/s\//)
	})

	test('nothing defaulted or enforced creates the share directly', async ({ page, user, filesListPage, sharingTab }) => {
		await applyDefaults({})
		await seedSharedFolder(page.request, user, 'no-defaults')
		await filesListPage.open()
		await openSharingPanel(filesListPage, sharingTab, 'no-defaults')

		// Nothing to ask for, so the button creates the share straight away
		const url = await sharingTab.createLinkShare()

		expect(url).toMatch(/\/s\//)
		await expect(sharingTab.linkShareEntries()).toHaveCount(1)
	})
})
