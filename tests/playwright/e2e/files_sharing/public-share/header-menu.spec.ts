/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../../support/fixtures/public-share-page.ts'
import { createLinkShare, seedSharedFolder } from '../../../support/utils/sharing.ts'
import { getZipEntries } from '../../../support/utils/zip.ts'

const SHARE_NAME = 'shared'
const FEDERATED_SHARE_API = '/apps/federatedfilesharing/createFederatedShare'

/** The direct link points at the share's DAV endpoint and downloads it as a zip. */
const DIRECT_LINK = /\/public\.php\/dav\/files\/.+\/?accept=zip$/

test.describe('files_sharing: Public share - header actions menu', () => {
	test.beforeEach(async ({ user, ownerRequest, publicShare }) => {
		await seedSharedFolder(ownerRequest, user, SHARE_NAME)
		const share = await createLinkShare(ownerRequest, `/${SHARE_NAME}`)
		await publicShare.open(share.url)
	})

	test('can download all files', async ({ page, publicShare }) => {
		const downloadPromise = page.waitForEvent('download')
		await publicShare.primaryAction('Download').click()
		const download = await downloadPromise

		expect(download.suggestedFilename()).toBe(`${SHARE_NAME}.zip`)
		expect(await getZipEntries(download)).toEqual([
			`${SHARE_NAME}/`,
			`${SHARE_NAME}/foo.txt`,
			`${SHARE_NAME}/subfolder/`,
			`${SHARE_NAME}/subfolder/bar.txt`,
		])
	})

	test('offers a direct link and closes the menu when it is used', async ({ publicShare }) => {
		await publicShare.openActionsMenu()

		const directLink = publicShare.actionsMenuEntry('Direct link')
		await expect(directLink).toHaveAttribute('href', DIRECT_LINK)

		await directLink.click()

		await expect(publicShare.actionsMenu()).toBeHidden()
	})

	test('can create a federated share', async ({ page, publicShare }) => {
		await publicShare.openActionsMenu()
		await publicShare.actionsMenuEntry(/Add to your/i).click()

		const dialog = publicShare.federatedShareDialog()
		await expect(dialog).toBeVisible()

		await dialog.getByRole('textbox').fill('user@nextcloud.local')

		const created = page.waitForResponse((r) => r.url().includes(FEDERATED_SHARE_API))
		await dialog.getByRole('button', { name: 'Create share' }).click()
		await created
	})

	test('disables the submit button while the federated share is created', async ({ page, publicShare }) => {
		// Hold the response back so the in-flight state can be observed
		const { promise: held, resolve: release } = Promise.withResolvers<void>()
		await page.route(`**${FEDERATED_SHARE_API}`, async (route) => {
			await held
			await route.fulfill({ status: 503, body: '' })
		})

		await publicShare.openActionsMenu()
		await publicShare.actionsMenuEntry(/Add to your/i).click()

		const dialog = publicShare.federatedShareDialog()
		await dialog.getByRole('textbox').fill('user@nextcloud.local')

		const submit = dialog.getByRole('button', { name: 'Create share' })
		await submit.click()
		await expect(submit).toBeDisabled()

		release()

		await expect(submit).toBeEnabled()
	})

	test('validates the federated share input', async ({ publicShare }) => {
		await publicShare.openActionsMenu()
		await publicShare.actionsMenuEntry(/Add to your/i).click()

		const input = publicShare.federatedShareDialog().getByRole('textbox')

		// A bare domain is missing the user part
		await input.fill('nextcloud.local')
		await expect(input).toHaveValidationMessage(/user/i)

		// And the domain itself has to be a URL
		await input.fill('user@invalid')
		await expect(input).toHaveValidationMessage(/invalid.+url/i)
	})

	test('moves the primary action into the menu on small screens', async ({ page, publicShare }) => {
		await page.setViewportSize({ width: 490, height: 490 })

		// Nothing is rendered next to the menu any more
		await expect(publicShare.primaryAction('Download')).toHaveCount(0)
		await expect(publicShare.primaryAction('Direct link')).toHaveCount(0)
		await expect(publicShare.primaryAction(/Add to your/i)).toHaveCount(0)

		const menu = await publicShare.openActionsMenu()
		await expect(menu.getByRole('menuitem')).toHaveCount(3)
		await expect(publicShare.actionsMenuEntry(/^Download/)).toBeVisible()
		await expect(publicShare.actionsMenuEntry('Direct link')).toHaveAttribute('href', DIRECT_LINK)

		await publicShare.actionsMenuEntry(/Add to your/i).click()

		await expect(publicShare.federatedShareDialog()).toBeVisible()
	})
})
