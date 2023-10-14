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

import '@nextcloud/dialogs/style.css'

import type { Node } from '@nextcloud/files'
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
	// For now the only restriction is that a shared file
	// cannot be copied if the download is disabled
	return canDownload(nodes)
}
