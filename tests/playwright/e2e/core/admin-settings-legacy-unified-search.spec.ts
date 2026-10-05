/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/random-user-session.ts'
import { LegacyUnifiedSearchPage } from '../../support/sections/LegacyUnifiedSearchPage.ts'
import { uploadContent } from '../../support/utils/dav.ts'

// The `admin-settings-` prefix puts this in the serial project: which search
// the header offers is an instance-wide setting.
test.describe('core: Legacy unified search', () => {
	test.beforeAll(async () => {
		await runOcc(['config:system:set', 'unified_search.enabled', '--value', 'true', '--type', 'boolean'])
	})

	test.afterAll(async () => {
		await runOcc(['config:system:delete', 'unified_search.enabled'])
	})

	test('lists the results grouped by provider and loads more on request', async ({ page, user }) => {
		for (let index = 1; index <= 7; index++) {
			await uploadContent(page.request, user, 'content', 'text/plain', `/legacy-search-${index}.txt`)
		}
		await page.goto('apps/files')

		const search = new LegacyUnifiedSearchPage(page)
		await search.search('legacy-search')

		const files = /^legacy-search-\d\.txt/
		await expect(search.resultLinks('Files', files)).toHaveCount(5)
		await search.loadMoreButton('Files').click()

		await expect(search.resultLinks('Files', files)).toHaveCount(7)
		await expect(search.loadMoreButton('Files')).toHaveCount(0)
	})

	test('explains that the query is too short', async ({ page }) => {
		await page.goto('apps/files')

		const search = new LegacyUnifiedSearchPage(page)
		await search.trigger().click()

		await expect(search.input()).toBeFocused()
		await expect(page.getByText('Start typing to search')).toBeVisible()
	})
})
