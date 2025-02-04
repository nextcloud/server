/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { FilesStore, RootsStore, RootOptions, Service, FilesState, FileSource } from '../types'
import type { Folder, Node } from '@nextcloud/files'

import { defineStore } from 'pinia'
import { subscribe } from '@nextcloud/event-bus'
import logger from '../logger'
import Vue from 'vue'

import { fetchNode } from '../services/WebdavClient.ts'
import { usePathsStore } from './paths.ts'

export const useFilesStore = function(...args) {
	const store = defineStore('files', {
		state: (): FilesState => ({
			files: {} as FilesStore,
			roots: {} as RootsStore,
		}),

		getters: {
			/**
			 * Get a file or folder by its source
			 * @param state
			 */
			getNode: (state) => (source: FileSource): Node|undefined => state.files[source],

			/**
			 * Get a list of files or folders by their IDs
			 * Note: does not return undefined values
			 * @param state
			 */
			getNodes: (state) => (sources: FileSource[]): Node[] => sources
				.map(source => state.files[source])
				.filter(Boolean),

			/**
			 * Get files or folders by their file ID
			 * Multiple nodes can have the same file ID but different sources
			 * (e.g. in a shared context)
			 * @param state
			 */
			getNodesById: (state) => (fileId: number): Node[] => Object.values(state.files).filter(node => node.fileid === fileId),

			/**
			 * Get the root folder of a service
			 * @param state
			 */
			getRoot: (state) => (service: Service): Folder|undefined => state.roots[service],
		},

		actions: {
			/**
			 * Get cached child nodes within a given path
			 *
			 * @param service The service (files view)
			 * @param path The path relative within the service
			 * @return Array of cached nodes within the path
			 */
			getNodesByPath(service: string, path?: string): Node[] {
				const pathsStore = usePathsStore()
				let folder: Folder | undefined

				// Get the containing folder from path store
				if (!path || path === '/') {
					folder = this.getRoot(service)
				} else {
					const source = pathsStore.getPath(service, path)
					if (source) {
						folder = this.getNode(source) as Folder | undefined
					}
				}

				// If we found a cache entry and the cache entry was already loaded (has children) then use it
				return (folder?._children ?? [])
					.map((source: string) => this.getNode(source))
					.filter(Boolean)
			},

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

			onMovedNode({ node, oldSource }: { node: Node, oldSource: string }) {
				if (!node.fileid) {
					logger.error('Trying to update/set a node without fileid', { node })
					return
				}

				// Update the path of the node
				Vue.delete(this.files, oldSource)
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
					await Promise.all(nodes.map(node => fetchNode(node.path))).then(this.updateNodes)
					logger.debug(nodes.length + ' nodes updated in store', { fileid: node.fileid })
					return
				}

				// If we have only one node with the file ID, we can update it directly
				if (node.source === nodes[0].source) {
					this.updateNodes([node])
					return
				}

				// Otherwise, it means we receive an event for a node that is not in the store
				fetchNode(node.path).then(n => this.updateNodes([n]))
			},

			// Handlers for legacy sidebar (no real nodes support)
			onAddFavorite(node: Node) {
				const ourNode = this.getNode(node.source)
				if (ourNode) {
					Vue.set(ourNode.attributes, 'favorite', 1)
				}
			},

			onRemoveFavorite(node: Node) {
				const ourNode = this.getNode(node.source)
				if (ourNode) {
					Vue.set(ourNode.attributes, 'favorite', 0)
				}
			},
		},
	})

	const fileStore = store(...args)
	// Make sure we only register the listeners once
	if (!fileStore._initialized) {
		subscribe('files:node:created', fileStore.onCreatedNode)
		subscribe('files:node:deleted', fileStore.onDeletedNode)
		subscribe('files:node:updated', fileStore.onUpdatedNode)
		subscribe('files:node:moved', fileStore.onMovedNode)
		// legacy sidebar
		subscribe('files:favorites:added', fileStore.onAddFavorite)
		subscribe('files:favorites:removed', fileStore.onRemoveFavorite)

		fileStore._initialized = true
	}

	return fileStore
}
