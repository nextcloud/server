/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileAction, IFolder, INode, IView } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import { subscribe } from '@nextcloud/event-bus'
import { Folder, getNavigation, Permission } from '@nextcloud/files'
import { getRemoteURL, getRootPath } from '@nextcloud/files/dav'
import { defineStore } from 'pinia'
import { ref, shallowRef, watch } from 'vue'
import logger from '../logger.ts'

// Temporary fake folder to use until we have the first valid folder
// fetched and cached. This allow us to mount the FilesListVirtual
// at all time and avoid unmount/mount and undesired rendering issues.
const dummyFolder = new Folder({
	id: 0,
	source: getRemoteURL() + getRootPath(),
	root: getRootPath(),
	owner: getCurrentUser()?.uid || null,
	permissions: Permission.NONE,
})

export const useActiveStore = defineStore('active', () => {
	/**
	 * The currently active action
	 */
	const activeAction = shallowRef<IFileAction>()

	/**
	 * The current active node within the folder
	 */
	const activeNode = ref<INode>()

	/**
	 * The current active view
	 */
	const activeView = shallowRef<IView>()

	/**
	 * The currently active folder
	 */
	const activeFolder = ref<IFolder>(dummyFolder)

	// Set the active node on the router params
	watch(activeNode, () => {
		if (typeof activeNode.value?.fileid !== 'number' || activeNode.value.fileid === activeFolder.value?.fileid) {
			return
		}

		logger.debug('Updating active fileid in URL query', { fileid: activeNode.value.fileid })
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
