/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ContentsWithRoot } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed, DAVResultResponseProps } from 'webdav'

import { CancelablePromise } from 'cancelable-promise'
import { File, Folder, davParsePermissions, davGetDefaultPropfind } from '@nextcloud/files'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

import { getClient, rootPath } from './WebdavClient'
import { hashCode } from '../utils/hashUtils'
import logger from '../logger'

const client = getClient()

interface ResponseProps extends DAVResultResponseProps {
	permissions: string,
	fileid: number,
	size: number,
}

export const resultToNode = function(node: FileStat): File | Folder {
	const userId = getCurrentUser()?.uid
	if (!userId) {
		throw new Error('No user id found')
	}

	const props = node.props as ResponseProps
	const permissions = davParsePermissions(props?.permissions)
	const owner = (props['owner-id'] || userId).toString()

	const source = generateRemoteUrl('dav' + rootPath + node.filename)
	const id = props?.fileid < 0
		? hashCode(source)
		: props?.fileid as number || 0

	const nodeData = {
		id,
		source,
		mtime: new Date(node.lastmod),
		mime: node.mime || 'application/octet-stream',
		size: props?.size as number || 0,
		permissions,
		owner,
		root: rootPath,
		attributes: {
			...node,
			...props,
			hasPreview: props?.['has-preview'],
			failed: props?.fileid < 0,
		},
	}

	delete nodeData.attributes.props

	return node.type === 'file'
		? new File(nodeData)
		: new Folder(nodeData)
}

export const getContents = (path = '/'): Promise<ContentsWithRoot> => {
	const controller = new AbortController()
	const propfindPayload = davGetDefaultPropfind()

	return new CancelablePromise(async (resolve, reject, onCancel) => {
		onCancel(() => controller.abort())
		try {
			const contentsResponse = await client.getDirectoryContents(path, {
				details: true,
				data: propfindPayload,
				includeSelf: true,
				signal: controller.signal,
			}) as ResponseDataDetailed<FileStat[]>

			const root = contentsResponse.data[0]
			const contents = contentsResponse.data.slice(1)
			if (root.filename !== path) {
				throw new Error('Root node does not match requested path')
			}

			resolve({
				folder: resultToNode(root) as Folder,
				contents: contents.map(result => {
					try {
						return resultToNode(result)
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
