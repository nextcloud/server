/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ContentsWithRoot } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'
import type { TagWithId } from '../types'

import { Folder, Permission, getDavNameSpaces, getDavProperties, davGetClient, davResultToNode } from '@nextcloud/files'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

import { fetchTags } from './api'

const client = davGetClient()
const resultToNode = (node: FileStat) => davResultToNode(node)

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
	const contentsResponse = await client.getDirectoryContents('/', {
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
