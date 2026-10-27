/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server'
import { expect, test } from '../../support/fixtures/files-page.ts'

/**
 * The "Shared by link" view lists link shares, so it must only be registered
 * while public link sharing is allowed instance-wide.
 */
test.describe('files_sharing: Shared by link view', () => {
	test.afterAll(async () => {
		await runOcc(['config:app:delete', 'core', 'shareapi_allow_links'])
	})

	test('is listed while link sharing is enabled', async ({ filesListPage, filesNavigation }) => {
		await runOcc(['config:app:set', '--value', 'yes', 'core', 'shareapi_allow_links'])

		await filesListPage.open('shareoverview')
		await filesNavigation.expandNavigationEntry('Shares')

		await expect(filesNavigation.getNavigationEntry('Shared by link')).toBeVisible()
	})

	test('is not listed when link sharing is disabled', async ({ filesListPage, filesNavigation }) => {
		await runOcc(['config:app:set', '--value', 'no', 'core', 'shareapi_allow_links'])

		await filesListPage.open('shareoverview')
		await filesNavigation.expandNavigationEntry('Shares')

		// The sibling views are expanded and visible, so a missing entry is really
		// an unregistered view and not just a collapsed parent.
		await expect(filesNavigation.getNavigationEntry('Shared with others')).toBeVisible()
		await expect(filesNavigation.getNavigationEntry('Shared by link')).toHaveCount(0)
	})
})
