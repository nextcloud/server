/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Folder, Node } from '@nextcloud/files'
import type { ShareAttribute } from '../../../files_sharing/src/sharing'

import { Permission } from '@nextcloud/files'
import PQueue from 'p-queue'

// This is the processing queue. We only want to allow 3 concurrent requests
let queue: PQueue

// Maximum number of concurrent operations
const MAX_CONCURRENCY = 5

/**
 * Get the processing queue
 */
export const getQueue = () => {
	if (!queue) {
		queue = new PQueue({ concurrency: MAX_CONCURRENCY })
	}
	return queue
}

export enum MoveCopyAction {
	MOVE = 'Move',
	COPY = 'Copy',
	MOVE_OR_COPY = 'move-or-copy',
}

export type MoveCopyResult = {
	destination: Folder
	action: MoveCopyAction.COPY | MoveCopyAction.MOVE
}

export const canMove = (nodes: Node[]) => {
	const minPermission = nodes.reduce((min, node) => Math.min(min, node.permissions), Permission.ALL)
	return Boolean(minPermission & Permission.DELETE)
}

export const canDownload = (nodes: Node[]) => {
	return nodes.every(node => {
		const shareAttributes = JSON.parse(node.attributes?.['share-attributes'] ?? '[]') as Array<ShareAttribute>
		return !shareAttributes.some(attribute => attribute.scope === 'permissions' && attribute.value === false && attribute.key === 'download')

	})
}

export const canCopy = (nodes: Node[]) => {
	// a shared file cannot be copied if the download is disabled
	// it can be copied if the user has at least read permissions
	return canDownload(nodes)
		&& !nodes.some(node => node.permissions === Permission.NONE)
}
