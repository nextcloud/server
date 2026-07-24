/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { Permission } from '@nextcloud/files'
import type { APIRequestContext } from '@playwright/test'

import { expect } from '@playwright/test'
import { getChildPermissions } from './dav.ts'

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
 * The share attribute that forbids downloading (and thus opens the file
 * view-only). It mirrors the "allow download" toggle in the share editor and is
 * what the versions sidebar reads to decide whether a "Download version" action
 * is offered. Pass it as the `attributes` option to {@link createShare}.
 */
export const DOWNLOAD_DISABLED_ATTRIBUTE = [
	{ scope: 'permissions', key: 'download', value: false },
] as const

/** Options for {@link createShare}. */
export interface CreateShareOptions {
	/** The permission bitmask to grant (defaults to all). */
	permissions?: number
	/** The OCS share type (defaults to a user share). */
	shareType?: number
	/**
	 * Share attributes (e.g. {@link DOWNLOAD_DISABLED_ATTRIBUTE}). Serialized to
	 * the OCS `attributes` field.
	 */
	attributes?: readonly { scope: string, key: string, value: boolean }[]
}

/**
 * Create a share via the OCS Share API. Seeding shares through the API avoids
 * driving the (flaky) share-editor sidebar.
 *
 * @param request - A request context authenticated as the share owner (e.g. the
 *   `ownerRequest` fixture)
 * @param path - The path to share, relative to the owner's root
 * @param shareWith - The recipient: a user id for a user share, a group id for a group share
 * @param options - Permission bitmask, share type and/or share attributes
 */
export async function createShare(
	request: APIRequestContext,
	path: string,
	shareWith: string,
	options: CreateShareOptions = {},
): Promise<void> {
	const {
		permissions = ALL_PERMISSIONS,
		shareType = ShareType.USER,
		attributes,
	} = options

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

	// A new share ignores the create-time permissions/attributes and always
	// starts with the full set, so anything restricted must be applied with a
	// follow-up update. Only send `permissions` when actually restricting: the
	// server clamps the natural full set to what the node allows (e.g. a file
	// share cannot carry DELETE/CREATE), so forcing ALL_PERMISSIONS would be
	// rejected on a file.
	const restrictsPermissions = permissions !== ALL_PERMISSIONS
	if (restrictsPermissions || attributes !== undefined) {
		const form: Record<string, string | number> = {}
		if (restrictsPermissions) {
			form.permissions = permissions
		}
		if (attributes !== undefined) {
			form.attributes = JSON.stringify(attributes)
		}
		const update = await request.put(`/ocs/v2.php/apps/files_sharing/api/v1/shares/${ocs.data.id}?format=json`, {
			headers: { 'OCS-APIRequest': 'true' },
			form,
		})
		const updateMeta = (await update.json()).ocs?.meta
		if (updateMeta?.statuscode !== 200) {
			throw new Error(`Updating share ${ocs.data.id} failed: ${updateMeta?.statuscode} ${updateMeta?.message}`)
		}
	}
}

/**
 * A share mounts into the recipient's tree asynchronously, and permission changes
 * propagate after that. Poll the recipient's directory listing for the entry's
 * `oc:permissions` (the same source the Files UI reads) until it exists and
 * satisfies `ready`, before driving the UI. Transient errors (mount not there
 * yet) are swallowed so the poll keeps waiting.
 *
 * @param request - A request context authenticated as the recipient
 * @param user - The recipient user
 * @param parentPath - The directory to list (relative to recipient root; '' = root)
 * @param childName - The shared entry to wait for
 * @param ready - Optional predicate on the entry's `oc:permissions` letters
 */
export async function waitForShare(
	request: APIRequestContext,
	user: User,
	parentPath: string,
	childName: string,
	ready: (permissions: string) => boolean = () => true,
): Promise<void> {
	await expect.poll(async () => {
		try {
			const permissions = await getChildPermissions(request, user, parentPath, childName)
			return permissions !== '' && ready(permissions)
		} catch {
			return false
		}
	}, { message: `share ${parentPath}/${childName} did not propagate to ${user.userId}`, timeout: 20_000 }).toBe(true)
}
