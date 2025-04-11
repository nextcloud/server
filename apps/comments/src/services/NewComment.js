/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import { getRootPath } from '../utils/davUtils.js'
import { decodeHtmlEntities } from '../utils/decodeHtmlEntities.js'
import axios from '@nextcloud/axios'
import client from './DavClient.js'

/**
 * Retrieve the comments list
 *
 * @param {string} resourceType the resource type
 * @param {number} resourceId the resource ID
 * @param {string} message the message
 * @return {object} the new comment
 */
export default async function(resourceType, resourceId, message) {
	const resourcePath = ['', resourceType, resourceId].join('/')

	const response = await axios.post(getRootPath() + resourcePath, {
		actorDisplayName: getCurrentUser().displayName,
		actorId: getCurrentUser().uid,
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
