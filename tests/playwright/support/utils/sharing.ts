/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { Permission } from '@nextcloud/files'
import type { APIRequestContext } from '@playwright/test'
import type { FilesListPage } from '../sections/FilesListPage.ts'
import type { SharingTab } from '../sections/SharingTab.ts'

import { expect } from '@playwright/test'
import { getChildPermissions, mkdir, uploadContent } from './dav.ts'

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
	LINK: 3,
	EMAIL: 4,
} as const

/**
 * The permission bundles the share editor offers as radio buttons ("View only",
 * "Allow upload and editing", "File request"), mirroring `BUNDLED_PERMISSIONS`
 * in `apps/files_sharing/src/lib/SharePermissionsToolBox.js`. Seeding a share
 * with one of these is equivalent to picking that bundle in the UI.
 */
export const BUNDLED_PERMISSIONS = {
	READ_ONLY: SharePermission.READ,
	UPLOAD_AND_UPDATE: SharePermission.READ | SharePermission.UPDATE | SharePermission.CREATE | SharePermission.DELETE,
	FILE_DROP: SharePermission.CREATE,
} as const

/**
 * The share attribute behind the editor's "Show files in grid view" toggle: it
 * makes a public share open in grid instead of list view.
 */
export const GRID_VIEW_ATTRIBUTE = [
	{ scope: 'config', key: 'grid_view', value: true },
] as const

/**
 * The share attribute that forbids downloading (and thus opens the file
 * view-only). It mirrors the "allow download" toggle in the share editor and is
 * what the versions sidebar reads to decide whether a "Download version" action
 * is offered. Pass it as the `attributes` option to {@link createShare}.
 */
export const DOWNLOAD_DISABLED_ATTRIBUTE = [
	{ scope: 'permissions', key: 'download', value: false },
] as const

/** A share as returned by the OCS Share API. */
export interface Share {
	/** The share id, e.g. to pass to {@link updateShare}. */
	id: string
	/** The public share URL — link and email shares only. */
	url: string
	/** The public share token — link and email shares only. */
	token: string
}

/** Share properties an update can set. */
export interface UpdateShareOptions {
	/** The permission bitmask, e.g. one of {@link BUNDLED_PERMISSIONS}. */
	permissions?: number
	/**
	 * Share attributes (e.g. {@link DOWNLOAD_DISABLED_ATTRIBUTE} or
	 * {@link GRID_VIEW_ATTRIBUTE}). Serialized to the OCS `attributes` field.
	 */
	attributes?: readonly { scope: string, key: string, value: boolean }[]
	/** The note shown to the recipient. */
	note?: string
	/** The share label (public shares only). */
	label?: string
	/** Whether the public share hides its download options. */
	hideDownload?: boolean
	/** The expiration date as `YYYY-MM-DD`. */
	expireDate?: string
	/** The public share password. */
	password?: string
}

/** Options for {@link createShare}. */
export interface CreateShareOptions extends UpdateShareOptions {
	/** The OCS share type (defaults to a user share). */
	shareType?: number
}

/**
 * POST a share and return it. OCS answers HTTP 200 even on failure, so the real
 * status is read from `ocs.meta`.
 */
async function postShare(
	request: APIRequestContext,
	path: string,
	shareType: number,
	shareWith?: string,
): Promise<Share> {
	const response = await request.post('/ocs/v2.php/apps/files_sharing/api/v1/shares?format=json', {
		headers: { 'OCS-APIRequest': 'true' },
		form: {
			path,
			shareType,
			...(shareWith !== undefined ? { shareWith } : {}),
		},
	})
	const { ocs } = await response.json()
	if (ocs?.meta?.statuscode !== 200) {
		throw new Error(`Creating share for ${path} failed: ${ocs?.meta?.statuscode} ${ocs?.meta?.message}`)
	}
	return { id: String(ocs.data.id), url: ocs.data.url, token: ocs.data.token }
}

/**
 * Apply share properties via the OCS Share API. A freshly created share always
 * starts with the full permission set, so anything restricted has to be applied
 * as a follow-up update — which is also how the share editor behaves.
 *
 * @param request - A request context authenticated as the share owner
 * @param id - The share id
 * @param options - The properties to set
 */
export async function updateShare(
	request: APIRequestContext,
	id: string,
	options: UpdateShareOptions,
): Promise<void> {
	const form: Record<string, string | number> = {}
	if (options.permissions !== undefined) {
		form.permissions = options.permissions
	}
	if (options.attributes !== undefined) {
		form.attributes = JSON.stringify(options.attributes)
	}
	if (options.note !== undefined) {
		form.note = options.note
	}
	if (options.label !== undefined) {
		form.label = options.label
	}
	if (options.hideDownload !== undefined) {
		form.hideDownload = options.hideDownload ? 'true' : 'false'
	}
	if (options.expireDate !== undefined) {
		form.expireDate = options.expireDate
	}
	if (options.password !== undefined) {
		form.password = options.password
	}

	const response = await request.put(`/ocs/v2.php/apps/files_sharing/api/v1/shares/${id}?format=json`, {
		headers: { 'OCS-APIRequest': 'true' },
		form,
	})
	const meta = (await response.json()).ocs?.meta
	if (meta?.statuscode !== 200) {
		throw new Error(`Updating share ${id} failed: ${meta?.statuscode} ${meta?.message}`)
	}
}

/**
 * Create a share via the OCS Share API. Seeding shares through the API avoids
 * driving the (flaky) share-editor sidebar.
 *
 * @param request - A request context authenticated as the share owner (e.g. the
 *   `ownerRequest` fixture)
 * @param path - The path to share, relative to the owner's root
 * @param shareWith - The recipient: a user id for a user share, a group id for a group share
 * @param options - Permission bitmask, share type and/or further share properties
 */
export async function createShare(
	request: APIRequestContext,
	path: string,
	shareWith: string,
	options: CreateShareOptions = {},
): Promise<Share> {
	const { shareType = ShareType.USER, permissions = ALL_PERMISSIONS, ...rest } = options
	const share = await postShare(request, path, shareType, shareWith)

	// Only send `permissions` when actually restricting: the server clamps the
	// natural full set to what the node allows (e.g. a file share cannot carry
	// DELETE/CREATE), so forcing ALL_PERMISSIONS would be rejected on a file.
	const update: UpdateShareOptions = { ...rest }
	if (permissions !== ALL_PERMISSIONS) {
		update.permissions = permissions
	}
	if (Object.keys(update).length > 0) {
		await updateShare(request, share.id, update)
	}
	return share
}

/**
 * Create a public link share via the OCS Share API and return it (its `url` is
 * what a guest visits). Pass `permissions` to seed one of the editor's
 * {@link BUNDLED_PERMISSIONS} bundles — a link share is created read-only, so
 * anything else needs the follow-up update this helper performs.
 *
 * @param request - A request context authenticated as the share owner
 * @param path - The path to share, relative to the owner's root
 * @param options - Share properties to apply after creation
 */
export async function createLinkShare(
	request: APIRequestContext,
	path: string,
	options: UpdateShareOptions = {},
): Promise<Share> {
	const share = await postShare(request, path, ShareType.LINK)
	if (Object.keys(options).length > 0) {
		await updateShare(request, share.id, options)
	}
	return share
}

/**
 * Open the share editor for an entry: trigger the row's "Details" action to open
 * the sidebar, then select its Sharing tab.
 *
 * @param filesListPage - The files list page object
 * @param sharingTab - The sharing tab page object
 * @param fileName - The name of the row whose shares to edit
 */
export async function openSharingPanel(
	filesListPage: FilesListPage,
	sharingTab: SharingTab,
	fileName: string,
): Promise<void> {
	await filesListPage.triggerActionForFile(fileName, 'details')
	await sharingTab.open()
}

/**
 * Seed the folder tree the public-share specs share out:
 *
 * ```
 * <name>/foo.txt
 * <name>/subfolder/bar.txt
 * ```
 *
 * @param request - A request context authenticated as the owner
 * @param user - The owner whose root the tree is created in
 * @param name - The name of the folder to share
 */
export async function seedSharedFolder(request: APIRequestContext, user: User, name: string): Promise<void> {
	await mkdir(request, user, `/${name}`)
	await mkdir(request, user, `/${name}/subfolder`)
	await uploadContent(request, user, '<content>foo</content>', 'text/plain', `/${name}/foo.txt`)
	await uploadContent(request, user, '<content>bar</content>', 'text/plain', `/${name}/subfolder/bar.txt`)
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
