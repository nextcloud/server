/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Permission } from '@nextcloud/files'
import type { APIRequestContext } from '@playwright/test'

// we cannot import the enum directly from the files app.
// It references the window object and causes errors when imported in a node context,
// so we re-declare the relevant values here. The type assertion ensures we stay in sync.
export const SharePermission = {
	READ: 1,
	UPDATE: 2,
	CREATE: 4,
	DELETE: 8,
	SHARE: 16,
} as const satisfies Partial<typeof Permission>

/** All permissions a user share can grant. */
export const ALL_PERMISSIONS = SharePermission.READ
	| SharePermission.UPDATE
	| SharePermission.CREATE
	| SharePermission.DELETE
	| SharePermission.SHARE

/** OCS Share API share types (subset we seed in tests). */
export const ShareType = {
	USER: 0,
	GROUP: 1,
} as const

/**
 * Create a share via the OCS Share API. Seeding shares through the API avoids
 * driving the (flaky) share-editor sidebar.
 *
 * @param request - A request context authenticated as the share owner (e.g. the
 *   `ownerRequest` fixture)
 * @param path - The path to share, relative to the owner's root
 * @param shareWith - The recipient: a user id for a user share, a group id for a group share
 * @param permissions - The permission bitmask to grant (defaults to all)
 * @param shareType - The OCS share type (defaults to a user share)
 */
export async function createShare(
	request: APIRequestContext,
	path: string,
	shareWith: string,
	permissions: number = ALL_PERMISSIONS,
	shareType: number = ShareType.USER,
): Promise<void> {
	const response = await request.post('/ocs/v2.php/apps/files_sharing/api/v1/shares?format=json', {
		headers: { 'OCS-APIRequest': 'true' },
		form: {
			path,
			shareType,
			shareWith,
			permissions,
		},
	})
	// OCS returns HTTP 200 even on failure; the real status lives in ocs.meta
	const { ocs } = await response.json()
	if (ocs?.meta?.statuscode !== 200) {
		throw new Error(`Creating share for ${path} failed: ${ocs?.meta?.statuscode} ${ocs?.meta?.message}`)
	}

	// A new share ignores the create-time permissions and always starts with the
	// full set, so restricted permissions must be applied with a follow-up update.
	if (permissions !== ALL_PERMISSIONS) {
		const update = await request.put(`/ocs/v2.php/apps/files_sharing/api/v1/shares/${ocs.data.id}?format=json`, {
			headers: { 'OCS-APIRequest': 'true' },
			form: { permissions },
		})
		const updateMeta = (await update.json()).ocs?.meta
		if (updateMeta?.statuscode !== 200) {
			throw new Error(`Updating share ${ocs.data.id} failed: ${updateMeta?.statuscode} ${updateMeta?.message}`)
		}
	}
}
