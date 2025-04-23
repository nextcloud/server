/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue, { computed } from 'vue'

import { mapState } from 'pinia'
import { useViewConfigStore } from '../store/viewConfig'
import { useNavigation } from '../composables/useNavigation'
import { FilesSortingMode, sortNodes as filesSortNodes, type INode } from '@nextcloud/files'
import { useUserConfigStore } from '../store/userconfig'

export function useFileSorting() {
	const { currentView } = useNavigation(true)
	const viewConfigStore = useViewConfigStore()
	const userConfigStore = useUserConfigStore()

	/**
	 * Get the sorting mode for the current view
	 */
	const sortingMode = computed(() => {
		return viewConfigStore.getConfig(currentView.value.id)?.sorting_mode as string
			|| currentView.value.defaultSortKey
			|| 'basename'
	})

	/**
	 * Get the sorting direction for the current view
	 */
	const isAscSorting = computed(() => {
		const sortingDirection = viewConfigStore.getConfig(currentView.value.id)?.sorting_direction
		return sortingDirection !== 'desc'
	})

	/**
	 * Set the sorting mode to the given key.
	 * If it is already set as the sorting mode then the direction will be flipped.
	 *
	 * @param key - The sorting key
	 */
	function toggleSortBy(key: string) {
		if (sortingMode.value === key) {
			// If we're already sorting by this key, flip the direction
			viewConfigStore.toggleSortingDirection(currentView.value.id)
		} else {
			// else sort ASC by this new key
			viewConfigStore.setSortingBy(key, currentView.value.id)
		}
	}

	/**
	 * Sort nodes based on current view and user config.
	 *
	 * @param nodes - Nodes to sort
	 * @returns Sorted array of nodes
	 */
	function sortNodes(nodes: INode[]): INode[] {
		const customColumn = (currentView.value.columns || [])
			.find((column) => column.id === sortingMode.value)

		// Custom column must provide their own sorting methods
		if (customColumn?.sort && typeof customColumn.sort === 'function') {
			// @ts-expect-error class vs interface
			const results = nodes.sort(customColumn.sort)
			return isAscSorting.value ? results : results.reverse()
		}

		return filesSortNodes(nodes, {
			sortFavoritesFirst: userConfigStore.userConfig.sort_favorites_first,
			sortFoldersFirst: userConfigStore.userConfig.sort_folders_first,
			sortingMode: sortingMode.value as FilesSortingMode,
			sortingOrder: isAscSorting.value ? 'asc' : 'desc',
		})
	}

	return {
		isAscSorting,
		sortingMode,

		sortNodes,
		toggleSortBy,
	}
})
