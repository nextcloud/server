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

import { parseXML, type DAVResult, type FileStat } from 'webdav'

// https://github.com/perry-mitchell/webdav-client/issues/339
import { processResponsePayload } from '../../../../node_modules/webdav/dist/node/response.js'
import { prepareFileFromProps } from '../../../../node_modules/webdav/dist/node/tools/dav.js'
import client from './DavClient.js'

export const DEFAULT_LIMIT = 20

/**
 * Retrieve the comments list
 *
 * @param {object} data destructuring object
 * @param {string} data.commentsType the ressource type
 * @param {number} data.ressourceId the ressource ID
 * @param {object} [options] optional options for axios
 * @param {number} [options.offset] the pagination offset
 * @return {object[]} the comments list
 */
export const getComments = async function({ commentsType, ressourceId }, options: { offset: number }) {
	const ressourcePath = ['', commentsType, ressourceId].join('/')

	const response = await client.customRequest(ressourcePath, Object.assign({
		method: 'REPORT',
		data: `<?xml version="1.0"?>
			<oc:filter-comments
				xmlns:d="DAV:"
				xmlns:oc="http://owncloud.org/ns"
				xmlns:nc="http://nextcloud.org/ns"
				xmlns:ocs="http://open-collaboration-services.org/ns">
				<oc:limit>${DEFAULT_LIMIT}</oc:limit>
				<oc:offset>${options.offset || 0}</oc:offset>
			</oc:filter-comments>`,
	}, options))

	const responseData = await response.text()
	const result = await parseXML(responseData)
	const stat = getDirectoryFiles(result, true)
	return processResponsePayload(response, stat, true)
}

// https://github.com/perry-mitchell/webdav-client/blob/8d9694613c978ce7404e26a401c39a41f125f87f/source/operations/directoryContents.ts
const getDirectoryFiles = function(
	result: DAVResult,
	isDetailed = false,
): Array<FileStat> {
	// Extract the response items (directory contents)
	const {
		multistatus: { response: responseItems },
	} = result

	// Map all items to a consistent output structure (results)
	return responseItems.map(item => {
		// Each item should contain a stat object
		const {
			propstat: { prop: props },
		} = item

		return prepareFileFromProps(props, props.id.toString(), isDetailed)
	})
}
