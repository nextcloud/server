/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { useHotKey } from '@nextcloud/vue/composables/useHotKey'
import { dirname } from 'path'
import { useRoute, useRouter } from 'vue-router/composables'
import { action as deleteAction } from '../actions/deleteAction.ts'
import { action as favoriteAction } from '../actions/favoriteAction.ts'
import { action as renameAction } from '../actions/renameAction.ts'
import { action as sidebarAction } from '../actions/sidebarAction.ts'
import logger from '../logger.ts'
import { useUserConfigStore } from '../store/userconfig.ts'
import { executeAction } from '../utils/actionUtils.ts'
import { useRouteParameters } from './useRouteParameters.ts'

/**
 * This register the hotkeys for the Files app.
 * As much as possible, we try to have all the hotkeys in one place.
 * Please make sure to add tests for the hotkeys after adding a new one.
 */
export function useHotKeys(): void {
	const userConfigStore = useUserConfigStore()
	const { directory } = useRouteParameters()
	const router = useRouter()
	const route = useRoute()

	// d opens the sidebar
	useHotKey('d', () => executeAction(sidebarAction), {
		stop: true,
		prevent: true,
	})

	// F2 renames the file
	useHotKey('F2', () => executeAction(renameAction), {
		stop: true,
		prevent: true,
	})

	// s toggle favorite
	useHotKey('s', () => executeAction(favoriteAction), {
		stop: true,
		prevent: true,
	})

	// Delete deletes the file
	useHotKey('Delete', () => executeAction(deleteAction), {
		stop: true,
		prevent: true,
	})

	// alt+up go to parent directory
	useHotKey('ArrowUp', goToParentDir, {
		stop: true,
		prevent: true,
		alt: true,
	})

	// v toggle grid view
	useHotKey('v', toggleGridView, {
		stop: true,
		prevent: true,
	})

	logger.debug('Hotkeys registered')

	/**
	 * Use the router to go to the parent directory
	 */
	function goToParentDir() {
		const dir = dirname(directory.value)

		logger.debug('Navigating to parent directory', { dir })
		router.push({ params: { ...route.params }, query: { ...route.query, dir } })
	}

	/**
	 * Toggle the grid view
	 */
	function toggleGridView() {
		const value = userConfigStore.userConfig.grid_view
		logger.debug('Toggling grid view', { old: value, new: !value })
		userConfigStore.update('grid_view', !value)
	}
}
