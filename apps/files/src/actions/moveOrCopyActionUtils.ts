/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Folder, Node } from '@nextcloud/files'
import type { ShareAttribute } from '../../../files_sharing/src/sharing'

import { Permission } from '@nextcloud/files'
import { isPublicShare } from '@nextcloud/sharing/public'
import PQueue from 'p-queue'
import { loadState } from '@nextcloud/initial-state'

const sharePermissions = loadState<number>('files_sharing', 'sharePermissions', Permission.NONE)

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
	if (!canDownload(nodes)) {
		return false
	}
	// it cannot be copied if the user has only view permissions
	if (nodes.some((node) => node.permissions === Permission.NONE)) {
		return false
	}
	// on public shares all files have the same permission so copy is only possible if write permission is granted
	if (isPublicShare()) {
		return Boolean(sharePermissions & Permission.CREATE)
	}
	// otherwise permission is granted
	return true
}
