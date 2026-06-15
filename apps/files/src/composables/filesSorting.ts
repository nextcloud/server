/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { computed } from 'vue'
import { useActiveStore } from '../store/active.ts'
import { useViewConfigStore } from '../store/viewConfig.ts'

/**
 * Composable to get and set the sorting mode and direction for the current view.
 */
export function useFilesSorting() {
	const activeStore = useActiveStore()
	const viewConfigStore = useViewConfigStore()

	/**
	 * Get the sorting mode for the current view
	 */
	const sortingMode = computed(() => {
		if (!activeStore.activeView) {
			return 'basename'
		}

		return viewConfigStore.getConfig(activeStore.activeView.id)?.sorting_mode as string
			|| activeStore.activeView?.defaultSortKey
			|| 'basename'
	})

	/**
	 * Get the sorting direction for the current view
	 */
	const isAscSorting = computed(() => {
		if (!activeStore.activeView) {
			return true
		}

		const sortingDirection = viewConfigStore.getConfig(activeStore.activeView.id)?.sorting_direction
		return sortingDirection !== 'desc'
	})

	/**
	 * Toogle sorting by a given key.
	 *
	 * If we're already sorting by this key, toggle the direction.
	 * Otherwise, sort ASC by this key.
	 *
	 * @param key - The key to sort by
	 */
	function toggleSortBy(key: string) {
		// If we're already sorting by this key, flip the direction
		if (sortingMode.value === key) {
			viewConfigStore.toggleSortingDirection(activeStore.activeView?.id)
			return
		}
		// else sort ASC by this new key
		viewConfigStore.setSortingBy(key, activeStore.activeView?.id)
	}

	return {
		toggleSortBy,
		sortingMode,
		isAscSorting,
	}
}
