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

import type { Node } from '@nextcloud/files'

import { emit } from '@nextcloud/event-bus'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { Permission, registerFileAction, FileAction, FileType } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import History from '@mdi/svg/svg/history.svg?raw'

import logger from '../../../files/src/logger.js'
import { encodePath } from '@nextcloud/paths'
import { RestoreParents, parseOriginalLocation, sortByDeletionTime } from '../utils.js'
import { confirmRestoration } from '../services/restoreDialog.ts'
import { useTrashbinStore } from '../store/trashbin.ts'

type Nullable<T> = null | T

const store = useTrashbinStore() // Use store to reduce DAV calls

/**
 * Return original parents of node sorted by most recently deleted
 *
 * @param node the node
 * @param nodes the other trash nodes
 */
const getOriginalParents = (node: Node, nodes: Node[]) => {
	const sortedNodes = nodes.toSorted(sortByDeletionTime)
	const originalParents = sortedNodes
		.filter(otherNode => {
			const originalPath = parseOriginalLocation(node, true)
			if (otherNode.type === FileType.File) {
				return false
			}
			const otherPath = parseOriginalLocation(otherNode, true)
			if (originalPath === otherPath) {
				return false
			}
			return originalPath.startsWith(otherPath)
		}).filter((otherNode, index, arr) => { // Filter out duplicates except the most recently deleted one
			const originalPath = parseOriginalLocation(otherNode, true)
			const firstIndexOfPath = arr.findIndex(node => originalPath === parseOriginalLocation(node, true))
			return firstIndexOfPath === index
		})
	return originalParents
}

const restore = async (node: Node): Promise<boolean> => {
	try {
		const destination = generateRemoteUrl(encodePath(`dav/trashbin/${getCurrentUser()?.uid}/restore/${node.basename}`))
		await axios({
			method: 'MOVE',
			url: node.encodedSource,
			headers: {
				destination,
			},
		})

		// Let's pretend the file is deleted since
		// we don't know the restored location
		emit('files:node:deleted', node)
		return true
	} catch (error) {
		logger.error(error)
		return false
	}
}

const restoreSequentially = async (nodes: Node[], withParents: boolean = true): Promise<Nullable<boolean>[]> => {
	const results: Nullable<boolean>[] = []
	for (const node of nodes) {
		if (withParents) {
			results.push(await restoreWithParents(node))
			continue
		}
		results.push(await restore(node))
	}
	return results
}

const restoreWithParents = async (node: Node): Promise<Nullable<boolean>> => {
	const otherNodes = (store.nodes.value as Node[]).filter(trashNode => trashNode.fileid !== node.fileid)
	const originalParents = getOriginalParents(node, otherNodes)
	if (originalParents.length === 0) {
		return restore(node)
	}
	const result = await confirmRestoration(node, originalParents)
	if (result === RestoreParents.Cancel) {
		return null
	}
	if (result === RestoreParents.Skip) {
		return restore(node)
	}
	const parentResults: Nullable<boolean>[] = await restoreSequentially(originalParents, false) // Bypass restoration with parents to avoid attempting to restore duplicates
	const restored = await restore(node)
	return restored && parentResults.every(Boolean)
}

const restoreBatchWithParents = async (nodes: Node[]): Promise<Nullable<boolean>[]> => {
	return restoreSequentially(nodes)
}

registerFileAction(new FileAction({
	id: 'restore',
	displayName() {
		return t('files_trashbin', 'Restore')
	},
	iconSvgInline: () => History,

	enabled(nodes: Node[], view) {
		// Only available in the trashbin view
		if (view.id !== 'trashbin') {
			return false
		}

		// Only available if all nodes have read permission
		return nodes.length > 0 && nodes
			.map(node => node.permissions)
			.every(permission => (permission & Permission.READ) !== 0)
	},

	async exec(node: Node) {
		await store.init()
		const result = await restoreWithParents(node)
		store.reset()
		return result
	},

	async execBatch(nodes: Node[]) {
		await store.init()
		const result = await restoreBatchWithParents(nodes)
		store.reset()
		return result
	},

	order: 1,
	inline: () => true,
}))
