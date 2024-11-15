/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'
import type { ShareAttribute } from '../../../files_sharing/src/sharing'

import { Permission } from '@nextcloud/files'
import { isPublicShare } from '@nextcloud/sharing/public'
import { loadState } from '@nextcloud/initial-state'

/**
 * Check if the node can be download
 * @param nodes one or multiple nodes to check
 */
export function canDownload(nodes: INode | INode[]): boolean {
	return [nodes].flat().every(node => {
		const shareAttributes = JSON.parse(node.attributes?.['share-attributes'] || '[]') as Array<ShareAttribute>
		return !shareAttributes.some(attribute => attribute.scope === 'permissions' && attribute.value === false && attribute.key === 'download')

	})
}

/**
 * Check if the node can be moved
 * @param nodes one or multiple nodes to check
 */
export function canMove(nodes: INode | INode[]): boolean {
	const minPermission = [nodes].flat()
		.reduce((min, node) => Math.min(min, node.permissions), Permission.ALL)
	return Boolean(minPermission & Permission.DELETE)
}

// On public shares we need to consider the top level permission
const sharePermissions = loadState<number>('files_sharing', 'sharePermissions', Permission.NONE)

/**
 * Check if the node can be copied
 * @param nodes one or multiple nodes to check
 */
export function canCopy(nodes: INode | INode[]): boolean {
	// a shared file cannot be copied if the download is disabled
	if (!canDownload(nodes)) {
		return false
	}
	// it cannot be copied if the user has only view permissions
	if ([nodes].flat().some((node) => node.permissions === Permission.NONE)) {
		return false
	}

	// on public shares all files have the same permission so copy is only possible if write permission is granted
	if (isPublicShare()) {
		return Boolean(sharePermissions & Permission.CREATE)
	}
	// otherwise permission is granted
	return true
}
