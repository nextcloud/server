/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { parseXML, type DAVResult, type FileStat, type ResponseDataDetailed } from 'webdav'

// https://github.com/perry-mitchell/webdav-client/issues/339
import { processResponsePayload } from 'webdav/dist/node/response.js'
import { prepareFileFromProps } from 'webdav/dist/node/tools/dav.js'
import client from './DavClient.js'

export const DEFAULT_LIMIT = 20

/**
 * Retrieve the comments list
 *
 * @param {object} data destructuring object
 * @param {string} data.resourceType the resource type
 * @param {number} data.resourceId the resource ID
 * @param {object} [options] optional options for axios
 * @param {number} [options.offset] the pagination offset
 * @param {number} [options.limit] the pagination limit, defaults to 20
 * @param {Date} [options.datetime] optional date to query
 * @return {{data: object[]}} the comments list
 */
export const getComments = async function({ resourceType, resourceId }, options: { offset: number, limit?: number, datetime?: Date }) {
	const resourcePath = ['', resourceType, resourceId].join('/')
	const datetime = options.datetime ? `<oc:datetime>${options.datetime.toISOString()}</oc:datetime>` : ''
	const response = await client.customRequest(resourcePath, Object.assign({
		method: 'REPORT',
		data: `<?xml version="1.0"?>
			<oc:filter-comments
				xmlns:d="DAV:"
				xmlns:oc="http://owncloud.org/ns"
				xmlns:nc="http://nextcloud.org/ns"
				xmlns:ocs="http://open-collaboration-services.org/ns">
				<oc:limit>${options.limit ?? DEFAULT_LIMIT}</oc:limit>
				<oc:offset>${options.offset || 0}</oc:offset>
				${datetime}
			</oc:filter-comments>`,
	}, options))

	const responseData = await response.text()
	const result = await parseXML(responseData)
	const stat = getDirectoryFiles(result, true)
	return processResponsePayload(response, stat, true) as ResponseDataDetailed<FileStat[]>
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
		const props = item.propstat!.prop!

		return prepareFileFromProps(props, props.id!.toString(), isDetailed)
	})
}
