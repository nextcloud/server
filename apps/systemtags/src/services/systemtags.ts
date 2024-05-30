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
import type { TagWithId } from '../types'

import { Folder, type ContentsWithRoot, Permission, getDavNameSpaces, getDavProperties } from '@nextcloud/files'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

import { fetchTags } from './api'
import { getClient } from '../../../files/src/services/WebdavClient'
import { resultToNode } from '../../../files/src/services/Files'

const formatReportPayload = (tagId: number) => `<?xml version="1.0"?>
<oc:filter-files ${getDavNameSpaces()}>
	<d:prop>
		${getDavProperties()}
	</d:prop>
    <oc:filter-rules>
        <oc:systemtag>${tagId}</oc:systemtag>
    </oc:filter-rules>
</oc:filter-files>`

const tagToNode = function(tag: TagWithId): Folder {
	return new Folder({
		id: tag.id,
		source: generateRemoteUrl('dav/systemtags/' + tag.id),
		owner: getCurrentUser()?.uid as string,
		root: '/systemtags',
		permissions: Permission.READ,
		attributes: {
			...tag,
			'is-tag': true,
		},
	})
}

export const getContents = async (path = '/'): Promise<ContentsWithRoot> => {
	// List tags in the root
	const tagsCache = (await fetchTags()).filter(tag => tag.userVisible) as TagWithId[]

	if (path === '/') {
		return {
			folder: new Folder({
				id: 0,
				source: generateRemoteUrl('dav/systemtags'),
				owner: getCurrentUser()?.uid as string,
				root: '/systemtags',
				permissions: Permission.NONE,
			}),
			contents: tagsCache.map(tagToNode),
		}
	}

	const tagId = parseInt(path.replace('/', ''), 10)
	const tag = tagsCache.find(tag => tag.id === tagId)

	if (!tag) {
		throw new Error('Tag not found')
	}

	const folder = tagToNode(tag)
	const contentsResponse = await getClient().getDirectoryContents('/', {
		details: true,
		// Only filter favorites if we're at the root
		data: formatReportPayload(tagId),
		headers: {
			// Patched in WebdavClient.ts
			method: 'REPORT',
		},
	}) as ResponseDataDetailed<FileStat[]>

	return {
		folder,
		contents: contentsResponse.data.map(resultToNode),
	}

}
