/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { FileAction } from '@nextcloud/files'

import { NodeStatus } from '@nextcloud/files'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import Vue from 'vue'

import { getPinia } from '../store'
import { useActiveStore } from '../store/active'
import logger from '../logger'

/**
 * Execute an action on the current active node
 *
 * @param action The action to execute
 */
export const executeAction = async (action: FileAction) => {
	const activeStore = useActiveStore(getPinia())
	const currentDir = (window?.OCP?.Files?.Router?.query?.dir || '/') as string
	const currentNode = activeStore.activeNode
	const currentView = activeStore.activeView

	if (!currentNode || !currentView) {
		logger.error('No active node or view', { node: currentNode, view: currentView })
		return
	}

	if (currentNode.status === NodeStatus.LOADING) {
		logger.debug('Node is already loading', { node: currentNode })
		return
	}

	if (!action.enabled!([currentNode], currentView)) {
		logger.debug('Action is not not available for the current context', { action, node: currentNode, view: currentView })
		return
	}

	let displayName = action.id
	try {
		displayName = action.displayName([currentNode], currentView)
	} catch (error) {
		logger.error('Error while getting action display name', { action, error })
	}

	try {
		// Set the loading marker
		Vue.set(currentNode, 'status', NodeStatus.LOADING)
		activeStore.setActiveAction(action)

		const success = await action.exec(currentNode, currentView, currentDir)

		// If the action returns null, we stay silent
		if (success === null || success === undefined) {
			return
		}

		if (success) {
			showSuccess(t('files', '"{displayName}" action executed successfully', { displayName }))
			return
		}
		showError(t('files', '"{displayName}" action failed', { displayName }))
	} catch (error) {
		logger.error('Error while executing action', { action, error })
		showError(t('files', '"{displayName}" action failed', { displayName }))
	} finally {
		// Reset the loading marker
		Vue.set(currentNode, 'status', undefined)
		activeStore.clearActiveAction()
	}
}
