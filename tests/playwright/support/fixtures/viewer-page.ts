/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext } from '@playwright/test'

import { mergeTests } from '@playwright/test'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { ViewerPage } from '../sections/ViewerPage.ts'
import { uploadContent } from '../utils/dav.ts'
import { test as filesTest } from './files-page.ts'
import { test as sharingTestBase } from './files-sharing-page.ts'
import { test as publicShareTestBase } from './public-share-page.ts'

/**
 * The absolute path of a media fixture bundled under `e2e/viewer/fixtures`.
 *
 * @param name - The fixture file name (e.g. `image1.jpg`)
 */
export function mediaFixturePath(name: string): string {
	return fileURLToPath(new URL(`../../e2e/viewer/fixtures/${name}`, import.meta.url))
}

/**
 * Upload a bundled media fixture to the user's WebDAV root.
 *
 * @param request - The request context to upload with (authenticated as `user`)
 * @param user - The user owning the file
 * @param fixture - The fixture file name to read from `e2e/viewer/fixtures`
 * @param remoteName - The remote name/path relative to the user root (defaults to the fixture name)
 * @param mimeType - The content type sent with the upload
 */
export async function uploadMediaFile(
	request: APIRequestContext,
	user: User,
	fixture: string,
	remoteName: string = fixture,
	mimeType: string = 'application/octet-stream',
): Promise<void> {
	const path = remoteName.startsWith('/') ? remoteName : `/${remoteName}`
	await uploadContent(request, user, readFileSync(mediaFixturePath(fixture)), mimeType, path)
}

interface ViewerFixtures {
	/** Page object for the viewer modal. */
	viewerPage: ViewerPage
	/** Upload a bundled media fixture to the logged-in user's root. */
	uploadMedia: (fixture: string, remoteName?: string, mimeType?: string) => Promise<void>
	/** Open a file (or folder) by clicking its name link, triggering its default action. */
	openFile: (name: string) => Promise<void>
}

/**
 * The Files fixtures (logged-in random `user`, `filesListPage`) plus the viewer
 * page object and helpers to upload media and open files into the viewer.
 */
export const test = mergeTests(filesTest).extend<ViewerFixtures>({
	viewerPage: async ({ page }, use) => {
		await use(new ViewerPage(page))
	},

	uploadMedia: async ({ page, user }, use) => {
		await use((fixture, remoteName, mimeType) => uploadMediaFile(page.request, user, fixture, remoteName, mimeType))
	},

	openFile: async ({ filesListPage }, use) => {
		await use((name) => filesListPage.getRowNameLinkForFile(name).click())
	},
})

/**
 * The public-share fixtures (guest `page`, share `owner` request) plus the
 * viewer page object — for viewer behaviour on public link shares.
 */
export const publicShareTest = mergeTests(publicShareTestBase).extend<{ viewerPage: ViewerPage }>({
	viewerPage: async ({ page }, use) => {
		await use(new ViewerPage(page))
	},
})

/**
 * The user-share fixtures (`page` logged in as the recipient `user`, `owner`
 * request context to seed and share) plus the viewer page object — for viewer
 * behaviour on files shared with the current user.
 */
export const sharingTest = mergeTests(sharingTestBase).extend<{ viewerPage: ViewerPage }>({
	viewerPage: async ({ page }, use) => {
		await use(new ViewerPage(page))
	},
})

export { expect } from '../matchers.ts'
