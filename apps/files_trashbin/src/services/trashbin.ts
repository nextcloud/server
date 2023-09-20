/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
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
import { File, Folder, davParsePermissions, type ContentsWithRoot } from '@nextcloud/files'
import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { getDavNameSpaces, getDavProperties } from '../../../files/src/services/DavProperties'

import client, { rootPath } from './client'

const data = `<?xml version="1.0"?>
<d:propfind ${getDavNameSpaces()}>
	<d:prop>
		<nc:trashbin-filename />
		<nc:trashbin-deletion-time />
		<nc:trashbin-original-location />
		<nc:trashbin-title />
		${getDavProperties()}
	</d:prop>
</d:propfind>`

const resultToNode = function(node: FileStat): File | Folder {
	const permissions = davParsePermissions(node.props?.permissions)
	const owner = getCurrentUser()?.uid as string
	const previewUrl = generateUrl('/apps/files_trashbin/preview?fileId={fileid}&x=32&y=32', node.props)

	const nodeData = {
		id: node.props?.fileid as number || 0,
		source: generateRemoteUrl('dav' + rootPath + node.filename),
		// do not show the mtime column
		// mtime: new Date(node.lastmod),
		mime: node.mime as string,
		size: node.props?.size as number || 0,
		permissions,
		owner,
		root: rootPath,
		attributes: {
			...node,
			...node.props,
			// Override displayed name on the list
			displayName: node.props?.['trashbin-filename'],
			previewUrl,
		},
	}

	delete nodeData.attributes.props

	return node.type === 'file'
		? new File(nodeData)
		: new Folder(nodeData)
}

export const getContents = async (path = '/'): Promise<ContentsWithRoot> => {
	// TODO: use only one request when webdav-client supports it
	// @see https://github.com/perry-mitchell/webdav-client/pull/334
	const rootResponse = await client.stat(path, {
		details: true,
		data,
	}) as ResponseDataDetailed<FileStat>

	const contentsResponse = await client.getDirectoryContents(path, {
		details: true,
		data,
	}) as ResponseDataDetailed<FileStat[]>

	return {
		folder: resultToNode(rootResponse.data) as Folder,
		contents: contentsResponse.data.map(resultToNode),
	}
}
