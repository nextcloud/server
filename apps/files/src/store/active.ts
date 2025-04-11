/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ActiveStore } from '../types.ts'
import type { FileAction, Node, View } from '@nextcloud/files'

import { defineStore } from 'pinia'
import { getNavigation } from '@nextcloud/files'
import { subscribe } from '@nextcloud/event-bus'

import logger from '../logger.ts'

export const useActiveStore = function(...args) {
	const store = defineStore('active', {
		state: () => ({
			_initialized: false,
			activeNode: null,
			activeView: null,
			activeAction: null,
		} as ActiveStore),

		actions: {
			setActiveNode(node: Node) {
				if (!node) {
					throw new Error('Use clearActiveNode to clear the active node')
				}
				logger.debug('Setting active node', { node })
				this.activeNode = node
			},

			clearActiveNode() {
				this.activeNode = null
			},

			onDeletedNode(node: Node) {
				if (this.activeNode && this.activeNode.source === node.source) {
					this.clearActiveNode()
				}
			},

			setActiveAction(action: FileAction) {
				this.activeAction = action
			},

			clearActiveAction() {
				this.activeAction = null
			},

			onChangedView(view: View|null = null) {
				logger.debug('Setting active view', { view })
				this.activeView = view
				this.clearActiveNode()
			},
		},
	})

	const activeStore = store(...args)
	const navigation = getNavigation()

	// Make sure we only register the listeners once
	if (!activeStore._initialized) {
		subscribe('files:node:deleted', activeStore.onDeletedNode)

		activeStore._initialized = true
		activeStore.onChangedView(navigation.active)

		// Or you can react to changes of the current active view
		navigation.addEventListener('updateActive', (event) => {
			activeStore.onChangedView(event.detail)
		})
	}

	return activeStore
}
