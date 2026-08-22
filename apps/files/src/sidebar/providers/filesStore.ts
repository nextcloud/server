/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'
import type { Pinia } from 'pinia'
import type { ISidebarDataProvider } from '../types.ts'

import { subscribe } from '@nextcloud/event-bus'
import { computed } from 'vue'
import { fetchNode } from '../../services/WebdavClient.ts'
import { useActiveStore } from '../../store/active.ts'
import { useFilesStore } from '../../store/files.ts'
import { getPinia } from '../../store/index.ts'
import { useSidebarStore } from '../../store/sidebar.ts'
import { logger } from '../../utils/logger.ts'

/**
 * Create the data provider used when the sidebar is rendered by the files app.
 *
 * The sidebar follows the state of the files app: it renders the active node
 * within the active folder and view, and keeps the current URL in sync.
 *
 * @param pinia - The pinia instance of the files app
 */
export function createFilesStoreDataProvider(pinia: Pinia = getPinia()): ISidebarDataProvider {
	const node = computed(() => useActiveStore(pinia).activeNode)
	const folder = computed(() => useActiveStore(pinia).activeFolder)
	const view = computed(() => useActiveStore(pinia).activeView)

	/**
	 * Make the node the active node of the files app.
	 *
	 * @param newNode - The node to render the sidebar for
	 */
	function setNode(newNode?: INode): void {
		const activeStore = useActiveStore(pinia)
		if (newNode && !activeStore.activeView) {
			logger.debug('sidebar: opening without an active view', { node: newNode })
		}

		activeStore.activeNode = newNode
	}

	/**
	 * Keep the `opendetails` query parameter in sync with the open state,
	 * so the sidebar can be restored when the URL is shared or reloaded.
	 *
	 * @param isOpen - The new open state
	 */
	function onOpenStateChanged(isOpen: boolean): void {
		const params = { ...(window.OCP?.Files?.Router?.params ?? {}) }
		const query = { ...(window.OCP?.Files?.Router?.query ?? {}) }

		logger.debug(`sidebar: ${isOpen ? 'opened' : 'closed'}`, { query, params })
		if (!isOpen && ('opendetails' in query)) {
			delete query.opendetails
			window.OCP.Files.Router.goToRoute(
				null,
				params,
				query,
				true,
			)
		}

		if (isOpen && !('opendetails' in query)) {
			window.OCP.Files.Router.goToRoute(
				null,
				params,
				{
					...query,
					opendetails: 'true',
				},
				true,
			)
		}
	}

	let routerHookRegistered = false
	// close the sidebar when the `opendetails` parameter is removed from the URL,
	// the router is only available once the files app is mounted
	subscribe('files:list:updated', () => {
		if (routerHookRegistered) {
			return
		}

		routerHookRegistered = true
		window.OCP.Files.Router._router.afterEach((to, from) => {
			if ((from.query && ('opendetails' in from.query))
				&& (to.query && !('opendetails' in to.query))) {
				logger.debug('sidebar: closing because "opendetails" query parameter was removed from URL.')
				useSidebarStore(pinia).close()
			}
		})
	})

	subscribe('viewer:sidebar:open', async ({ source, path }) => {
		logger.debug('sidebar: opening for node from Viewer.', { source })
		try {
			// Currently (as of Nextcloud 35) the viewer not fully uses the Node API
			// the emitted node is only partially filled, so we need to resolve it to a full node.
			const node = useFilesStore(pinia).getNode(source)
				?? await fetchNode(path)
			if (!node) {
				throw new Error(`Node '${source}' could not be resolved`)
			}

			useSidebarStore(pinia).open(node)
		} catch (error) {
			logger.error('sidebar: could not open for the node from the Viewer.', { error, source })
		}
	})

	return {
		node,
		folder,
		view,

		setNode,
		onOpenStateChanged,
	}
}
