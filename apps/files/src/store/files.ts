/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode } from '@nextcloud/files'
import type { FileSource, FilesStore, RootOptions, RootsStore, Service } from '../types.ts'

import { subscribe } from '@nextcloud/event-bus'
import { defineStore } from 'pinia'
import Vue, { ref } from 'vue'
import { fetchNode } from '../services/WebdavClient.ts'
import { logger } from '../utils/logger.ts'
import { usePathsStore } from './paths.ts'

/**
 * Store for files and folders in the files app.
 */
export const useFilesStore = defineStore('files', () => {
	const files = ref<FilesStore>({})
	const roots = ref<RootsStore>({})

	// initialize the store once its used first time
	initalizeStore()

	/**
	 * Get a file or folder by its source
	 *
	 * @param source - The file source
	 */
	function getNode(source: FileSource): INode | undefined {
		return files.value[source]
	}

	/**
	 * Get a list of files or folders by their IDs
	 * Note: does not return undefined values
	 *
	 * @param sources - The file sources
	 */
	function getNodes(sources: FileSource[]): INode[] {
		return sources
			.map((source) => files.value[source])
			.filter(Boolean) as INode[]
	}

	/**
	 * Get files or folders by their ID
	 * Multiple nodes can have the same ID but different sources
	 * (e.g. in a shared context)
	 *
	 * @param id - The file ID
		*/
	function getNodesById(id: string): INode[] {
		return Object.values(files.value)
			.filter((node) => node.id === id)
	}

	/**
	 * Get the root folder of a service
	 *
	 * @param service - The service (files view)
	 * @return The root folder if set
	 */
	function getRoot(service: Service): IFolder | undefined {
		return roots.value[service]
	}

	/**
	 * Get cached directory matching a given path
	 *
	 * @param service - The service (files view)
	 * @param path - The path relative within the service
	 * @return The folder if found
	 */
	function getDirectoryByPath(service: string, path?: string): IFolder | undefined {
		const pathsStore = usePathsStore()
		let folder: IFolder | undefined

		// Get the containing folder from path store
		if (!path || path === '/') {
			folder = getRoot(service)
		} else {
			const source = pathsStore.getPath(service, path)
			if (source) {
				folder = getNode(source) as IFolder | undefined
			}
		}

		return folder
	}

	/**
	 * Get cached child nodes within a given path
	 *
	 * @param service - The service (files view)
	 * @param path - The path relative within the service
	 * @return Array of cached nodes within the path
	 */
	function getNodesByPath(service: string, path?: string): INode[] {
		const folder = getDirectoryByPath(service, path)

		// If we found a cache entry and the cache entry was already loaded (has children) then use it
		return ((folder as { _children?: string[] })?._children ?? [])
			.map((source: string) => getNode(source))
			.filter(Boolean) as INode[]
	}

	/**
	 * Update or set nodes in the store
	 *
	 * @param nodes - The nodes to update or set
	 */
	function updateNodes(nodes: INode[]) {
		// Update the store all at once
		const newNodes = nodes.reduce((acc, node) => {
			if (files.value[node.source]?.id && !node.id) {
				logger.error('Trying to update/set a node without id', { node })
				return acc
			}

			acc[node.source] = node
			return acc
		}, {} as FilesStore)

		files.value = { ...files.value, ...newNodes }
	}

	/**
	 * Delete nodes from the store
	 *
	 * @param nodes - The nodes to delete
	 */
	function deleteNodes(nodes: INode[]) {
		const entries = Object.entries(files.value)
			.filter(([, node]) => !nodes.some((n) => n.source === node.source))
		files.value = Object.fromEntries(entries)
	}

	/**
	 * Set the root folder for a service
	 *
	 * @param options - The options for setting the root
	 * @param options.service - The service (files view)
	 * @param options.root - The root folder
	 */
	function setRoot({ service, root }: RootOptions) {
		roots.value = { ...roots.value, [service]: root }
	}

	return {
		files,
		roots,

		deleteNodes,
		getDirectoryByPath,
		getNode,
		getNodes,
		getNodesById,
		getNodesByPath,
		getRoot,
		setRoot,
		updateNodes,
	}

	// Internal helper functions

	/**
	 * Initialize the store by subscribing to events
	 */
	function initalizeStore() {
		subscribe('files:node:created', onCreatedNode)
		subscribe('files:node:deleted', onDeletedNode)
		subscribe('files:node:updated', onUpdatedNode)
		subscribe('files:node:moved', onMovedNode)
		// legacy sidebar
		subscribe('files:favorites:added', onAddFavorite)
		subscribe('files:favorites:removed', onRemoveFavorite)
	}

	/**
	 * Called when a node is deleted, removes the node from the store
	 *
	 * @param node - The deleted node
	 */
	function onDeletedNode(node: INode) {
		deleteNodes([node])
	}

	/**
	 * Handler for when a node is created
	 *
	 * @param node - The created node
	 */
	function onCreatedNode(node: INode) {
		updateNodes([node])
	}

	/**
	 * Handler for when a node is moved, updates the path of the node in the store
	 *
	 * @param context - The context of the moved node
	 * @param context.node - The moved node
	 * @param context.oldSource - The old source of the node before it was moved
	 */
	function onMovedNode({ node, oldSource }: { node: INode, oldSource: string }) {
		// Update the path of the node
		delete files.value[oldSource]
		updateNodes([node])
	}

	/**
	 * Handler for when a node is updated, updates the node in the store
	 *
	 * @param node - The updated node
	 */
	async function onUpdatedNode(node: INode) {
		// If we have multiple nodes with the same file ID, we need to update all of them
		const nodes = node.id
			? getNodesById(node.id)
			: getNodes([node.source])
		if (nodes.length > 1) {
			await Promise.all(nodes.map((node) => fetchNode(node.path))).then(updateNodes)
			logger.debug(nodes.length + ' nodes updated in store', { fileid: node.id, source: node.source })
			return
		}

		// If we have only one node with the file ID, we can update it directly
		if (nodes.length === 1 && node.source === nodes[0]!.source) {
			updateNodes([node])
			return
		}

		// Otherwise, it means we receive an event for a node that is not in the store
		fetchNode(node.path).then((n) => updateNodes([n]))
	}

	/**
	 * Handlers for legacy sidebar (no real nodes support)
	 *
	 * @param node - The node that was added to favorites
	 */
	function onAddFavorite(node: INode) {
		const ourNode = getNode(node.source)
		if (ourNode) {
			Vue.set(ourNode.attributes, 'favorite', 1)
		}
	}

	/**
	 * Handler for when a node is removed from favorites
	 *
	 * @param node - The removed favorite
	 */
	function onRemoveFavorite(node: INode) {
		const ourNode = getNode(node.source)
		if (ourNode) {
			Vue.set(ourNode.attributes, 'favorite', 0)
		}
	}
})
