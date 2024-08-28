/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import client from './DavClient.js'

/**
 * Delete a comment
 *
 * @param {string} resourceType the resource type
 * @param {number} resourceId the resource ID
 * @param {number} commentId the comment iD
 */
export default async function(resourceType, resourceId, commentId) {
	const commentPath = ['', resourceType, resourceId, commentId].join('/')

	// Fetch newly created comment data
	await client.deleteFile(commentPath)
}
