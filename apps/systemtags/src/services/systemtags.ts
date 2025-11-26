/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ContentsWithRoot } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'
import type { TagWithId } from '../types.ts'

import { getCurrentUser } from '@nextcloud/auth'
import { davGetClient, davRemoteURL, davResultToNode, davRootPath, Folder, getDavNameSpaces, getDavProperties, Permission } from '@nextcloud/files'
import { fetchTags } from './api.ts'

const rootPath = '/systemtags'

const client = davGetClient()
const resultToNode = (node: FileStat) => davResultToNode(node)

/**
 *
 * @param tagId
 */
function formatReportPayload(tagId: number) {
	return `<?xml version="1.0"?>
<oc:filter-files ${getDavNameSpaces()}>
	<d:prop>
		${getDavProperties()}
	</d:prop>
	<oc:filter-rules>
		<oc:systemtag>${tagId}</oc:systemtag>
	</oc:filter-rules>
</oc:filter-files>`
}

/**
 *
 * @param tag
 */
function tagToNode(tag: TagWithId): Folder {
	return new Folder({
		id: tag.id,
		source: `${davRemoteURL}${rootPath}/${tag.id}`,
		owner: String(getCurrentUser()?.uid ?? 'anonymous'),
		root: rootPath,
		displayname: tag.displayName,
		permissions: Permission.READ,
		attributes: {
			...tag,
			'is-tag': true,
		},
	})
}

/**
 *
 * @param path
 */
export async function getContents(path = '/'): Promise<ContentsWithRoot> {
	// List tags in the root
	const tagsCache = (await fetchTags()).filter((tag) => tag.userVisible) as TagWithId[]

	if (path === '/') {
		return {
			folder: new Folder({
				id: 0,
				source: `${davRemoteURL}${rootPath}`,
				owner: getCurrentUser()?.uid as string,
				root: rootPath,
				permissions: Permission.NONE,
			}),
			contents: tagsCache.map(tagToNode),
		}
	}

	const tagId = parseInt(path.split('/', 2)[1])
	const tag = tagsCache.find((tag) => tag.id === tagId)

	if (!tag) {
		throw new Error('Tag not found')
	}

	const folder = tagToNode(tag)
	const contentsResponse = await client.getDirectoryContents(davRootPath, {
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
