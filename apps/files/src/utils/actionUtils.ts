/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ActionContextSingle, FileAction } from '@nextcloud/files'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { NodeStatus } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import Vue from 'vue'
import logger from '../logger.ts'
import { useActiveStore } from '../store/active.ts'

/**
 * Execute an action on the current active node
 *
 * @param action The action to execute
 */
export async function executeAction(action: FileAction) {
	const activeStore = useActiveStore()
	const currentFolder = activeStore.activeFolder
	const currentNode = activeStore.activeNode
	const currentView = activeStore.activeView

	if (!currentFolder || !currentNode || !currentView) {
		logger.error('No active folder, node or view', { folder: currentFolder, node: currentNode, view: currentView })
		return
	}

	if (currentNode.status === NodeStatus.LOADING) {
		logger.debug('Node is already loading', { node: currentNode })
		return
	}

	// @ts-expect-error _children is private
	const contents = currentFolder?._children || []
	const context = {
		nodes: [currentNode],
		view: currentView,
		folder: currentFolder,
		contents,
	} as ActionContextSingle

	if (!action.enabled!(context)) {
		logger.debug('Action is not not available for the current context', { action, node: currentNode, view: currentView })
		return
	}

	let displayName = action.id
	try {
		displayName = action.displayName(context)
	} catch (error) {
		logger.error('Error while getting action display name', { action, error })
	}

	try {
		// Set the loading marker
		Vue.set(currentNode, 'status', NodeStatus.LOADING)
		activeStore.activeAction = action

		const success = await action.exec(context)

		// If the action returns null, we stay silent
		if (success === null || success === undefined) {
			return
		}

		if (success) {
			showSuccess(t('files', '{displayName}: done', { displayName }))
			return
		}
		showError(t('files', '{displayName}: failed', { displayName }))
	} catch (error) {
		logger.error('Error while executing action', { action, error })
		showError(t('files', '{displayName}: failed', { displayName }))
	} finally {
		// Reset the loading marker
		Vue.set(currentNode, 'status', undefined)
		activeStore.activeAction = undefined
	}
}
