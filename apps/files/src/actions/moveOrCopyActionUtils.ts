/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Folder, Node } from '@nextcloud/files'
import type { ShareAttribute } from '../../../files_sharing/src/sharing.ts'

import { Permission } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { isPublicShare } from '@nextcloud/sharing/public'
import PQueue from 'p-queue'

const sharePermissions = loadState<number>('files_sharing', 'sharePermissions', Permission.NONE)

// This is the processing queue. We only want to allow 3 concurrent requests
let queue: PQueue

// Maximum number of concurrent operations
const MAX_CONCURRENCY = 5

/**
 * Get the processing queue
 */
export function getQueue() {
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

/**
 *
 * @param nodes
 */
export function canMove(nodes: Node[]) {
	const minPermission = nodes.reduce((min, node) => Math.min(min, node.permissions), Permission.ALL)
	return Boolean(minPermission & Permission.DELETE)
}

/**
 *
 * @param nodes
 */
export function canDownload(nodes: Node[]) {
	return nodes.every((node) => {
		const shareAttributes = JSON.parse(node.attributes?.['share-attributes'] ?? '[]') as Array<ShareAttribute>
		return !shareAttributes.some((attribute) => attribute.scope === 'permissions' && attribute.value === false && attribute.key === 'download')
	})
}

/**
 *
 * @param nodes
 */
export function canCopy(nodes: Node[]) {
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
