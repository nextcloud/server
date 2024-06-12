/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Folder, Node } from '@nextcloud/files'
import { Permission } from '@nextcloud/files'
import PQueue from 'p-queue'

// This is the processing queue. We only want to allow 3 concurrent requests
let queue: PQueue

/**
 * Get the processing queue
 */
export const getQueue = () => {
	if (!queue) {
		queue = new PQueue({ concurrency: 3 })
	}
	return queue
}

type ShareAttribute = {
	enabled: boolean
	key: string
	scope: string
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
	return (minPermission & Permission.UPDATE) !== 0
}

export const canDownload = (nodes: Node[]) => {
	return nodes.every(node => {
		const shareAttributes = JSON.parse(node.attributes?.['share-attributes'] ?? '[]') as Array<ShareAttribute>
		return !shareAttributes.some(attribute => attribute.scope === 'permissions' && attribute.enabled === false && attribute.key === 'download')

	})
}

export const canCopy = (nodes: Node[]) => {
	// a shared file cannot be copied if the download is disabled
	// it can be copied if the user has at least read permissions
	return canDownload(nodes)
		&& !nodes.some(node => node.permissions === Permission.NONE)
}
