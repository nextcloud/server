/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { INode } from '@nextcloud/files'
import type { ResponseDataDetailed, SearchResult } from 'webdav'

import { getCurrentUser } from '@nextcloud/auth'
import { defaultRootPath, getDavNameSpaces, getDavProperties, resultToNode } from '@nextcloud/files/dav'
import { getBaseUrl } from '@nextcloud/router'
import { client } from './WebdavClient.ts'

export interface SearchNodesOptions {
	signal?: AbortSignal
}

/**
 * Search for nodes matching the given query.
 *
 * @param query - Search query
 * @param options - Options
 * @param options.signal - Abort signal for the request
 */
export async function searchNodes(query: string, { signal }: SearchNodesOptions): Promise<INode[]> {
	const user = getCurrentUser()
	// the search plugin only works for user roots
	if (!user) {
		return []
	}

	const { data } = await client.search('/', {
		details: true,
		signal,
		data: `
<d:searchrequest ${getDavNameSpaces()}>
	 <d:basicsearch>
		 <d:select>
			 <d:prop>
			 ${getDavProperties()}
			 </d:prop>
		 </d:select>
		 <d:from>
			 <d:scope>
				 <d:href>/files/${user.uid}</d:href>
				 <d:depth>infinity</d:depth>
			 </d:scope>
		 </d:from>
		 <d:where>
			 <d:like>
				 <d:prop>
					 <d:displayname/>
				 </d:prop>
				 <d:literal>%${query.replace('%', '')}%</d:literal>
			 </d:like>
		 </d:where>
		 <d:orderby/>
	</d:basicsearch>
</d:searchrequest>`,
	}) as ResponseDataDetailed<SearchResult>

	// check if the request was aborted
	if (signal?.aborted) {
		return []
	}

	// otherwise return the result mapped to Nextcloud nodes
	return data.results.map((result) => resultToNode(result, defaultRootPath, getBaseUrl()))
}
