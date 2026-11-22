/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import client from './DavClient.ts'

/**
 * Edit an existing comment
 *
 * @param resourceType the resource type
 * @param resourceId the resource ID
 * @param commentId the comment iD
 * @param message the message content
 */
export default async function(resourceType: string, resourceId: number, commentId: number, message: string) {
	const commentPath = ['', resourceType, resourceId, commentId].join('/')

	return await client.customRequest(commentPath, {
		method: 'PROPPATCH',
		data: `<?xml version="1.0"?>
			<d:propertyupdate
				xmlns:d="DAV:"
				xmlns:oc="http://owncloud.org/ns">
			<d:set>
				<d:prop>
					<oc:message>${message}</oc:message>
				</d:prop>
			</d:set>
			</d:propertyupdate>`,
	})
}
