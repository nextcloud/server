/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ActionContextSingle, IFileAction } from '@nextcloud/files'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { NodeStatus } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import Vue from 'vue'
import { useActiveStore } from '../store/active.ts'
import { logger } from '../utils/logger.ts'

/**
 * How the execution of an action was triggered.
 * `hotkey` means the action was triggered by a keyboard shortcut,
 * `menu` means it was triggered from the actions menu or an inline action button.
 */
export type ActionTrigger = 'hotkey' | 'menu'

/**
 * Action context enriched with the trigger that started the execution.
 * The trigger is undefined if the action was executed programmatically.
 */
export type TriggeredActionContext = ActionContextSingle & { trigger?: ActionTrigger }

/**
 * Execute an action on the current active node
 *
 * @param action The action to execute
 * @param trigger How the execution was triggered
 */
export async function executeAction(action: IFileAction, trigger?: ActionTrigger) {
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
		trigger,
	} as TriggeredActionContext

	if (!action.enabled!(context)) {
		logger.debug('Action is not not available for the current context', { action, node: currentNode, view: currentView })
		return
	}

	let displayName = action.id
	try {
		displayName = action.displayName(context) || displayName
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
