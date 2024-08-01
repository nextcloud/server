/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ContentsWithRoot } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import { Folder, Permission, davRemoteURL, davRootPath, getFavoriteNodes } from '@nextcloud/files'
import { CancelablePromise } from 'cancelable-promise'
import { getContents as filesContents } from './Files.ts'
import { client } from './WebdavClient.ts'

export const getContents = (path = '/'): CancelablePromise<ContentsWithRoot> => {
	// We only filter root files for favorites, for subfolders we can simply reuse the files contents
	if (path !== '/') {
		return filesContents(path)
	}

	return new CancelablePromise((resolve, reject, cancel) => {
		const promise = getFavoriteNodes(client)
			.catch(reject)
			.then((contents) => {
				if (!contents) {
					reject()
					return
				}
				resolve({
					contents,
					folder: new Folder({
						id: 0,
						source: `${davRemoteURL}${davRootPath}`,
						root: davRootPath,
						owner: getCurrentUser()?.uid || null,
						permissions: Permission.READ,
					}),
				})
			})
		cancel(() => promise.cancel())
	})
}
