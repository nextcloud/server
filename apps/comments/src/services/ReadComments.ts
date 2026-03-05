/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Response } from 'webdav'

import client from './DavClient.js'

/**
 * Mark comments older than the date timestamp as read
 *
 * @param resourceType the resource type
 * @param resourceId the resource ID
 * @param date the date object
 */
export function markCommentsAsRead(
	resourceType: string,
	resourceId: number,
	date: Date,
): Promise<Response> {
	const resourcePath = ['', resourceType, resourceId].join('/')
	const readMarker = date.toUTCString()

	return client.customRequest(resourcePath, {
		method: 'PROPPATCH',
		data: `<?xml version="1.0"?>
			<d:propertyupdate
				xmlns:d="DAV:"
				xmlns:oc="http://owncloud.org/ns">
			<d:set>
				<d:prop>
					<oc:readMarker>${readMarker}</oc:readMarker>
				</d:prop>
			</d:set>
			</d:propertyupdate>`,
	})
}
