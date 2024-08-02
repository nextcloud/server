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
import type { FileStat, ResponseDataDetailed } from 'webdav'
import type { ContentsWithRoot } from '@nextcloud/files'

import { File, Folder, davResultToNode, getDavNameSpaces, getDavProperties } from '@nextcloud/files'
import { client, rootPath } from './client'
import { generateUrl } from '@nextcloud/router'

const data = `<?xml version="1.0"?>
<d:propfind ${getDavNameSpaces()}>
	<d:prop>
		<nc:trashbin-deletion-time />
		<nc:trashbin-original-location />
		<nc:trashbin-title />
		${getDavProperties()}
	</d:prop>
</d:propfind>`

const resultToNode = (stat: FileStat): File | Folder => {
	const node = davResultToNode(stat, rootPath)
	node.attributes.previewUrl = generateUrl('/apps/files_trashbin/preview?fileId={fileid}&x=32&y=32', { fileid: node.fileid })
	return node
}

export const getContents = async (path = '/'): Promise<ContentsWithRoot> => {
	const contentsResponse = await client.getDirectoryContents(`${rootPath}${path}`, {
		details: true,
		data,
		includeSelf: true,
	}) as ResponseDataDetailed<FileStat[]>

	const contents = contentsResponse.data.map(resultToNode)
	const [folder] = contents.splice(contents.findIndex((node) => node.dirname === path), 1)

	return {
		folder: folder as Folder,
		contents,
	}
}
