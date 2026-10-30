/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import client from './DavClient.ts'

/**
 * Delete a comment
 *
 * @param resourceType the resource type
 * @param resourceId the resource ID
 * @param commentId the comment iD
 */
export default async function(resourceType: string, resourceId: number, commentId: number) {
	const commentPath = ['', resourceType, resourceId, commentId].join('/')

	// Fetch newly created comment data
	await client.deleteFile(commentPath)
}
