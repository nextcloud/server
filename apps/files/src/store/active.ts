/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { FileAction, IFolder, INode, IView } from '@nextcloud/files'

import { subscribe } from '@nextcloud/event-bus'
import { getNavigation } from '@nextcloud/files'
import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import logger from '../logger.ts'

export const useActiveStore = defineStore('active', () => {
	/**
	 * The currently active action
	 */
	const activeAction = ref<FileAction>()

	/**
	 * The currently active folder
	 */
	const activeFolder = ref<IFolder>()

	/**
	 * The current active node within the folder
	 */
	const activeNode = ref<INode>()

	/**
	 * The current active view
	 */
	const activeView = ref<IView>()

	// Set the active node on the router params
	watch(activeNode, () => {
		if (!activeNode.value?.fileid || activeNode.value.fileid === activeFolder.value?.fileid) {
			return
		}

		window.OCP.Files.Router.goToRoute(
			null,
			{ ...window.OCP.Files.Router.params, fileid: String(activeNode.value.fileid) },
			{ ...window.OCP.Files.Router.query },
			true,
		)
	})

	initialize()

	/**
	 * Unset the active node if deleted
	 *
	 * @param node - The node thats deleted
	 */
	function onDeletedNode(node: INode) {
		if (activeNode.value && activeNode.value.source === node.source) {
			activeNode.value = undefined
		}
	}

	/**
	 * Callback to update the current active view
	 *
	 * @param view - The new active view
	 */
	function onChangedView(view: IView | null = null) {
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
		onChangedView(navigation.active)

		// Make sure we only register the listeners once
		subscribe('files:node:deleted', onDeletedNode)
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
