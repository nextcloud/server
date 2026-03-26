/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode, NodeData } from '@nextcloud/files'
import type { FileSource, FilesState, FilesStore, RootOptions, RootsStore, Service } from '../types.ts'

import { subscribe } from '@nextcloud/event-bus'
import { File, Folder } from '@nextcloud/files'
import { defineStore } from 'pinia'
import Vue from 'vue'
import logger from '../logger.ts'
import { fetchNode } from '../services/WebdavClient.ts'
import { usePathsStore } from './paths.ts'

const DB_NAME = 'nextcloud_files_store'
const DB_VERSION = 1
const STORE_FILES = 'files'
const STORE_ROOTS = 'roots'

/**
 * Lazily opened IndexedDB connection, shared across all store instances.
 * IndexedDB can store significantly more data than localStorage and supports
 * storing millions of individual file records efficiently.
 */
const dbPromise: Promise<IDBDatabase> = new Promise((resolve, reject) => {
	if (typeof indexedDB === 'undefined') {
		reject(new Error('IndexedDB is not available'))
		return
	}
	const request = indexedDB.open(DB_NAME, DB_VERSION)
	request.onupgradeneeded = (event) => {
		const db = (event.target as IDBOpenDBRequest).result
		if (!db.objectStoreNames.contains(STORE_FILES)) {
			db.createObjectStore(STORE_FILES)
		}
		if (!db.objectStoreNames.contains(STORE_ROOTS)) {
			db.createObjectStore(STORE_ROOTS)
		}
	}
	request.onsuccess = () => resolve(request.result)
	request.onerror = () => reject(request.error)
})

/**
 * Deserialize a stored JSON string back into a File or Folder instance.
 *
 * @param nodeJson JSON string of a serialized node
 */
function deserializeNode(nodeJson: string): INode | undefined {
	try {
		const node = JSON.parse(nodeJson) as [NodeData, RegExp]
		if (node[0].mime === 'httpd/unix-directory') {
			return new Folder(...node)
		}
		return new File(...node)
	} catch {
		return undefined
	}
}

/**
 * Restore all entries from an IndexedDB object store into a keyed map of INode instances.
 *
 * @param db Open IDBDatabase
 * @param storeName Name of the object store to read
 */
function restoreFromStore(db: IDBDatabase, storeName: string): Promise<Record<string, INode>> {
	return new Promise((resolve) => {
		const result: Record<string, INode> = {}
		const tx = db.transaction(storeName, 'readonly')
		const objectStore = tx.objectStore(storeName)
		const keysRequest = objectStore.getAllKeys()
		const valuesRequest = objectStore.getAll()

		tx.oncomplete = () => {
			const keys = keysRequest.result as string[]
			const values = valuesRequest.result as string[]
			keys.forEach((key, index) => {
				const raw = values[index]
				const node = raw !== undefined ? deserializeNode(raw) : undefined
				if (node) {
					result[key] = node
				}
			})
			resolve(result)
		}
		tx.onerror = () => resolve(result)
	})
}

/**
 *
 * @param args
 */
export function useFilesStore(...args) {
	const store = defineStore('files', {
		state: (): FilesState => ({
			files: {} as FilesStore,
			roots: {} as RootsStore,
		}),

		getters: {
			/**
			 * Get a file or folder by its source
			 *
			 * @param state
			 */
			getNode: (state) => (source: FileSource): INode | undefined => state.files[source],

			/**
			 * Get a list of files or folders by their IDs
			 * Note: does not return undefined values
			 *
			 * @param state
			 */
			getNodes: (state) => (sources: FileSource[]): INode[] => sources
				.map((source) => state.files[source])
				.filter(Boolean) as INode[],

			/**
			 * Get files or folders by their file ID
			 * Multiple nodes can have the same file ID but different sources
			 * (e.g. in a shared context)
			 *
			 * @param state
			 */
			getNodesById: (state) => (fileId: string): INode[] => Object.values(state.files).filter((node) => node.id === fileId),

			/**
			 * Get the root folder of a service
			 *
			 * @param state
			 */
			getRoot: (state) => (service: Service): IFolder | undefined => state.roots[service],
		},

		actions: {
			/**
			 * Get cached directory matching a given path
			 *
			 * @param service - The service (files view)
			 * @param path - The path relative within the service
			 * @return The folder if found
			 */
			getDirectoryByPath(service: string, path?: string): IFolder | undefined {
				const pathsStore = usePathsStore()
				let folder: IFolder | undefined

				// Get the containing folder from path store
				if (!path || path === '/') {
					folder = this.getRoot(service)
				} else {
					const source = pathsStore.getPath(service, path)
					if (source) {
						folder = this.getNode(source) as IFolder | undefined
					}
				}

				return folder
			},

			/**
			 * Get cached child nodes within a given path
			 *
			 * @param service - The service (files view)
			 * @param path - The path relative within the service
			 * @return Array of cached nodes within the path
			 */
			getNodesByPath(service: string, path?: string): INode[] {
				const folder = this.getDirectoryByPath(service, path)

				// If we found a cache entry and the cache entry was already loaded (has children) then use it
				return (folder?.attributes._children ?? [])
					.map((source: string) => this.getNode(source))
					.filter(Boolean)
			},

			updateNodes(nodes: INode[]) {
				// Update the store all at once
				const files = nodes.reduce((acc, node) => {
					if (!node.id) {
						logger.error('Trying to update/set a node without id', { node })
						return acc
					}

					acc[node.source] = node
					return acc
				}, {} as FilesStore)

				Vue.set(this, 'files', { ...this.files, ...files })

				// Persist new/updated nodes individually to IndexedDB
				dbPromise.then((db) => {
					const tx = db.transaction(STORE_FILES, 'readwrite')
					const objectStore = tx.objectStore(STORE_FILES)
					for (const [source, node] of Object.entries(files)) {
						objectStore.put(node.toJSON(), source)
					}
				}).catch((e) => logger.error('Failed to persist nodes to IndexedDB', { error: e }))
			},

			deleteNodes(nodes: INode[]) {
				nodes.forEach((node) => {
					if (node.source) {
						Vue.delete(this.files, node.source)
					}
				})

				// Remove deleted nodes from IndexedDB
				dbPromise.then((db) => {
					const tx = db.transaction(STORE_FILES, 'readwrite')
					const objectStore = tx.objectStore(STORE_FILES)
					for (const node of nodes) {
						if (node.source) {
							objectStore.delete(node.source)
						}
					}
				}).catch((e) => logger.error('Failed to delete nodes from IndexedDB', { error: e }))
			},

			setRoot({ service, root }: RootOptions) {
				Vue.set(this.roots, service, root)

				// Persist the root folder to IndexedDB
				dbPromise.then((db) => {
					const tx = db.transaction(STORE_ROOTS, 'readwrite')
					tx.objectStore(STORE_ROOTS).put(root.toJSON(), service)
				}).catch((e) => logger.error('Failed to persist root to IndexedDB', { error: e }))
			},

			onDeletedNode(node: INode) {
				this.deleteNodes([node])
			},

			onCreatedNode(node: INode) {
				this.updateNodes([node])
			},

			onMovedNode({ node, oldSource }: { node: INode, oldSource: string }) {
				if (!node.id) {
					logger.error('Trying to update/set a node without id', { node })
					return
				}

				// Remove the old source key from IndexedDB before writing the new one
				dbPromise.then((db) => {
					const tx = db.transaction(STORE_FILES, 'readwrite')
					tx.objectStore(STORE_FILES).delete(oldSource)
				}).catch((e) => logger.error('Failed to delete moved node from IndexedDB', { error: e }))

				// Update the path of the node
				Vue.delete(this.files, oldSource)
				this.updateNodes([node])
			},

			async onUpdatedNode(node: INode) {
				if (!node.id) {
					logger.error('Trying to update/set a node without id', { node })
					return
				}

				// If we have multiple nodes with the same file ID, we need to update all of them
				const nodes = this.getNodesById(node.id)
				if (nodes.length > 1) {
					await Promise.all(nodes.map((node) => fetchNode(node.path))).then(this.updateNodes)
					logger.debug(nodes.length + ' nodes updated in store', { id: node.id })
					return
				}

				// If we have only one node with the file ID, we can update it directly
				if (nodes.length === 1 && nodes[0] && node.source === nodes[0].source) {
					this.updateNodes([node])
					return
				}

				// Otherwise, it means we receive an event for a node that is not in the store
				fetchNode(node.path).then((n) => this.updateNodes([n]))
			},

			// Handlers for legacy sidebar (no real nodes support)
			onAddFavorite(node: INode) {
				const ourNode = this.getNode(node.source)
				if (ourNode) {
					Vue.set(ourNode.attributes, 'favorite', 1)
					// Persist the updated node to IndexedDB
					dbPromise.then((db) => {
						const tx = db.transaction(STORE_FILES, 'readwrite')
						tx.objectStore(STORE_FILES).put(ourNode.toJSON(), ourNode.source)
					}).catch((e) => logger.error('Failed to update favorite node in IndexedDB', { error: e }))
				}
			},

			onRemoveFavorite(node: INode) {
				const ourNode = this.getNode(node.source)
				if (ourNode) {
					Vue.set(ourNode.attributes, 'favorite', 0)
					// Persist the updated node to IndexedDB
					dbPromise.then((db) => {
						const tx = db.transaction(STORE_FILES, 'readwrite')
						tx.objectStore(STORE_FILES).put(ourNode.toJSON(), ourNode.source)
					}).catch((e) => logger.error('Failed to update favorite node in IndexedDB', { error: e }))
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

		// Restore state from IndexedDB asynchronously.
		// Each node is stored as an individual record, so this scales to millions of files.
		dbPromise.then(async (db) => {
			const [files, roots] = await Promise.all([
				restoreFromStore(db, STORE_FILES),
				restoreFromStore(db, STORE_ROOTS),
			])
			fileStore.$state.files = files as FilesStore
			fileStore.$state.roots = roots as RootsStore
			logger.info('Restored files store from IndexedDB', {
				files: Object.keys(files).length,
				roots: Object.keys(roots).length,
			})
		}).catch((e) => logger.info('Failed to restore files store from IndexedDB', { error: e }))

		fileStore._initialized = true
	}

	return fileStore
}
