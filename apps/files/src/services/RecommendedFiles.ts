/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ContentsWithRoot } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { CancelablePromise } from 'cancelable-promise'
import { File, Folder, Permission } from '@nextcloud/files'
import { getCurrentUser } from '@nextcloud/auth'
import { getDefaultPropfind, getRemoteURL, registerDavProperty, resultToNode } from '@nextcloud/files/dav'
import { client } from './WebdavClient'
import logger from '../logger'
import { getCapabilities } from '@nextcloud/capabilities'
import { getContents as getRecentContents } from './Recent'

// Check if the recommendations capability is enabled
// If not, we'll just use recent files
const isRecommendationEnabled = getCapabilities()?.recommendations?.enabled === true
if (isRecommendationEnabled) {
	registerDavProperty('nc:recommendation-reason', { nc: 'http://nextcloud.org/ns' })
	registerDavProperty('nc:recommendation-reason-label', { nc: 'http://nextcloud.org/ns' })
}

export const getContents = (): CancelablePromise<ContentsWithRoot> => {
	if (!isRecommendationEnabled) {
		logger.debug('Recommendations capability is not enabled, falling back to recent files')
		return getRecentContents()
	}

	const controller = new AbortController()
	const propfindPayload = getDefaultPropfind()

	return new CancelablePromise(async (resolve, reject, onCancel) => {
		onCancel(() => controller.abort())

		const root = `/recommendations/${getCurrentUser()?.uid}`
		try {
			const contentsResponse = await client.getDirectoryContents(root, {
				details: true,
				data: propfindPayload,
				includeSelf: false,
				signal: controller.signal,
			}) as ResponseDataDetailed<FileStat[]>

			const contents = contentsResponse.data
			resolve({
				folder: new Folder({
					id: 0,
					source: `${getRemoteURL()}${root}`,
					root,
					owner: getCurrentUser()?.uid || null,
					permissions: Permission.READ,
				}),
				contents: contents.map((result) => {
					try {
						return resultToNode(result, root)
					} catch (error) {
						logger.error(`Invalid node detected '${result.basename}'`, { error })
						return null
					}
				}).filter(Boolean) as File[],
			})
		} catch (error) {
			reject(error)
		}
	})
}
