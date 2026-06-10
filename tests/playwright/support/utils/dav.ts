/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { APIRequestContext } from '@playwright/test'
import type { User } from '@nextcloud/e2e-test-server'

/**
 * Make a MKCOL request to create a directory at the given path for the given user.
 *
 * @param request - The Playwright API request context
 * @param user - The user to create the directory for
 * @param path - The path of the directory to create (relative to user root)
 */
export async function mkdir(request: APIRequestContext, user: User, path: string): Promise<void> {
	const requesttoken = await getRequestToken(request)
	const response = await request.fetch(davUrl(user, path), {
		method: 'MKCOL',
		headers: { requesttoken },
	})
	if (!response.ok()) {
		throw new Error(`MKCOL ${path} failed with status ${response.status()}`)
	}
}

/**
 * Upload content to a DAV path and return the file ID from the response headers.
 *
 * @param request The Playwright API request context
 * @param user The user to upload as
 * @param content The content to upload
 * @param mimeType The MIME type of the content
 * @param path The path to upload to (relative to user root)
 * @return The file ID from the oc-fileid response header
 */
export async function uploadContent(
	request: APIRequestContext,
	user: User,
	content: Buffer | string,
	mimeType: string,
	path: string,
): Promise<string> {
	const requesttoken = await getRequestToken(request)
	const response = await request.fetch(davUrl(user, path), {
		method: 'PUT',
		headers: {
			'Content-Type': mimeType,
			requesttoken,
		},
		data: typeof content === 'string' ? content : content,
	})
	if (!response.ok()) {
		throw new Error(`PUT ${path} failed with status ${response.status()}`)
	}
	const fileId = response.headers()['oc-fileid']
	return fileId ? String(parseInt(fileId, 10)) : '0'
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
