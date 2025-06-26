/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ContentsWithRoot } from '@nextcloud/files'

import { CancelablePromise } from 'cancelable-promise'
import { File, Folder, Permission,  } from '@nextcloud/files'
import { generateOcsUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { getRemoteURL, getRootPath } from '@nextcloud/files/dav'
import axios from '@nextcloud/axios'

import { getContents as getDefaultContents } from './Files'

type RecommendedFiles = {
	'id': string
	'timestamp': number
	'name': string
	'directory': string
	'extension': string
	'mimeType': string
	'hasPreview': boolean
	'reason': string
}

type RecommendedFilesResponse = {
	'recommendations': RecommendedFiles[]
}

const fetchRecommendedFiles = (controller: AbortController): Promise<RecommendedFilesResponse> => {
	const url = generateOcsUrl('apps/recommendations/api/v1/recommendations/always')

	return axios.get(url, {
		signal: controller.signal,
		headers: {
			'OCS-APIRequest': 'true',
			'Content-Type': 'application/json',
		},
	}).then(resp => resp.data.ocs.data as RecommendedFilesResponse)
}

export const getContents = (path = '/'): CancelablePromise<ContentsWithRoot> => {
	if (path !== '/') {
		return getDefaultContents(path)
	}

	const controller = new AbortController()
	return new CancelablePromise(async (resolve, reject, cancel) => {
		cancel(() => controller.abort())
		try {
			const { recommendations } = await fetchRecommendedFiles(controller)

			resolve({
				folder: new Folder({
					id: 0,
					source: `${getRemoteURL()}${getRootPath()}`,
					root: getRootPath(),
					owner: getCurrentUser()?.uid || null,
					permissions: Permission.READ,
				}),
				contents: recommendations.map((rec) => {
					const Node = rec.mimeType === 'httpd/unix-directory' ? Folder : File
					return new Node({
						id: parseInt(rec.id),
						source: `${getRemoteURL()}/${getRootPath()}/${rec.directory}/${rec.name}`.replace(/\/\//g, '/'),
						root: getRootPath(),
						mime: rec.mimeType,
						mtime: new Date(rec.timestamp * 1000),
						owner: getCurrentUser()?.uid || null,
						permissions: Permission.READ,
						attributes: rec,
					})
				}),
			})
		} catch (error) {
			reject(error)
		}
	})
}
