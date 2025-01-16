/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { dirname } from 'path'
import { useHotKey } from '@nextcloud/vue/dist/Composables/useHotKey.js'

import { action as deleteAction } from '../actions/deleteAction.ts'
import { action as favoriteAction } from '../actions/favoriteAction.ts'
import { action as renameAction } from '../actions/renameAction.ts'
import { action as sidebarAction } from '../actions/sidebarAction.ts'
import { executeAction } from '../utils/actionUtils.ts'
import { useUserConfigStore } from '../store/userconfig.ts'
import logger from '../logger.ts'

/**
 * This register the hotkeys for the Files app.
 * As much as possible, we try to have all the hotkeys in one place.
 * Please make sure to add tests for the hotkeys after adding a new one.
 */
export const registerHotkeys = function() {
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
}

const goToParentDir = function() {
	const params = window.OCP.Files.Router?.params || {}
	const query = window.OCP.Files.Router?.query || {}

	const currentDir = (query?.dir || '/') as string
	const parentDir = dirname(currentDir)

	logger.debug('Navigating to parent directory', { parentDir })
	window.OCP.Files.Router.goToRoute(
		null,
		{ ...params },
		{ ...query, dir: parentDir },
	)
}

const toggleGridView = function() {
	const userConfigStore = useUserConfigStore()
	const value = userConfigStore?.userConfig?.grid_view
	logger.debug('Toggling grid view', { old: value, new: !value })
	userConfigStore.update('grid_view', !value)
}
