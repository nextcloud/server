/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ContentsWithRoot } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import { Folder, Permission } from '@nextcloud/files'
import { defaultRemoteURL } from '@nextcloud/files/dav'
import { CancelablePromise } from 'cancelable-promise'
import { searchNodes } from './WebDavSearch.ts'

/**
 * Get the contents for a search view
 */
export function getContents(): CancelablePromise<ContentsWithRoot> {
	const controller = new AbortController()

	const dir = window.OCP.Files.Router.query.dir || '/'
	const { query } = window.OCP.Files.Router.query

	return new CancelablePromise<ContentsWithRoot>(async (resolve, reject, cancel) => {
		cancel(() => controller.abort())
		const contents = await searchNodes(query, { dir, signal: controller.signal })

		resolve({
			contents,
			folder: new Folder({
				id: 0,
				source: `${defaultRemoteURL}#search`,
				owner: getCurrentUser()!.uid,
				permissions: Permission.READ,
			}),
		})
	})
}
