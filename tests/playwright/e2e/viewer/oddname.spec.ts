/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { MediaKind } from '../../support/sections/ViewerPage.ts'

import { expect, test } from '../../support/fixtures/viewer-page.ts'
import { FilesListPage } from '../../support/sections/FilesListPage.ts'
import { ViewerPage } from '../../support/sections/ViewerPage.ts'
import { mkdir } from '../../support/utils/dav.ts'
import { createLinkShare } from '../../support/utils/sharing.ts'

/**
 * Build a name aimed at breaking the viewer in case of escaping errors.
 *
 * @param realName - The original file name
 */
function naughtyFileName(realName: string): string {
	const ext = realName.split('.').pop() ?? ''
	return (
		'~⛰️ shot of a $[big} mountain`, '
		+ "realy #1's "
		+ '" #_+="%2520%27%22%60%25%21%23 was this called '
		+ realName
		+ 'in the'
		+ '☁️'
		+ '👩‍💻'
		+ '? :* .'
		+ ext.toUpperCase()
	)
}

/**
 * Build a folder name with special characters around a file name.
 *
 * @param realName - The original file name
 */
function naughtyFolderName(realName: string): string {
	return 'Nextcloud "%27%22%60%25%21%23" >`⛰️<' + realName + "><` e*'rocks!#?#%~"
}

interface OddCase {
	fixture: string
	mime: string
	kind: MediaKind
}

const CASES: OddCase[] = [
	{ fixture: 'image.png', mime: 'image/png', kind: 'image' },
	{ fixture: 'video1.mp4', mime: 'video/mp4', kind: 'video' },
	{ fixture: 'audio.mp3', mime: 'audio/mpeg', kind: 'audio' },
]

for (const testCase of CASES) {
	const placedName = naughtyFileName(testCase.fixture)
	const folderName = naughtyFolderName(testCase.fixture)

	test.describe(`Odd file name (${testCase.fixture})`, () => {
		test.beforeEach(async ({ page, user, filesListPage, uploadMedia }) => {
			await mkdir(page.request, user, `/${folderName}`)
			await uploadMedia(testCase.fixture, `/${folderName}/${placedName}`, testCase.mime)
			await filesListPage.open()
		})

		test('opens a file with an odd name in the viewer', async ({ filesListPage, openFile, viewerPage }) => {
			await openFile(folderName)
			await expect(filesListPage.getRowForFile(placedName)).toBeVisible()

			await openFile(placedName)
			await viewerPage.waitForOpen()

			expect(await viewerPage.currentName()).toBe(placedName)
			await viewerPage.expectHandler(testCase.kind)

			await viewerPage.close()
		})
	})
}

test.describe('Odd file name (image, sidebar and public share)', () => {
	const fixture = 'image.png'
	const placedName = naughtyFileName(fixture)
	const folderName = naughtyFolderName(fixture)

	test.beforeEach(async ({ page, user, filesListPage, uploadMedia }) => {
		await mkdir(page.request, user, `/${folderName}`)
		await uploadMedia(fixture, `/${folderName}/${placedName}`, 'image/png')
		await filesListPage.open()
	})

	test('opens the sidebar for an odd file name', async ({ page, openFile, viewerPage }) => {
		await openFile(folderName)
		await openFile(placedName)
		await viewerPage.waitForOpen()

		await viewerPage.openSidebar()

		const sidebar = page.locator('aside.app-sidebar')
		await expect(sidebar).toBeVisible()
		await expect(sidebar.locator('.app-sidebar-header__mainname')).toContainText(placedName)
	})

	test('opens an odd file name from a public link share', async ({ browser, baseURL, playwright, user }) => {
		// Seed and share as the owner through a clean basic-auth request context —
		// browser session cookies would otherwise win over basic auth.
		const ownerRequest = await playwright.request.newContext({
			baseURL,
			httpCredentials: { username: user.userId, password: user.password, send: 'always' },
		})
		let share
		try {
			share = await createLinkShare(ownerRequest, `/${folderName}`)
		} finally {
			await ownerRequest.dispose()
		}

		// Visit the share as an anonymous guest, in a fresh unauthenticated context.
		const context = await browser.newContext({ storageState: undefined, baseURL })
		try {
			const guestPage = await context.newPage()
			await guestPage.goto(share.url)

			const filesListPage = new FilesListPage(guestPage)
			const viewerPage = new ViewerPage(guestPage)

			await expect(filesListPage.getRowForFile(placedName)).toBeVisible()

			await filesListPage.getRowNameLinkForFile(placedName).click()
			await viewerPage.waitForOpen()

			expect(await viewerPage.currentName()).toBe(placedName)
			await viewerPage.expectHandler('image')
		} finally {
			await context.close()
		}
	})
})
