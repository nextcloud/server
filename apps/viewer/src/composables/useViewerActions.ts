/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { IFile, IFileAction, IFolder, INode, IView } from '@nextcloud/files'
import type { MaybeRefOrGetter } from 'vue'

import { DefaultType, getFileActions } from '@nextcloud/files'
import { computed, toValue } from 'vue'
import { logger } from '../services/logger.ts'

// NOTE: the enabled-filtering + order-sorting below mirrors
// `useEnabledFileActions` in the Files app (apps/files/src/composables/useFileActions.ts).
// This logic is generic and should be consolidated into @nextcloud/files
// (e.g. a `getEnabledFileActions(context)` helper) so every consumer shares it;
// this composable would then become just the viewer-specific exclusions on top.

// The viewer's own open actions must never appear inside the viewer menu, and
// the sidebar is already offered by the dedicated "Open sidebar" button.
const EXCLUDED_ACTION_IDS = new Set(['details'])
const EXCLUDED_ACTION_PREFIXES = ['viewer-open']

/**
 * Expose the file actions available for the current file so they can be
 * rendered inside the viewer modal (download, delete, …). This links the
 * viewer menu to the Files actions (see nextcloud/viewer#7).
 *
 * A view and folder are required to build the action context; they are
 * forwarded through ViewerOptions from whatever opened the viewer. Without
 * them, no external actions are shown.
 *
 * @param file - The currently displayed file
 * @param contents - The files currently opened in the viewer
 * @param view - The files view the viewer was opened from
 * @param folder - The folder the files live in
 */
export function useViewerActions(
	file: MaybeRefOrGetter<IFile | undefined>,
	contents: MaybeRefOrGetter<IFile[]>,
	view: MaybeRefOrGetter<IView | undefined>,
	folder: MaybeRefOrGetter<IFolder | undefined>,
) {
	const context = computed(() => {
		const currentFile = toValue(file)
		const currentView = toValue(view)
		const currentFolder = toValue(folder)
		if (!currentFile || !currentView || !currentFolder) {
			return null
		}
		return {
			nodes: [currentFile] as [INode],
			view: currentView,
			folder: currentFolder,
			contents: toValue(contents) as INode[],
		}
	})

	// Every action (top-level and children) that is enabled and renderable in the
	// viewer menu, sorted by order.
	const enabledActions = computed<IFileAction[]>(() => {
		const ctx = context.value
		if (!ctx) {
			return []
		}

		return getFileActions()
			.filter((action) => !EXCLUDED_ACTION_IDS.has(action.id))
			.filter((action) => !EXCLUDED_ACTION_PREFIXES.some((prefix) => action.id.startsWith(prefix)))
			// Mirror the Files app menu: skip actions rendered by a custom
			// component (their static icon/label are empty) and hidden actions.
			.filter((action) => typeof action.renderInline !== 'function')
			.filter((action) => action.default !== DefaultType.HIDDEN)
			.filter((action) => {
				try {
					return action.enabled?.(ctx) ?? true
				} catch (error) {
					logger.error('Error while checking viewer action', { action: action.id, error })
					return false
				}
			})
			// A menu entry needs a label: drop actions whose displayName is empty
			// in the viewer context (e.g. inline actions that render their own UI).
			.filter((action) => {
				try {
					return action.displayName(ctx).trim() !== ''
				} catch (error) {
					logger.error('Error while resolving viewer action label', { action: action.id, error })
					return false
				}
			})
			.sort((a, b) => (a.order ?? 0) - (b.order ?? 0))
	})

	// Enabled children grouped by their parent action id (submenus, e.g. "Set reminder").
	const enabledSubmenuActions = computed<Record<string, IFileAction[]>>(() => {
		const record: Record<string, IFileAction[]> = {}
		for (const action of enabledActions.value) {
			if (action.parent) {
				(record[action.parent] ??= []).push(action)
			}
		}
		return record
	})

	// Top-level actions shown in the menu (children are reached through their parent).
	const actions = computed<IFileAction[]>(() => enabledActions.value.filter((action) => !action.parent))

	/**
	 * Whether an action opens a submenu (i.e. has at least one enabled child).
	 *
	 * @param action - The file action
	 */
	function isValidMenu(action: IFileAction): boolean {
		return (enabledSubmenuActions.value[action.id]?.length ?? 0) > 0
	}

	/**
	 * The translated label for a file action.
	 *
	 * @param action - The file action
	 */
	function actionLabel(action: IFileAction): string {
		return context.value ? action.displayName(context.value) : ''
	}

	/**
	 * The inline SVG icon for a file action.
	 *
	 * @param action - The file action
	 */
	function actionIcon(action: IFileAction): string {
		return context.value ? action.iconSvgInline(context.value) : ''
	}

	/**
	 * Execute a file action against the current file.
	 *
	 * @param action - The file action to run
	 */
	async function execAction(action: IFileAction): Promise<void> {
		if (!context.value) {
			return
		}
		try {
			await action.exec(context.value)
		} catch (error) {
			logger.error('Error while executing viewer action', { action: action.id, error })
		}
	}

	return { actions, enabledSubmenuActions, isValidMenu, actionLabel, actionIcon, execAction }
}
