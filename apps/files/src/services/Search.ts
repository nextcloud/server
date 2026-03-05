/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ContentsWithRoot } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import { Folder, Permission } from '@nextcloud/files'
import { defaultRemoteURL, getRootPath } from '@nextcloud/files/dav'
import logger from '../logger.ts'
import { getPinia } from '../store/index.ts'
import { useSearchStore } from '../store/search.ts'
import { searchNodes } from './WebDavSearch.ts'

/**
 * Get the contents for a search view
 *
 * @param path - (not used)
 * @param options - Options including abort signal
 * @param options.signal - Abort signal to cancel the request
 */
export async function getContents(path, options: { signal: AbortSignal }): Promise<ContentsWithRoot> {
	const searchStore = useSearchStore(getPinia())

	try {
		const contents = await searchNodes(searchStore.query, { signal: options.signal })
		return {
			contents,
			folder: new Folder({
				id: 0,
				source: `${defaultRemoteURL}${getRootPath()}}#search`,
				owner: getCurrentUser()!.uid,
				permissions: Permission.READ,
				root: getRootPath(),
			}),
		}
	} catch (error) {
		if (options.signal.aborted) {
			logger.info('Fetching search results aborted')
			throw new DOMException('Aborted', 'AbortError')
		}
		logger.error('Failed to fetch search results', { error })
		throw error
	}
}
