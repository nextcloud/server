/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import client from './DavClient.js'

/**
 * Edit an existing comment
 *
 * @param {string} resourceType the resource type
 * @param {number} resourceId the resource ID
 * @param {number} commentId the comment iD
 * @param {string} message the message content
 */
export default async function(resourceType, resourceId, commentId, message) {
	const commentPath = ['', resourceType, resourceId, commentId].join('/')

	return await client.customRequest(commentPath, Object.assign({
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
	}))
}
