/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { getRootPath } from '../utils/davUtils.ts'
import { decodeHtmlEntities } from '../utils/decodeHtmlEntities.ts'
import client from './DavClient.ts'

/**
 * Retrieve the comments list
 *
 * @param resourceType the resource type
 * @param resourceId the resource ID
 * @param message the message
 * @return The new comment
 */
export default async function(resourceType: string, resourceId: number, message: string) {
	const resourcePath = ['', resourceType, resourceId].join('/')

	const response = await axios.post(getRootPath() + resourcePath, {
		actorDisplayName: getCurrentUser()!.displayName,
		actorId: getCurrentUser()!.uid,
		actorType: 'users',
		creationDateTime: (new Date()).toUTCString(),
		message,
		objectType: resourceType,
		verb: 'comment',
	})

	// Retrieve comment id from resource location
	const commentId = parseInt(response.headers['content-location'].split('/').pop())
	const commentPath = resourcePath + '/' + commentId

	// Fetch newly created comment data
	const comment = await client.stat(commentPath, {
		details: true,
	})

	const props = comment.data.props
	// Decode twice to handle potentially double-encoded entities
	// FIXME Remove this once https://github.com/nextcloud/server/issues/29306
	// is resolved
	props.actorDisplayName = decodeHtmlEntities(props.actorDisplayName, 2)
	props.message = decodeHtmlEntities(props.message, 2)

	return comment.data
}
