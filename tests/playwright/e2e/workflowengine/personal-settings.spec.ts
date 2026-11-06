/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/random-user-session.ts'

test.describe('Flow personal settings', () => {
	test('hides the Flow entry when no flow is available to users', async ({ page }) => {
		await page.goto('settings/user/profile-contact')

		const personalSettings = page.getByRole('list', { name: 'Personal', exact: true })
		await expect(personalSettings).toBeVisible()
		await expect(personalSettings.getByRole('link', { name: 'Flow', exact: true })).toHaveCount(0)
	})
})
