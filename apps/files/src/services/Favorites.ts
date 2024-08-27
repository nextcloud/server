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
import type { ContentsWithRoot } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import { Folder, Permission, davRemoteURL, davRootPath, getFavoriteNodes } from '@nextcloud/files'
import { CancelablePromise } from 'cancelable-promise'
import { getContents as filesContents } from './Files.ts'
import { client } from './WebdavClient.ts'

export const getContents = (path = '/'): CancelablePromise<ContentsWithRoot> | Promise<ContentsWithRoot> => {
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
