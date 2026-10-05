/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { PublicShareAuthPage } from '../sections/PublicShareAuthPage.ts'
import { uploadContent } from '../utils/dav.ts'
import { createLinkShare } from '../utils/sharing.ts'
import { test as publicShareTest } from './public-share-page.ts'

type PublicShareAuthFixtures = {
	/** A password-protected link share of a single file owned by `user`. */
	protectedShare: { url: string, token: string, password: string, fileName: string }
	/** The password prompt of a protected share. */
	publicShareAuth: PublicShareAuthPage
}

/**
 * Fixtures for password-protected link shares. Like the public share fixtures
 * the `page` is a guest that is not logged in.
 */
export const test = publicShareTest.extend<PublicShareAuthFixtures>({
	protectedShare: async ({ user, ownerRequest }, use) => {
		const fileName = 'protected.txt'
		const password = 'correct horse battery staple'
		await uploadContent(ownerRequest, user, 'protected content', 'text/plain', `/${fileName}`)
		const { url, token } = await createLinkShare(ownerRequest, `/${fileName}`, { password })
		await use({ url, token, password, fileName })
	},

	publicShareAuth: async ({ page }, use) => {
		await use(new PublicShareAuthPage(page))
	},
})

export { expect } from '../matchers.ts'
