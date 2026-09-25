/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { FilesListPage } from '../../support/sections/FilesListPage.ts'

import { expect, test } from '../../support/fixtures/transfer-ownership-page.ts'
import { getFileContent, mkdir, rm, uploadContent } from '../../support/utils/dav.ts'
import { getToast } from '../../support/utils/toast.ts'
import { completeOwnershipTransfer, transferFolderPattern } from '../../support/utils/transferOwnership.ts'

/**
 * Assert that the only entry in the current list is the folder a transfer from
 * `source` created, and return its name — it carries the time of the transfer.
 *
 * @param filesList - The files list of the new owner, showing their root
 * @param source - The user the files were transferred from
 */
async function expectSingleTransferFolder(filesList: FilesListPage, source: User): Promise<string> {
	await expect.poll(() => filesList.getRowNames())
		.toEqual([expect.stringMatching(transferFolderPattern(source))])

	const [name] = await filesList.getRowNames()
	return name
}

test.describe('Files: Transfer ownership', () => {
	// Accepting the transfer and running its background job shell out to occ,
	// which takes considerably longer than the browser interaction itself
	test.slow()

	test.beforeEach(async ({ page, user, recipient, recipientPage }) => {
		// Both accounts start with a welcome.txt — remove it so the transferred
		// files are the only content of either account
		await rm(page.request, user, '/welcome.txt')
		await rm(recipientPage.request, recipient, '/welcome.txt')
	})

	test('transfers a single file', async ({ page, user, filesListPage, recipient, recipientFilesList, recipientPage, recipientRequest, transferOwnershipPage }) => {
		await uploadContent(page.request, user, 'transferred content', 'text/plain', '/document.txt')
		await uploadContent(page.request, user, 'kept content', 'text/plain', '/other.txt')

		await transferOwnershipPage.open()
		await expect(transferOwnershipPage.getSubmitButton()).toBeDisabled()
		await expect(transferOwnershipPage.getMissingNodeHint()).toHaveCount(1)
		await expect(transferOwnershipPage.getMissingOwnerHint()).toHaveCount(1)

		await transferOwnershipPage.selectFile('document.txt')
		await expect(transferOwnershipPage.getMissingNodeHint()).toHaveCount(0)

		await transferOwnershipPage.selectNewOwner(recipient)
		await expect(transferOwnershipPage.getMissingOwnerHint()).toHaveCount(0)

		await expect(transferOwnershipPage.getSubmitButton())
			.toHaveAccessibleName(`Transfer document.txt to ${recipient.userId}`)
		await transferOwnershipPage.submit()

		await expect(getToast(page, 'Ownership transfer request sent')).toBeVisible()
		// The form is reset, ready for the next transfer
		await expect(transferOwnershipPage.getSubmitButton()).toBeDisabled()
		await expect(transferOwnershipPage.getMissingNodeHint()).toHaveCount(1)
		await expect(transferOwnershipPage.getMissingOwnerHint()).toHaveCount(1)

		await completeOwnershipTransfer(recipientRequest, user, recipient)

		// The previous owner keeps everything but the transferred file
		await filesListPage.open()
		await expect(filesListPage.getRowForFile('other.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('document.txt')).toHaveCount(0)

		// The new owner received it, with its content, in the transfer folder
		await recipientFilesList.open()
		const transferFolder = await expectSingleTransferFolder(recipientFilesList, user)
		await recipientFilesList.navigateToFolder(transferFolder)
		await expect(recipientFilesList.getRowForFile('document.txt')).toBeVisible()
		expect(await getFileContent(recipientPage.request, recipient, `${transferFolder}/document.txt`))
			.toBe('transferred content')
	})

	test('transfers a folder with all of its content', async ({ page, user, filesListPage, recipient, recipientFilesList, recipientRequest, transferOwnershipPage }) => {
		await mkdir(page.request, user, '/project')
		await uploadContent(page.request, user, 'readme', 'text/plain', '/project/readme.md')
		await mkdir(page.request, user, '/project/notes')
		await uploadContent(page.request, user, 'todo', 'text/plain', '/project/notes/todo.md')

		await transferOwnershipPage.open()
		await transferOwnershipPage.selectFolder('project')
		await transferOwnershipPage.selectNewOwner(recipient)
		await transferOwnershipPage.submit()
		await expect(getToast(page, 'Ownership transfer request sent')).toBeVisible()

		await completeOwnershipTransfer(recipientRequest, user, recipient)

		// The folder is gone for the previous owner
		await filesListPage.open()
		await expect(filesListPage.getRows()).toHaveCount(0)

		// The new owner received the folder with its whole tree
		await recipientFilesList.open()
		const transferFolder = await expectSingleTransferFolder(recipientFilesList, user)
		await recipientFilesList.navigateToFolder(`${transferFolder}/project`)
		await expect(recipientFilesList.getRowForFile('readme.md')).toBeVisible()

		await recipientFilesList.navigateToFolder('notes')
		await expect(recipientFilesList.getRowForFile('todo.md')).toBeVisible()
	})

	test('transfers all files at once', async ({ page, user, filesListPage, recipient, recipientFilesList, recipientRequest, transferOwnershipPage }) => {
		await uploadContent(page.request, user, 'text', 'text/plain', '/document.txt')
		await mkdir(page.request, user, '/pictures')
		await uploadContent(page.request, user, 'image', 'image/png', '/pictures/image.png')

		await transferOwnershipPage.open()
		await transferOwnershipPage.selectAllFiles(user)
		await transferOwnershipPage.selectNewOwner(recipient)
		await transferOwnershipPage.submit()
		await expect(getToast(page, 'Ownership transfer request sent')).toBeVisible()

		await completeOwnershipTransfer(recipientRequest, user, recipient)

		// The previous owner is left with an empty account
		await filesListPage.open()
		await expect(filesListPage.getRows()).toHaveCount(0)

		// Everything they owned is now in the new owners transfer folder
		await recipientFilesList.open()
		const transferFolder = await expectSingleTransferFolder(recipientFilesList, user)
		await recipientFilesList.navigateToFolder(transferFolder)
		await expect(recipientFilesList.getRowForFile('document.txt')).toBeVisible()

		await recipientFilesList.navigateToFolder('pictures')
		await expect(recipientFilesList.getRowForFile('image.png')).toBeVisible()
	})
})
