/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ContentsWithRoot } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import { Folder, Permission } from '@nextcloud/files'
import { defaultRemoteURL } from '@nextcloud/files/dav'
import { CancelablePromise } from 'cancelable-promise'
import { searchNodes } from './WebDavSearch.ts'
import logger from '../logger.ts'
import { useSearchStore } from '../store/search.ts'
import { getPinia } from '../store/index.ts'

/**
 * Get the contents for a search view
 */
export function getContents(query = ''): CancelablePromise<ContentsWithRoot> {
	const controller = new AbortController()

	const searchStore = useSearchStore(getPinia())
	const dir = searchStore.base?.path

	return new CancelablePromise<ContentsWithRoot>(async (resolve, reject, cancel) => {
		cancel(() => controller.abort())
		try {
			const contents = await searchNodes(query || searchStore.query, { dir, signal: controller.signal })
			resolve({
				contents,
				folder: new Folder({
					id: 0,
					source: `${defaultRemoteURL}#search`,
					owner: getCurrentUser()!.uid,
					permissions: Permission.READ,
				}),
			})
		} catch (error) {
			// Be silent if the request was canceled
			if (error?.name === 'AbortError') {
				logger.debug('Search request was canceled', { query, dir })
				reject(error)
				return
			}
			logger.error('Failed to fetch search results', { error })
			reject(error)
		}
	})
}
