/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* eslint-disable jsdoc/require-jsdoc */

import type { IFile } from '@nextcloud/files'
import type { ResponseDataDetailed, WebDAVClient } from 'webdav'

import { getClient } from '@nextcloud/files/dav'

// Manually load a WebDAV media from its filename, then expose the received Blob as an object URL.
// This is needed for E2EE files that will error when loading them directly from the HTML element's src attribute.
// Can be removed if we ever move the E2EE proxy to a service worker.
export async function preloadMedia(file: IFile): Promise<string> {
	const client = getClient() as WebDAVClient
	const response = await client.getFileContents(file.source, { details: true }) as ResponseDataDetailed<ArrayBuffer>
	return URL.createObjectURL(new Blob([response.data], { type: response.headers['content-type'] }))
}
