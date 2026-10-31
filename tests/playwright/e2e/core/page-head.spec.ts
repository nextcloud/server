/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test as publicTest } from '../../support/fixtures/public-share-page.ts'
import { test as userTest } from '../../support/fixtures/random-user-session.ts'
import { uploadContent } from '../../support/utils/dav.ts'
import { createLinkShare } from '../../support/utils/sharing.ts'

/**
 * The page `<head>` must declare each icon exactly once: browsers pick the last
 * matching `rel`, so a duplicate `apple-touch-icon-precomposed` silently
 * overrides the modern `apple-touch-icon`.
 */

userTest.describe('core: page head', () => {
	userTest('user layout emits a single apple-touch-icon', async ({ page }) => {
		await page.goto('apps/files')

		await expect(page.locator('head link[rel="apple-touch-icon"]')).toHaveCount(1)
		await expect(page.locator('head link[rel="apple-touch-icon-precomposed"]')).toHaveCount(0)
		await expect(page.locator('head link[rel="mask-icon"]')).toHaveCount(1)
	})
})

publicTest.describe('core: page head', () => {
	publicTest('public layout emits a single apple-touch-icon', async ({ page, user, ownerRequest, publicShare }) => {
		await uploadContent(ownerRequest, user, '<content>head</content>', 'text/plain', '/head-check.txt')
		const share = await createLinkShare(ownerRequest, '/head-check.txt')

		await publicShare.open(share.url)

		await expect(page.locator('head link[rel="apple-touch-icon"]')).toHaveCount(1)
		await expect(page.locator('head link[rel="apple-touch-icon-precomposed"]')).toHaveCount(0)
		await expect(page.locator('head link[rel="mask-icon"]')).toHaveCount(1)
	})
})
