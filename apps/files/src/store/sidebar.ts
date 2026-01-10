/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode, ISidebarContext } from '@nextcloud/files'

import { subscribe } from '@nextcloud/event-bus'
import { getSidebarActions, getSidebarTabs } from '@nextcloud/files'
import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import logger from '../logger.ts'
import { useActiveStore } from './active.ts'
import { useFilesStore } from './files.ts'

export const useSidebarStore = defineStore('sidebar', () => {
	const activeTab = ref<string>()
	const currentNode = ref<INode>()
	const isOpen = computed(() => !!currentNode.value)

	const activeStore = useActiveStore()
	const hasContext = computed(() => !!(currentNode.value && activeStore.activeFolder && activeStore.activeView))
	const currentContext = computed<ISidebarContext | undefined>(() => {
		if (!hasContext.value) {
			return
		}
		return {
			node: currentNode.value!,
			folder: activeStore.activeFolder!,
			view: activeStore.activeView!,
		}
	})

	const currentActions = computed(() => currentContext.value ? getActions(currentContext.value) : [])
	const currentTabs = computed(() => currentContext.value ? getTabs(currentContext.value) : [])

	/**
	 * Open the sidebar for a given node and optional tab ID.
	 *
	 * @param node - The node to display in the sidebar.
	 * @param tabId - Optional ID of the tab to activate.
	 */
	function open(node: INode, tabId?: string) {
		const activeStore = useActiveStore()
		if (!(node && activeStore.activeFolder && activeStore.activeView)) {
			logger.debug('Cannot open sidebar because the active folder or view is not set.', {
				node,
				activeFolder: activeStore.activeFolder,
				activeView: activeStore.activeView,
			})

			throw new Error('Cannot open sidebar because the active folder or view is not set.')
		}

		const newTabs = getTabs({
			node,
			folder: activeStore.activeFolder,
			view: activeStore.activeView,
		})

		if (tabId && !newTabs.find(({ id }) => id === tabId)) {
			logger.warn(`Cannot open sidebar tab '${tabId}' because it is not available for the current context.`)
			activeTab.value = newTabs[0]?.id
		} else {
			activeTab.value = tabId ?? newTabs[0]?.id
		}
		currentNode.value = node
	}

	/**
	 * Close the sidebar.
	 */
	function close() {
		currentNode.value = undefined
	}

	/**
	 * Get the available tabs for the sidebar.
	 * If a context is provided, only tabs enabled for that context are returned.
	 *
	 * @param context - Optional context to filter the available tabs.
	 */
	function getTabs(context?: ISidebarContext) {
		let tabs = getSidebarTabs()
		if (context) {
			tabs = tabs.filter((tab) => tab.enabled(context))
		}
		return tabs.sort((a, b) => a.order - b.order)
	}

	/**
	 * Get the available actions for the sidebar.
	 * If a context is provided, only actions enabled for that context are returned.
	 *
	 * @param context - Optional context to filter the available actions.
	 */
	function getActions(context?: ISidebarContext) {
		let actions = getSidebarActions()
		if (context) {
			actions = actions.filter((tab) => tab.enabled(context))
		}
		return actions.sort((a, b) => a.order - b.order)
	}

	/**
	 * Set the active tab in the sidebar.
	 *
	 * @param tabId - The ID of the tab to activate.
	 */
	function setActiveTab(tabId: string) {
		if (!currentTabs.value.find(({ id }) => id === tabId)) {
			throw new Error(`Cannot set sidebar tab '${tabId}' because it is not available for the current context.`)
		}
		activeTab.value = tabId
	}

	// update the current node if updated
	subscribe('files:node:updated', (node: INode) => {
		if (node.source === currentNode.value?.source) {
			currentNode.value = node
		}
	})

	// close the sidebar if the current node is deleted
	subscribe('files:node:deleted', (node) => {
		if (node.fileid === currentNode.value?.fileid) {
			close()
		}
	})

	subscribe('viewer:sidebar:open', ({ source }) => {
		const filesStore = useFilesStore()
		const node = filesStore.getNode(source)
		if (node) {
			logger.debug('Opening sidebar for node from Viewer.', { node })
			open(node)
		} else {
			logger.error(`Cannot open sidebar for node '${source}' because it was not found in the current view.`)
		}
	})

	let initialized = false
	// close sidebar when parameter is removed from url
	subscribe('files:list:updated', () => {
		if (!initialized) {
			initialized = true
			window.OCP.Files.Router._router.afterEach((to) => {
				if (to.query && !('opendetails' in to.query)) {
					close()
				}
			})
		}
	})

	// watch open state and update URL query parameters
	watch(currentNode, (node) => {
		const query = { ...(window.OCP?.Files?.Router?.query ?? {}) }

		if (!node && 'opendetails' in query) {
			delete query.opendetails
			window.OCP.Files.Router.goToRoute(
				null,
				{ ...window.OCP.Files.Router.params },
				{
					...query,
				},
				true,
			)
		}

		if (node) {
			const fileid = String(node.fileid)
			if (!('opendetails' in query) || window.OCP.Files.Router.params.fileid !== fileid) {
				window.OCP.Files.Router.goToRoute(
					null,
					{
						...window.OCP.Files.Router.params,
						fileid,
					},
					{
						...query,
						opendetails: 'true',
					},
					true,
				)
			}
		}
	})

	return {
		activeTab,
		currentActions,
		currentContext,
		currentNode,
		currentTabs,
		hasContext,
		isOpen,

		open,
		close,
		getActions,
		getTabs,
		setActiveTab,
	}
})
