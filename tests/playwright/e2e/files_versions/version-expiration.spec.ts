/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect, test } from '../../support/fixtures/files-versions-tab-page.ts'
import { openVersionsPanel, seedThreeVersions } from '../../support/utils/versions.ts'

const FILE_NAME = 'expiration.txt'

/**
 * Run the versioning expiration for a single user with a retention obligation
 * that keeps only the current version (and any named versions). The obligation
 * is a system config, so it is reset to the default afterwards even on failure;
 * the expiry itself is scoped to `user` so it never touches other tests' files.
 */
async function expireVersions(user: User): Promise<void> {
	await runOcc(['config:system:set', 'versions_retention_obligation', '--value', '0, 0'])
	try {
		await runOcc(['versions:expire', user.userId])
	} finally {
		await runOcc(['config:system:set', 'versions_retention_obligation', '--value', 'auto'])
	}
}

test.describe('files_versions: versions expiration', () => {
	test('expires all but the current version', async ({ page, user, filesListPage, versionsTab }) => {
		await seedThreeVersions(page.request, user, `/${FILE_NAME}`)
		await expireVersions(user)

		await filesListPage.open()
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)

		await expect(versionsTab.versions()).toHaveCount(1)
		await expect(versionsTab.version(0)).toContainText('Current version')
		expect(await versionsTab.getVersionContent(0)).toBe('v3')
	})

	test('keeps named versions when expiring', async ({ page, user, filesListPage, versionsTab }) => {
		await seedThreeVersions(page.request, user, `/${FILE_NAME}`)

		await filesListPage.open()
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)
		await expect(versionsTab.versions()).toHaveCount(3)

		// Name the initial version so it survives expiration
		await versionsTab.nameVersion(2, 'v1')
		await expireVersions(user)

		await filesListPage.open()
		await openVersionsPanel(filesListPage, versionsTab, FILE_NAME)

		await expect(versionsTab.versions()).toHaveCount(2)
		await expect(versionsTab.version(0)).toContainText('Current version')
		await expect(versionsTab.version(1)).toContainText('v1')
		expect(await versionsTab.getVersionContent(0)).toBe('v3')
		expect(await versionsTab.getVersionContent(1)).toBe('v1')
	})
})
