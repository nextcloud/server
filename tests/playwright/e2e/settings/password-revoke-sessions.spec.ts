/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/personal-settings-page.ts'
import { addAppPassword } from '../../support/utils/auth-tokens.ts'

const NEW_PASSWORD = 'a-brand-new-password'

test.describe('Settings: revoking sessions on a password change', () => {
	test('drops the other sessions live and leaves the current one usable', async ({ page, passwordPage, devicesSessionsPage, user }) => {
		await addAppPassword(user.userId, 'Playwright device')

		await passwordPage.open()
		await expect(devicesSessionsPage.tokenRows()).toHaveCount(2)

		await passwordPage.changePassword(NEW_PASSWORD, true)

		// Devices & sessions is a separate Vue app, reached over the event bus.
		await expect(devicesSessionsPage.tokenRow('Playwright device')).toHaveCount(0)
		await expect(devicesSessionsPage.tokenRows()).toHaveCount(1)

		// A killed session token would redirect this reload to the login page.
		await page.reload()
		await expect(devicesSessionsPage.heading()).toBeVisible()
		await expect(devicesSessionsPage.tokenRow('This session')).toBeVisible()
	})

	test('keeps the other sessions when the option is left unticked', async ({ page, passwordPage, devicesSessionsPage, user }) => {
		await addAppPassword(user.userId, 'Playwright device')

		await passwordPage.open()
		await passwordPage.changePassword(NEW_PASSWORD)

		// Reload so the assertion reflects the server, not just untouched local state.
		await page.reload()
		await expect(devicesSessionsPage.tokenRow('Playwright device')).toBeVisible()
		await expect(devicesSessionsPage.tokenRows()).toHaveCount(2)
	})
})
