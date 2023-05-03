/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import client from './DavClient.js'

import type { Response } from 'webdav'

/**
 * Mark comments older than the date timestamp as read
 *
 * @param commentsType the ressource type
 * @param ressourceId the ressource ID
 * @param date the date object
 */
export const markCommentsAsRead = (
	commentsType: string,
	ressourceId: number,
	date: Date,
): Promise<Response> => {
	const ressourcePath = ['', commentsType, ressourceId].join('/')
	const readMarker = date.toUTCString()

	return client.customRequest(ressourcePath, {
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
