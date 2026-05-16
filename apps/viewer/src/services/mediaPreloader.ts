/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ResponseDataDetailed } from 'webdav'

import { getClient, getRootPath } from '@nextcloud/files/dav'

/**
 * Manually load a WebDAV media from its filename, then expose the received Blob as an object URL.
 * This is needed for E2EE files that will error when loading them directly from the HTML element's src attribute.
 * Can be removed if we ever move the E2EE proxy to a service worker.
 *
 * @param filename - The file's path
 */
export async function preloadMedia(filename: string): Promise<string> {
	const client = getClient()
	const response = await client.getFileContents(`${getRootPath()}${filename}`, { details: true }) as ResponseDataDetailed<ArrayBuffer>
	return URL.createObjectURL(new Blob([response.data], { type: response.headers['content-type'] }))
}
