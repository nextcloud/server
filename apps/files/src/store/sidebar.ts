/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode, ISidebarAction, ISidebarContext, ISidebarTab } from '@nextcloud/files'

import { subscribe } from '@nextcloud/event-bus'
import { getSidebarActions, getSidebarTabs } from '@nextcloud/files'
import { defineStore } from 'pinia'
import { computed, readonly, ref, watch } from 'vue'
import { getSidebarDataProvider } from '../sidebar/provider.ts'
import { logger } from '../utils/logger.ts'

export const useSidebarStore = defineStore('sidebar', () => {
	const activeTab = ref<string>()
	const isOpen = ref(false)
	const isFullScreen = ref(false)

	const currentNode = computed(() => isOpen.value ? getSidebarDataProvider()?.node.value : undefined)
	const hasContext = computed(() => currentNode.value !== undefined)
	const currentContext = computed<ISidebarContext | undefined>(() => {
		if (!currentNode.value) {
			return undefined
		}

		const provider = getSidebarDataProvider()
		return {
			node: currentNode.value,
			folder: provider?.folder.value,
			view: provider?.view.value,
		}
	})

	const currentActions = computed(() => currentContext.value ? getActions(currentContext.value) : [])
	const currentTabs = computed(() => currentContext.value ? getTabs(currentContext.value) : [])

	/**
	 * Open the sidebar for a given node and optional tab ID.
	 *
	 * @param node - The node to display in the sidebar.
	 * @param tabId - Optional ID of the tab to activate.
	 * @throws {Error} If the sidebar is not available on the current page.
	 */
	function open(node: INode, tabId?: string) {
		const provider = getSidebarDataProvider()
		if (!provider) {
			throw new Error('Cannot open the sidebar because it is not available on this page.')
		}

		if (isOpen.value && currentNode.value?.source === node.source) {
			logger.debug('sidebar: already open for current node')
			if (tabId) {
				logger.debug('sidebar: already open for current node - switching tab', { tabId })
				setActiveTab(tabId)
			}
			return
		}

		provider.setNode(node)
		setInitialTab(node, tabId)
		logger.debug(`sidebar: opening for ${node.displayname}`, { node })
		isOpen.value = true
	}

	/**
	 * Close the sidebar.
	 */
	function close() {
		isOpen.value = false
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
			tabs = tabs.filter((tab) => isEnabled(tab, context))
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
			actions = actions.filter((action) => isEnabled(action, context))
		}
		return actions.sort((a, b) => a.order - b.order)
	}

	/**
	 * Check whether a registered tab or action is enabled for a context.
	 * Entries which cannot handle the context are skipped,
	 * as the folder and the view of the context are only set within the files app
	 * while entries registered by other apps might expect both to be set.
	 *
	 * @param entry - The registered tab or action.
	 * @param context - The context to check the entry for.
	 */
	function isEnabled(entry: ISidebarTab | ISidebarAction, context: ISidebarContext): boolean {
		if (entry.enabled === undefined) {
			return true
		}

		try {
			return entry.enabled(context)
		} catch (error) {
			logger.error(`sidebar: '${entry.id}' could not be checked for the current context`, { error, entry })
			return false
		}
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

	/**
	 * Render the sidebar as a fullscreen overlay of the current page.
	 * Only meant for apps embedding the sidebar into their own app.
	 *
	 * @param fullScreen - Whether to render the sidebar fullscreen.
	 */
	function setFullScreenMode(fullScreen: boolean) {
		isFullScreen.value = fullScreen
	}

	/**
	 * Set the tab to show when the sidebar is opened for a node.
	 * Falls back to the first available tab if the requested one is not available.
	 *
	 * @param node - The node the sidebar was opened for.
	 * @param tabId - Optional ID of the requested tab.
	 */
	function setInitialTab(node: INode, tabId?: string) {
		const provider = getSidebarDataProvider()
		const tabs = getTabs({
			node,
			folder: provider?.folder.value,
			view: provider?.view.value,
		})

		if (tabId && !tabs.find(({ id }) => id === tabId)) {
			logger.warn(`sidebar: cannot open tab '${tabId}' because it is not available for the current context.`)
			activeTab.value = tabs[0]?.id
			return
		}

		activeTab.value = tabId ?? tabs[0]?.id
	}

	// update the current node if updated
	subscribe('files:node:updated', (node: INode) => {
		if (node.source === currentNode.value?.source) {
			getSidebarDataProvider()?.setNode(node)
		}
	})

	// close the sidebar if the current node is deleted
	subscribe('files:node:deleted', (node) => {
		if (node.id === currentNode.value?.id) {
			close()
		}
	})

	// let the current app synchronize its own state, like the current URL
	watch(isOpen, (isOpen) => {
		getSidebarDataProvider()?.onOpenStateChanged?.(isOpen)
	})

	return {
		activeTab,
		currentActions,
		currentContext,
		currentNode,
		currentTabs,
		hasContext,
		isFullScreen: readonly(isFullScreen),
		isOpen: readonly(isOpen),
		/** Alias of `currentNode` as expected by the `ISidebar` interface of `@nextcloud/files` */
		node: currentNode,

		open,
		close,
		getActions,
		getTabs,
		setActiveTab,
		setFullScreenMode,
	}
})
