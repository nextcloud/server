/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../../support/fixtures/public-share-page.ts'
import { createLinkShare, seedSharedFolder } from '../../../support/utils/sharing.ts'

/**
 * The guest identification menu of a public share: a visitor is anonymous until
 * they set a public name, which is then remembered across shares.
 */
test.describe('files_sharing: Public share - guest identification', () => {
	test('shows the anonymous guest menu', async ({ user, ownerRequest, publicShare }) => {
		await seedSharedFolder(ownerRequest, user, 'public1')
		const share = await createLinkShare(ownerRequest, '/public1')
		await publicShare.open(share.url)

		const menu = await publicShare.openUserMenu()

		await expect(menu.getByRole('note')).toContainText('not identified')
		await expect(menu.getByRole('link', { name: /Set public name/i })).toBeVisible()
	})

	test('can set a public name', async ({ user, ownerRequest, publicShare }) => {
		await seedSharedFolder(ownerRequest, user, 'public1')
		const share = await createLinkShare(ownerRequest, '/public1')
		await publicShare.open(share.url)

		await publicShare.setPublicName('John Doe')

		// The avatar is now the one generated for that guest name
		await expect(publicShare.userMenuButton().locator('img'))
			.toHaveAttribute('src', /avatar\/guest\/John%20Doe/)
	})

	test('keeps the public name across shares and allows changing it', async ({ user, ownerRequest, publicShare }) => {
		await seedSharedFolder(ownerRequest, user, 'public1')
		await seedSharedFolder(ownerRequest, user, 'public2')
		const first = await createLinkShare(ownerRequest, '/public1')
		const second = await createLinkShare(ownerRequest, '/public2')

		await publicShare.open(first.url)
		await publicShare.setPublicName('Jane Doe')

		// The name travels to another share of the same visitor
		await publicShare.open(second.url)
		const menu = await publicShare.openUserMenu()
		await expect(menu.getByRole('note')).toContainText('Your guest name: Jane Doe')

		await publicShare.setPublicName('Foo Bar')

		await expect(publicShare.userMenuButton().locator('img'))
			.toHaveAttribute('src', /avatar\/guest\/Foo%20Bar/)
	})
})
