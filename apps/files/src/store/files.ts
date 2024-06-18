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

import type { FilesStore, RootsStore, RootOptions, Service, FilesState, FileSource } from '../types'
import type { FileStat, ResponseDataDetailed } from 'webdav'
import type { Folder, Node } from '@nextcloud/files'

import { davGetDefaultPropfind, davResultToNode, davRootPath } from '@nextcloud/files'
import { defineStore } from 'pinia'
import { subscribe } from '@nextcloud/event-bus'
import logger from '../logger'
import Vue from 'vue'

import { getClient } from '../services/WebdavClient.ts'
const client = getClient()

const fetchNode = async (node: Node): Promise<Node> => {
	const propfindPayload = davGetDefaultPropfind()
	const result = await client.stat(`${davRootPath}${node.path}`, {
		details: true,
		data: propfindPayload,
	}) as ResponseDataDetailed<FileStat>
	return davResultToNode(result.data)
}

export const useFilesStore = function(...args) {
	const store = defineStore('files', {
		state: (): FilesState => ({
			files: {} as FilesStore,
			roots: {} as RootsStore,
		}),

		getters: {
			/**
			 * Get a file or folder by its source
			 */
			getNode: (state) => (source: FileSource): Node|undefined => state.files[source],

			/**
			 * Get a list of files or folders by their IDs
			 * Note: does not return undefined values
			 */
			getNodes: (state) => (sources: FileSource[]): Node[] => sources
				.map(source => state.files[source])
				.filter(Boolean),

			/**
			 * Get files or folders by their file ID
			 * Multiple nodes can have the same file ID but different sources
			 * (e.g. in a shared context)
			 */
			getNodesById: (state) => (fileId: number): Node[] => Object.values(state.files).filter(node => node.fileid === fileId),

			/**
			 * Get the root folder of a service
			 */
			getRoot: (state) => (service: Service): Folder|undefined => state.roots[service],
		},

		actions: {
			updateNodes(nodes: Node[]) {
				// Update the store all at once
				const files = nodes.reduce((acc, node) => {
					if (!node.fileid) {
						logger.error('Trying to update/set a node without fileid', { node })
						return acc
					}

					acc[node.source] = node
					return acc
				}, {} as FilesStore)

				Vue.set(this, 'files', { ...this.files, ...files })
			},

			deleteNodes(nodes: Node[]) {
				nodes.forEach(node => {
					if (node.source) {
						Vue.delete(this.files, node.source)
					}
				})
			},

			setRoot({ service, root }: RootOptions) {
				Vue.set(this.roots, service, root)
			},

			onDeletedNode(node: Node) {
				this.deleteNodes([node])
			},

			onCreatedNode(node: Node) {
				this.updateNodes([node])
			},

			async onUpdatedNode(node: Node) {
				if (!node.fileid) {
					logger.error('Trying to update/set a node without fileid', { node })
					return
				}

				// If we have multiple nodes with the same file ID, we need to update all of them
				const nodes = this.getNodesById(node.fileid)
				if (nodes.length > 1) {
					await Promise.all(nodes.map(fetchNode)).then(this.updateNodes)
					logger.debug(nodes.length + ' nodes updated in store', { fileid: node.fileid })
					return
				}

				// If we have only one node with the file ID, we can update it directly
				if (node.source === nodes[0].source) {
					this.updateNodes([node])
					return
				}

				// Otherwise, it means we receive an event for a node that is not in the store
				fetchNode(node).then(n => this.updateNodes([n]))
			},
		},
	})

	const fileStore = store(...args)
	// Make sure we only register the listeners once
	if (!fileStore._initialized) {
		subscribe('files:node:created', fileStore.onCreatedNode)
		subscribe('files:node:deleted', fileStore.onDeletedNode)
		subscribe('files:node:updated', fileStore.onUpdatedNode)

		fileStore._initialized = true
	}

	return fileStore
}
