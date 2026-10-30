/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server'
import { expect, test } from '../../../support/fixtures/public-share-page.ts'
import { mkdir } from '../../../support/utils/dav.ts'
import { BUNDLED_PERMISSIONS, createLinkShare } from '../../../support/utils/sharing.ts'

const SHARE_NAME = 'shared'
const DISCLAIMER = 'TEST: Some disclaimer text'

/**
 * The disclaimer is an instance-wide setting, so this lives in the serial
 * `admin-settings` project rather than next to the other file-drop tests.
 */
test.beforeAll(async () => {
	await runOcc(['config:app:set', '--value', DISCLAIMER, '--type', 'string', 'core', 'shareapi_public_link_disclaimertext'])
})

test.afterAll(async () => {
	await runOcc(['config:app:delete', 'core', 'shareapi_public_link_disclaimertext'])
})

test('files_sharing: a file drop shows the terms of service', async ({ page, user, ownerRequest, publicShare }) => {
	await mkdir(ownerRequest, user, `/${SHARE_NAME}`)
	const share = await createLinkShare(ownerRequest, `/${SHARE_NAME}`, {
		permissions: BUNDLED_PERMISSIONS.FILE_DROP,
	})
	await publicShare.open(share.url)

	await expect(publicShare.fileDropDescription(SHARE_NAME)).toBeVisible()
	await expect(page.getByText('agree to the terms of service')).toBeVisible()

	await page.getByRole('button', { name: /terms of service/i }).click()

	const dialog = page.getByRole('dialog', { name: 'Terms of service' })
	await expect(dialog).toContainText(DISCLAIMER)

	await dialog.getByRole('button', { name: 'Close' }).click()

	await expect(dialog).toBeHidden()
})
