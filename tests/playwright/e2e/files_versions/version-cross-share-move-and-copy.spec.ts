/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext } from '@playwright/test'
import type { CopyMoveDialogPage } from '../../support/sections/CopyMoveDialogPage.ts'
import type { FilesListPage } from '../../support/sections/FilesListPage.ts'
import type { VersionsTab } from '../../support/sections/VersionsTab.ts'

import { mergeTests } from '@playwright/test'
import { test as sharingTest } from '../../support/fixtures/files-sharing-page.ts'
import { expect, test as versionsTest } from '../../support/fixtures/files-versions-tab-page.ts'
import { mkdir } from '../../support/utils/dav.ts'
import { createShare, waitForShare } from '../../support/utils/sharing.ts'
import { openVersionsPanel, seedThreeVersions } from '../../support/utils/versions.ts'

const test = mergeTests(versionsTest, sharingTest)

const SHARED_FOLDER = 'cross-share'
const FILE_NAME = 'file.txt'

/**
 * Seed a versioned file inside the shared folder for the owner, share the folder
 * with the recipient (full permissions), and wait for the file to propagate.
 * Parent directories of `filePath` (relative to the shared folder) are created
 * first.
 *
 * @param owner - The file owner
 * @param ownerRequest - A request context authenticated as the owner
 * @param recipient - The share recipient
 * @param recipientRequest - A request context authenticated as the recipient
 * @param filePath - The file path relative to the shared folder (e.g. "sub/deep/file.txt")
 */
async function seedSharedVersionedFile(
	owner: User,
	ownerRequest: APIRequestContext,
	recipient: User,
	recipientRequest: APIRequestContext,
	filePath: string,
): Promise<void> {
	await mkdir(ownerRequest, owner, `/${SHARED_FOLDER}`)
	// Create any intermediate folders of the file path inside the shared folder
	const segments = filePath.split('/')
	let current = SHARED_FOLDER
	for (const segment of segments.slice(0, -1)) {
		current += `/${segment}`
		await mkdir(ownerRequest, owner, `/${current}`)
	}
	await seedThreeVersions(ownerRequest, owner, `${SHARED_FOLDER}/${filePath}`)
	await createShare(ownerRequest, `/${SHARED_FOLDER}`, recipient.userId)

	const parent = [SHARED_FOLDER, ...segments.slice(0, -1)].join('/')
	await waitForShare(recipientRequest, recipient, parent, segments.at(-1)!)
}

/**
 * As the recipient, open the versions panel of the file, name its initial
 * version "v1", and close the sidebar.
 */
async function nameInitialVersion(
	filesListPage: FilesListPage,
	versionsTab: VersionsTab,
	folderPath: string,
	fileName: string,
): Promise<void> {
	await filesListPage.open()
	await filesListPage.navigateToFolder(folderPath)
	await openVersionsPanel(filesListPage, versionsTab, fileName)
	await expect(versionsTab.versions()).toHaveCount(3)
	await versionsTab.nameVersion(2, 'v1')
	await expect(versionsTab.version(2)).toContainText('v1')
}

/**
 * Reload from the recipient's root, open the versions of the file at `filePath`
 * and assert all three versions travelled with the move/copy (content v3/v2/v1).
 * A fresh reload avoids the stale sibling rows a cross-storage move can leave
 * behind. `expectLabel` asserts the "v1" label survived — only moves preserve
 * version metadata, copies do not.
 */
async function assertVersionsContent(
	filesListPage: FilesListPage,
	versionsTab: VersionsTab,
	filePath: string,
	{ expectLabel }: { expectLabel: boolean },
): Promise<void> {
	const segments = filePath.split('/')
	const fileName = segments.at(-1)!
	const folderPath = segments.slice(0, -1).join('/')

	await filesListPage.open()
	if (folderPath) {
		await filesListPage.navigateToFolder(folderPath)
	}
	await openVersionsPanel(filesListPage, versionsTab, fileName)

	await expect(versionsTab.versions()).toHaveCount(3)
	expect(await versionsTab.getVersionContent(0)).toBe('v3')
	expect(await versionsTab.getVersionContent(1)).toBe('v2')
	expect(await versionsTab.getVersionContent(2)).toBe('v1')

	if (expectLabel) {
		await expect(versionsTab.version(2)).toContainText('v1')
	}
}

/** Move the given entry out of the current folder to the recipient's root. */
async function moveToRoot(filesListPage: FilesListPage, copyMoveDialog: CopyMoveDialogPage, name: string): Promise<void> {
	await filesListPage.triggerActionForFile(name, 'move-copy')
	await copyMoveDialog.goToAllFiles()
	await copyMoveDialog.moveToCurrentFolder()
}

/** Copy the given entry to the recipient's root. */
async function copyToRoot(filesListPage: FilesListPage, copyMoveDialog: CopyMoveDialogPage, name: string): Promise<void> {
	await filesListPage.triggerActionForFile(name, 'move-copy')
	await copyMoveDialog.goToAllFiles()
	await copyMoveDialog.copyToCurrentFolder()
}

test.describe('files_versions: versions across a share move/copy', () => {
	test('moves the versions when the file is moved out of a received share', async ({ page, user, owner, ownerRequest, filesListPage, versionsTab, filesSidebar, copyMoveDialog }) => {
		await seedSharedVersionedFile(owner, ownerRequest, user, page.request, FILE_NAME)
		await nameInitialVersion(filesListPage, versionsTab, SHARED_FOLDER, FILE_NAME)
		await filesSidebar.close()

		await moveToRoot(filesListPage, copyMoveDialog, FILE_NAME)

		await assertVersionsContent(filesListPage, versionsTab, FILE_NAME, { expectLabel: true })
	})

	test('copies the versions when the file is copied out of a received share', async ({ page, user, owner, ownerRequest, filesListPage, versionsTab, filesSidebar, copyMoveDialog }) => {
		await seedSharedVersionedFile(owner, ownerRequest, user, page.request, FILE_NAME)
		await nameInitialVersion(filesListPage, versionsTab, SHARED_FOLDER, FILE_NAME)
		await filesSidebar.close()

		await copyToRoot(filesListPage, copyMoveDialog, FILE_NAME)

		// A copy keeps version content but not the version metadata (label)
		await assertVersionsContent(filesListPage, versionsTab, FILE_NAME, { expectLabel: false })
	})

	test('moves the versions when a containing folder is moved out of a received share', async ({ page, user, owner, ownerRequest, filesListPage, versionsTab, filesSidebar, copyMoveDialog }) => {
		const subFolder = 'sub'
		const subSubFolder = 'deep'
		const relPath = `${subFolder}/${subSubFolder}/${FILE_NAME}`
		await seedSharedVersionedFile(owner, ownerRequest, user, page.request, relPath)
		await nameInitialVersion(filesListPage, versionsTab, `${SHARED_FOLDER}/${subFolder}/${subSubFolder}`, FILE_NAME)
		await filesSidebar.close()

		await filesListPage.open()
		await filesListPage.navigateToFolder(SHARED_FOLDER)
		await moveToRoot(filesListPage, copyMoveDialog, subFolder)

		await assertVersionsContent(filesListPage, versionsTab, relPath, { expectLabel: true })
	})

	test('copies the versions when a containing folder is copied out of a received share', async ({ page, user, owner, ownerRequest, filesListPage, versionsTab, filesSidebar, copyMoveDialog }) => {
		const subFolder = 'sub'
		const subSubFolder = 'deep'
		const relPath = `${subFolder}/${subSubFolder}/${FILE_NAME}`
		await seedSharedVersionedFile(owner, ownerRequest, user, page.request, relPath)
		await nameInitialVersion(filesListPage, versionsTab, `${SHARED_FOLDER}/${subFolder}/${subSubFolder}`, FILE_NAME)
		await filesSidebar.close()

		await filesListPage.open()
		await filesListPage.navigateToFolder(SHARED_FOLDER)
		await copyToRoot(filesListPage, copyMoveDialog, subFolder)

		await assertVersionsContent(filesListPage, versionsTab, relPath, { expectLabel: false })
	})
})
