/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '@playwright/test'

test.describe('404 error page', () => {
	test('renders 404 page with a link back to login', async ({ page }) => {
		// No authentication — the 404 page is shown to unauthenticated visitors.
		await page.goto('/doesnotexist')

		await expect(page.getByRole('heading', { name: /Page not found/ })).toBeVisible()

		const backLink = page.getByRole('link', { name: /Back to Nextcloud/ })
		await expect(backLink).toBeVisible()
		await backLink.click()

		await expect(page).toHaveURL(/\/login$/)
	})
})
