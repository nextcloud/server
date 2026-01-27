/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ContentsWithRoot } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'
import type { TagWithId } from '../types.ts'

import { getCurrentUser } from '@nextcloud/auth'
import { Folder, Permission } from '@nextcloud/files'
import { getClient, getDavNameSpaces, getDavProperties, getRemoteURL, getRootPath, resultToNode } from '@nextcloud/files/dav'
import { fetchTags } from './api.ts'

const rootPath = '/systemtags'

const client = getClient()

/**
 * Format the REPORT payload to filter files by tag
 *
 * @param tagId - The tag ID
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
 * Convert a tag to a Folder node
 *
 * @param tag - The tag
 */
function tagToNode(tag: TagWithId): Folder {
	return new Folder({
		id: tag.id,
		source: `${getRemoteURL()}${rootPath}/${tag.id}`,
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
 * Get the contents of a folder or tag
 *
 * @param path - The path to the folder or tag
 */
export async function getContents(path = '/'): Promise<ContentsWithRoot> {
	// List tags in the root
	const tagsCache = (await fetchTags()).filter((tag) => tag.userVisible) as TagWithId[]

	if (path === '/') {
		return {
			folder: new Folder({
				id: 0,
				source: `${getRemoteURL()}${rootPath}`,
				owner: getCurrentUser()?.uid as string,
				root: rootPath,
				permissions: Permission.NONE,
			}),
			contents: tagsCache.map(tagToNode),
		}
	}

	const tagIdStr = path.split('/', 2)[1]
	if (!tagIdStr || isNaN(parseInt(tagIdStr))) {
		throw new Error('Invalid tag ID')
	}

	const tagId = parseInt(tagIdStr)
	const tag = tagsCache.find((tag) => tag.id === tagId)
	if (!tag) {
		throw new Error('Tag not found')
	}

	const folder = tagToNode(tag)
	const contentsResponse = await client.getDirectoryContents(getRootPath(), {
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
		contents: contentsResponse.data.map((stat) => resultToNode(stat)),
	}
}
