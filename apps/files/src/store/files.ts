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
/* eslint-disable */
import type { Folder, Node } from '@nextcloud/files'
import type { FilesStore, RootsStore, RootOptions, Service, FilesState } from '../types'

import { defineStore } from 'pinia'
import { subscribe } from '@nextcloud/event-bus'
import logger from '../logger'
import type { FileId } from '../types'
import Vue from 'vue'

export const useFilesStore = function() {
	const store = defineStore('files', {
		state: (): FilesState => ({
			files: {} as FilesStore,
			roots: {} as RootsStore,
		}),

		getters: {
			/**
			 * Get a file or folder by id
			 */
			getNode: (state)  => (id: FileId): Node|undefined => state.files[id],

			/**
			 * Get a list of files or folders by their IDs
			 * Does not return undefined values
			 */
			getNodes: (state) => (ids: FileId[]): Node[] => ids
				.map(id => state.files[id])
				.filter(Boolean),
			/**
			 * Get a file or folder by id
			 */
			getRoot: (state)  => (service: Service): Folder|undefined => state.roots[service],
		},

		actions: {
			updateNodes(nodes: Node[]) {
				// Update the store all at once
				const files = nodes.reduce((acc, node) => {
					if (!node.fileid) {
						logger.warn('Trying to update/set a node without fileid', node)
						return acc
					}
					acc[node.fileid] = node
					return acc
				}, {} as FilesStore)

				Vue.set(this, 'files', {...this.files, ...files})
			},

			deleteNodes(nodes: Node[]) {
				nodes.forEach(node => {
					if (node.fileid) {
						Vue.delete(this.files, node.fileid)
					}
				})
			},

			setRoot({ service, root }: RootOptions) {
				Vue.set(this.roots, service, root)
			},

			onDeletedNode(node: Node) {
				this.deleteNodes([node])
			},
		}
	})

	const fileStore = store(...arguments)
	// Make sure we only register the listeners once
	if (!fileStore._initialized) {
		// subscribe('files:node:created', fileStore.onCreatedNode)
		subscribe('files:node:deleted', fileStore.onDeletedNode)
		// subscribe('files:node:moved', fileStore.onMovedNode)
		// subscribe('files:node:updated', fileStore.onUpdatedNode)

		fileStore._initialized = true
	}

	return fileStore
}
