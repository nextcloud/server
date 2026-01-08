/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ContentsWithRoot } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import { Folder, Permission } from '@nextcloud/files'
import { getFavoriteNodes, getRemoteURL, getRootPath } from '@nextcloud/files/dav'
import logger from '../logger.ts'
import { getContents as filesContents } from './Files.ts'
import { client } from './WebdavClient.ts'

/**
 * Get the contents for the favorites view
 *
 * @param path - The path to get the contents for
 * @param options - Additional options
 * @param options.signal - Optional AbortSignal to cancel the request
 * @return A promise resolving to the contents with root folder
 */
export async function getContents(path = '/', options: { signal: AbortSignal }): Promise<ContentsWithRoot> {
	// We only filter root files for favorites, for subfolders we can simply reuse the files contents
	if (path && path !== '/') {
		return filesContents(path, options)
	}

	try {
		const contents = await getFavoriteNodes({ client, signal: options.signal })
		return {
			contents,
			folder: new Folder({
				id: 0,
				source: `${getRemoteURL()}${getRootPath()}`,
				root: getRootPath(),
				owner: getCurrentUser()?.uid || null,
				permissions: Permission.READ,
			}),
		}
	} catch (error) {
		if (options.signal.aborted) {
			logger.debug('Favorite nodes request was aborted')
			throw new DOMException('Aborted', 'AbortError')
		}
		logger.error('Failed to load favorite nodes via WebDAV', { error })
		throw error
	}
}
