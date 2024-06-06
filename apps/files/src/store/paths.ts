/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { FileId, PathsStore, PathOptions, ServicesState } from '../types'
import { defineStore } from 'pinia'
import { FileType, Folder, Node, getNavigation } from '@nextcloud/files'
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
				return (service: string, path: string): FileId|undefined => {
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
				Vue.set(this.paths[payload.service], payload.path, payload.fileid)
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
						fileid: node.fileid,
					})
				}

				// Update parent folder children if exists
				// If the folder is the root, get it and update it
				if (node.dirname === '/') {
					const root = files.getRoot(service)
					if (!root._children) {
						Vue.set(root, '_children', [])
					}
					root._children.push(node.fileid)
					return
				}

				// If the folder doesn't exists yet, it will be
				// fetched later and its children updated anyway.
				if (this.paths[service][node.dirname]) {
					const parentId = this.paths[service][node.dirname]
					const parentFolder = files.getNode(parentId) as Folder
					logger.debug('Path already exists, updating children', { parentFolder, node })

					if (!parentFolder) {
						logger.error('Parent folder not found', { parentId })
						return
					}

					if (!parentFolder._children) {
						Vue.set(parentFolder, '_children', [])
					}
					parentFolder._children.push(node.fileid)
					return
				}

				logger.debug('Parent path does not exists, skipping children update', { node })
			},
		},
	})

	const pathsStore = store(...args)
	// Make sure we only register the listeners once
	if (!pathsStore._initialized) {
		// TODO: watch folders to update paths?
		subscribe('files:node:created', pathsStore.onCreatedNode)
		// subscribe('files:node:deleted', pathsStore.onDeletedNode)
		// subscribe('files:node:moved', pathsStore.onMovedNode)

		pathsStore._initialized = true
	}

	return pathsStore
}
