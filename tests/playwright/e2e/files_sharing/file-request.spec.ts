/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/sharing-page.ts'
import { PublicSharePage } from '../../support/sections/PublicSharePage.ts'
import { getFileContent, mkdir } from '../../support/utils/dav.ts'

const FOLDER = 'test-folder'
const GUEST = 'Guest'

/**
 * The whole file-request round trip: the owner creates the request through the
 * "New" menu, a guest identifies itself and uploads, and the upload shows up in
 * the owner's folder under the guest's name.
 *
 * The Cypress original split this across three tests that shared the share URL
 * through a closure; it is one flow, so it is one test here.
 */
test('files_sharing: a guest can upload through a file request', async ({ page, browser, user, filesListPage }) => {
	await mkdir(page.request, user, `/${FOLDER}`)
	await filesListPage.open()
	await filesListPage.navigateToFolder(FOLDER)

	// The owner creates the file request
	await page.locator('[data-cy-upload-picker]').getByRole('button', { name: 'New' }).first().click()
	await page.getByRole('menuitem', { name: 'Create file request' }).click()

	const dialog = page.getByRole('dialog', { name: 'Create a file request' })
	await expect(dialog).toBeVisible()
	await expect(dialog.getByRole('textbox', { name: 'Upload destination' })).toHaveValue(new RegExp(FOLDER))
	await dialog.getByRole('textbox', { name: 'Request subject' }).fill('Please upload')
	await dialog.getByRole('button', { name: 'Continue' }).click()

	// Neither an expiration date nor a password is asked for by default
	await expect(dialog.getByRole('checkbox', { name: 'Set a submission expiration date' })).not.toBeChecked()
	await expect(dialog.getByRole('checkbox', { name: 'Set a password' })).not.toBeChecked()
	await dialog.getByRole('button', { name: 'Continue' }).click()

	const created = page.getByRole('dialog', { name: 'File request created' })
	const shareUrl = await created.getByRole('textbox', { name: 'Share link' }).inputValue()
	expect(shareUrl).toContain('/s/')

	// The dialog's own close control, not the dialog chrome's "Close" button
	await created.getByRole('button', { name: 'Close', exact: true }).last().click()
	await expect(created).toBeHidden()

	// A guest — a separate, anonymous browser context — uploads a file
	const guestContext = await browser.newContext()
	try {
		const guestPage = await guestContext.newPage()
		const publicShare = new PublicSharePage(guestPage)

		await publicShare.open(shareUrl)
		await publicShare.submitGuestName(GUEST)

		await expect(guestPage.getByText(`Upload files to ${FOLDER}`).first()).toBeVisible()

		await guestPage.getByRole('button', { name: 'Upload', exact: true }).click()
		await publicShare.uploadFiles('Upload files', [
			{ name: 'file.txt', mimeType: 'text/plain', buffer: Buffer.from('abcdef') },
		])

		// The upload lands in a folder named after the guest
		await expect.poll(() => getFileContent(page.request, user, `/${FOLDER}/${GUEST}/file.txt`).catch(() => ''))
			.toBe('abcdef')
	} finally {
		await guestContext.close()
	}

	// And the owner sees it there
	await filesListPage.open()
	await filesListPage.navigateToFolder(FOLDER)
	await expect(filesListPage.getRowForFile(GUEST)).toBeVisible()

	await filesListPage.navigateToFolder(GUEST)
	await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()
})
