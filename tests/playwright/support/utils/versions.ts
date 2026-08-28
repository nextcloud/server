/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext } from '@playwright/test'
import type { FilesListPage } from '../sections/FilesListPage.ts'
import type { VersionsTab } from '../sections/VersionsTab.ts'

import { uploadContent } from './dav.ts'

/**
 * The three payloads seeded by {@link seedThreeVersions}, oldest ("v1") to
 * newest ("v3"). The newest is the current file content; "v1" and "v2" become
 * the two older versions.
 */
export const VERSION_CONTENTS = ['v1', 'v2', 'v3'] as const

/**
 * Seed a file with three distinct versions by uploading it three times.
 *
 * The Files versioning backend keys each stored version on the file's mtime at
 * the moment it is overwritten (`files_versions/<path>.v<mtime>`). The Cypress
 * original waited 1.1s of real time between uploads so consecutive versions got
 * distinct mtimes and survived the versioning auto-expiration — a slow and flaky
 * approach.
 *
 * Instead we set explicit, widely-spaced mtimes via the `X-OC-MTime` header: the
 * three uploads land 120s apart in the recent past. That guarantees three
 * distinct version files with no real waiting, and keeps them clear of the
 * auto-expiration tiers (which keep one version per 60s within the last hour),
 * so exactly three versions reliably survive.
 *
 * After the three uploads the versions list shows: the current file ("v3"), plus
 * the two stored versions "v2" and "v1" — three entries total, newest first.
 *
 * @param request - A request context authenticated as the file owner
 * @param user - The owner whose root `path` is relative to
 * @param path - The file path to create versions for (relative to user root)
 */
export async function seedThreeVersions(
	request: APIRequestContext,
	user: User,
	path: string,
): Promise<void> {
	const base = Math.floor(Date.now() / 1000)
	for (const [index, content] of VERSION_CONTENTS.entries()) {
		// Oldest first: v1 at base-360, v2 at base-240, v3 (current) at base-120.
		const mtime = base - (VERSION_CONTENTS.length - index) * 120
		await uploadContent(request, user, content, 'text/plain', path, mtime)
	}
}

/**
 * Open the Versions tab of the sidebar for the file at `path`.
 *
 * Triggers the file row's "Details" action to open the sidebar, then selects the
 * Versions tab (waiting for the version list to load). `path` may be a full path;
 * only its last segment (the file name) is used to find the row, so the caller
 * must already be in the containing folder.
 *
 * @param filesList - The files list page object
 * @param versionsTab - The versions tab page object
 * @param path - The file path (or bare name) whose versions to open
 */
export async function openVersionsPanel(
	filesList: FilesListPage,
	versionsTab: VersionsTab,
	path: string,
): Promise<void> {
	const name = path.split('/').filter(Boolean).pop() ?? path
	await filesList.triggerActionForFile(name, 'details')
	await versionsTab.open()
}
