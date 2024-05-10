/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Folder, Node } from '@nextcloud/files'
import type { FilesStore, RootsStore, RootOptions, Service, FilesState, FileSource } from '../types'

import { defineStore } from 'pinia'
import { subscribe } from '@nextcloud/event-bus'
import logger from '../logger'
import Vue from 'vue'

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

			onUpdatedNode(node: Node) {
				this.updateNodes([node])
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
