/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { FileSource, PathsStore, PathOptions, ServicesState, Service } from '../types'
import { defineStore } from 'pinia'
import { dirname } from '@nextcloud/paths'
import { File, FileType, Folder, Node, getNavigation } from '@nextcloud/files'
import { subscribe } from '@nextcloud/event-bus'
import Vue from 'vue'
import logger from '../logger'

import { useFilesStore } from './files'

export const usePathsStore = function(...args) {
	const files = useFilesStore(...args)

	const store = defineStore('paths', {
		state: () => ({
			paths: {} as ServicesState,
		} as PathsStore),

		getters: {
			getPath: (state) => {
				return (service: string, path: string): FileSource|undefined => {
					if (!state.paths[service]) {
						return undefined
					}
					return state.paths[service][path]
				}
			},
		},

		actions: {
			addPath(payload: PathOptions) {
				// If it doesn't exists, init the service state
				if (!this.paths[payload.service]) {
					Vue.set(this.paths, payload.service, {})
				}

				// Now we can set the provided path
				Vue.set(this.paths[payload.service], payload.path, payload.source)
			},

			deletePath(service: Service, path: string) {
				// skip if service does not exist
				if (!this.paths[service]) {
					return
				}

				Vue.delete(this.paths[service], path)
			},

			onCreatedNode(node: Node) {
				const service = getNavigation()?.active?.id || 'files'
				if (!node.fileid) {
					logger.error('Node has no fileid', { node })
					return
				}

				// Only add path if it's a folder
				if (node.type === FileType.Folder) {
					this.addPath({
						service,
						path: node.path,
						source: node.source,
					})
				}

				// Update parent folder children if exists
				// If the folder is the root, get it and update it
				this.addNodeToParentChildren(node)
			},

			onDeletedNode(node: Node) {
				const service = getNavigation()?.active?.id || 'files'

				if (node.type === FileType.Folder) {
					// Delete the path
					this.deletePath(
						service,
						node.path,
					)
				}

				this.deleteNodeFromParentChildren(node)
			},

			onMovedNode({ node, oldSource }: { node: Node, oldSource: string }) {
				const service = getNavigation()?.active?.id || 'files'

				// Update the path of the node
				if (node.type === FileType.Folder) {
					// Delete the old path if it exists
					const oldPath = Object.entries(this.paths[service]).find(([, source]) => source === oldSource)
					if (oldPath?.[0]) {
						this.deletePath(service, oldPath[0])
					}

					// Add the new path
					this.addPath({
						service,
						path: node.path,
						source: node.source,
					})
				}

				// Dummy simple clone of the renamed node from a previous state
				const oldNode = new File({ source: oldSource, owner: node.owner, mime: node.mime })

				this.deleteNodeFromParentChildren(oldNode)
				this.addNodeToParentChildren(node)
			},

			deleteNodeFromParentChildren(node: Node) {
				const service = getNavigation()?.active?.id || 'files'

				// Update children of a root folder
				const parentSource = dirname(node.source)
				const folder = (node.dirname === '/' ? files.getRoot(service) : files.getNode(parentSource)) as Folder & { _children?: string[] }
				if (folder) {
					// ensure sources are unique
					const children = new Set(folder._children ?? [])
					children.delete(node.source)
					Vue.set(folder, '_children', [...children.values()])
					logger.debug('Children updated', { parent: folder, node, children: folder._children })
					return
				}

				logger.debug('Parent path does not exists, skipping children update', { node })
			},

			addNodeToParentChildren(node: Node) {
				const service = getNavigation()?.active?.id || 'files'

				// Update children of a root folder
				const parentSource = dirname(node.source)
				const folder = (node.dirname === '/' ? files.getRoot(service) : files.getNode(parentSource)) as Folder & { _children?: string[] }
				if (folder) {
					// ensure sources are unique
					const children = new Set(folder._children ?? [])
					children.add(node.source)
					Vue.set(folder, '_children', [...children.values()])
					logger.debug('Children updated', { parent: folder, node, children: folder._children })
					return
				}

				logger.debug('Parent path does not exists, skipping children update', { node })
			},

		},
	})

	const pathsStore = store(...args)
	// Make sure we only register the listeners once
	if (!pathsStore._initialized) {
		subscribe('files:node:created', pathsStore.onCreatedNode)
		subscribe('files:node:deleted', pathsStore.onDeletedNode)
		subscribe('files:node:moved', pathsStore.onMovedNode)

		pathsStore._initialized = true
	}

	return pathsStore
}
