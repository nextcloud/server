/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { FileAction, Folder, Node, View } from '@nextcloud/files'

import { subscribe } from '@nextcloud/event-bus'
import { getNavigation } from '@nextcloud/files'
import { defineStore } from 'pinia'
import { ref } from 'vue'
import logger from '../logger.ts'

export const useActiveStore = defineStore('active', () => {
	/**
	 * The currently active action
	 */
	const activeAction = ref<FileAction>()

	/**
	 * The currently active folder
	 */
	const activeFolder = ref<Folder>()

	/**
	 * The current active node within the folder
	 */
	const activeNode = ref<Node>()

	/**
	 * The current active view
	 */
	const activeView = ref<View>()

	initialize()

	/**
	 * Unset the active node if deleted
	 *
	 * @param node - The node thats deleted
	 */
	function onDeletedNode(node: Node) {
		if (activeNode.value && activeNode.value.source === node.source) {
			activeNode.value = undefined
		}
	}

	/**
	 * Callback to update the current active view
	 *
	 * @param view - The new active view
	 */
	function onChangedView(view: View | null = null) {
		logger.debug('Setting active view', { view })
		activeView.value = view ?? undefined
		activeNode.value = undefined
	}

	/**
	 * Initalize the store - connect all event listeners.
	 *
	 */
	function initialize() {
		const navigation = getNavigation()

		// Make sure we only register the listeners once
		subscribe('files:node:deleted', onDeletedNode)

		onChangedView(navigation.active)

		// Or you can react to changes of the current active view
		navigation.addEventListener('updateActive', (event) => {
			onChangedView(event.detail)
		})
	}

	return {
		activeAction,
		activeFolder,
		activeNode,
		activeView,
	}
})
