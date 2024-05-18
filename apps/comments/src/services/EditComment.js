/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
