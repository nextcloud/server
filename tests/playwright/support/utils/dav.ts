/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext } from '@playwright/test'

/**
 * Make a MKCOL request to create a directory at the given path for the given user.
 *
 * @param request - The Playwright API request context (authenticated as the
 *   acting user; use an owner-scoped context to seed data for another user)
 * @param user - The user whose root the path is relative to
 * @param path - The path of the directory to create (relative to user root)
 */
export async function mkdir(request: APIRequestContext, user: User, path: string): Promise<string> {
	const requesttoken = await getRequestToken(request)
	const response = await request.fetch(davUrl(user, path), {
		method: 'MKCOL',
		headers: { requesttoken },
	})
	if (!response.ok()) {
		throw new Error(`MKCOL ${path} failed with status ${response.status()}`)
	}
	const fileId = response.headers()['oc-fileid']
	return fileId ? String(parseInt(fileId, 10)) : '0'
}

/**
 * Upload content to a DAV path and return the file ID from the response headers.
 *
 * @param request The Playwright API request context
 * @param user The user to upload as
 * @param content The content to upload
 * @param mimeType The MIME type of the content
 * @param path The path to upload to (relative to user root)
 * @param mtime Optional modification time in seconds (sets the `X-OC-MTime` header)
 * @return The file ID from the oc-fileid response header
 */
export async function uploadContent(
	request: APIRequestContext,
	user: User,
	content: Buffer | string,
	mimeType: string,
	path: string,
	mtime?: number,
): Promise<string> {
	const requesttoken = await getRequestToken(request)
	const response = await request.fetch(davUrl(user, path), {
		method: 'PUT',
		headers: {
			'Content-Type': mimeType,
			requesttoken,
			...(mtime !== undefined ? { 'X-OC-MTime': String(Math.floor(mtime)) } : {}),
		},
		data: content,
	})
	if (!response.ok()) {
		throw new Error(`PUT ${path} failed with status ${response.status()}`)
	}
	const fileId = response.headers()['oc-fileid']
	return fileId ? String(parseInt(fileId, 10)) : '0'
}

/**
 * Mark a file or folder as (un)favorite via PROPPATCH of the `oc:favorite` prop.
 *
 * @param request - The Playwright API request context
 * @param user - The user whose root the path is relative to
 * @param path - The path to (un)favorite (relative to user root)
 * @param favorite - Whether to set (default) or clear the favorite flag
 */
export async function setFavorite(request: APIRequestContext, user: User, path: string, favorite = true): Promise<void> {
	const requesttoken = await getRequestToken(request)
	const response = await request.fetch(davUrl(user, path), {
		method: 'PROPPATCH',
		headers: { requesttoken, 'Content-Type': 'application/xml' },
		data: `<?xml version="1.0"?><d:propertyupdate xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:set><d:prop><oc:favorite>${favorite ? 1 : 0}</oc:favorite></d:prop></d:set></d:propertyupdate>`,
	})
	if (!response.ok()) {
		throw new Error(`PROPPATCH (favorite) ${path} failed with status ${response.status()}`)
	}
}

/**
 * Link a file to its live-photo counterpart via PROPPATCH of the
 * `nc:metadata-files-live-photo` prop (the file id of the paired media file).
 *
 * A live photo is a still image (.jpg) paired with a short video (.mov); each
 * file stores the other's file id in this property, and the Files app treats the
 * pair as a single unit (hiding the video, and copying/moving/deleting/restoring
 * both together). Seed the link on both files for a complete pair.
 *
 * @param request - The Playwright API request context
 * @param user - The user whose root the path is relative to
 * @param path - The path of the file to annotate (relative to user root)
 * @param linkedFileId - The file id of the paired media file
 */
export async function setLivePhotoMetadata(request: APIRequestContext, user: User, path: string, linkedFileId: number): Promise<void> {
	const requesttoken = await getRequestToken(request)
	const response = await request.fetch(davUrl(user, path), {
		method: 'PROPPATCH',
		headers: { requesttoken, 'Content-Type': 'application/xml' },
		data: `<?xml version="1.0"?><d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns"><d:set><d:prop><nc:metadata-files-live-photo>${linkedFileId}</nc:metadata-files-live-photo></d:prop></d:set></d:propertyupdate>`,
	})
	if (!response.ok()) {
		throw new Error(`PROPPATCH (live-photo) ${path} failed with status ${response.status()}`)
	}
}

/**
 * Delete a file or directory at the given path for the given user.
 *
 * @param request - The Playwright API request context
 * @param user - The user to delete as
 * @param path - The path to delete (relative to user root)
 */
export async function rm(request: APIRequestContext, user: User, path: string): Promise<void> {
	const requesttoken = await getRequestToken(request)
	const response = await request.fetch(davUrl(user, path), {
		method: 'DELETE',
		headers: { requesttoken },
	})
	if (!response.ok()) {
		throw new Error(`DELETE ${path} failed with status ${response.status()}`)
	}
}

/**
 * PROPFIND a directory (Depth 1) and return the WebDAV permission letters
 * (`oc:permissions`, e.g. "SRGDNVCK") of the named child entry, or '' if absent.
 *
 * The Files UI derives action availability from a directory listing's entries
 * (e.g. the move/copy picker gates a destination on its `C` permission), so
 * polling the child entry here matches what the UI reads and lets a test wait
 * for a share-permission change to propagate. Letters of interest: `C` = can
 * create (in a folder), `D` = can delete (the entry).
 *
 * @param request - The Playwright API request context (acts as this session's user)
 * @param user - The user whose root the parent path is relative to
 * @param parentPath - The directory to list (relative to user root; '' = root)
 * @param childName - The name of the child entry whose permissions to return
 */
export async function getChildPermissions(
	request: APIRequestContext,
	user: User,
	parentPath: string,
	childName: string,
): Promise<string> {
	const requesttoken = await getRequestToken(request)
	const response = await request.fetch(davUrl(user, parentPath), {
		method: 'PROPFIND',
		headers: { requesttoken, Depth: '1' },
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:permissions/></d:prop></d:propfind>',
	})
	if (!response.ok()) {
		throw new Error(`PROPFIND ${parentPath} failed with status ${response.status()}`)
	}
	const body = await response.text()
	for (const entry of body.split(/<\/d:response>/i)) {
		const href = entry.match(/<d:href>([^<]*)<\/d:href>/)?.[1] ?? ''
		const name = decodeURIComponent(href.replace(/\/$/, '').split('/').pop() ?? '')
		if (name === childName) {
			return entry.match(/<oc:permissions>([^<]*)<\/oc:permissions>/)?.[1] ?? ''
		}
	}
	return ''
}

/**
 * Construct the DAV URL for a given user and path.
 *
 * @param user - The user the path belongs to
 * @param path - The path relative to the user's root directory
 */
function davUrl(user: User, path: string): string {
	const cleanPath = ('/' + path).replace(/\/+/g, '/')
	const encodedPath = cleanPath.split('/').map((seg) => seg ? encodeURIComponent(seg) : '').join('/')
	return `/remote.php/dav/files/${encodeURIComponent(user.userId)}${encodedPath}`
}

/**
 * Get a CSRF request token using the Playwright API request context.
 *
 * @param request - The Playwright API request context
 */
async function getRequestToken(request: APIRequestContext): Promise<string> {
	const response = await request.get('/csrftoken', { failOnStatusCode: true })
	return (await response.json()).token
}
