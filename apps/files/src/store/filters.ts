/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { FilterUpdateChipsEvent, IFileListFilter, IFileListFilterChip } from '@nextcloud/files'
import { subscribe } from '@nextcloud/event-bus'
import { getFileListFilters } from '@nextcloud/files'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import logger from '../logger'

/**
 * Check if the given value is an instance file list filter with mount function
 * @param value The filter to check
 */
function isFileListFilterWithUi(value: IFileListFilter): value is Required<IFileListFilter> {
	return 'mount' in value
}

export const useFiltersStore = defineStore('filters', () => {
	const chips = ref<Record<string, IFileListFilterChip[]>>({})
	const filters = ref<IFileListFilter[]>([])
	const filtersChanged = ref(false)

	/**
	 * Currently active filter chips
	 */
	const activeChips = computed<IFileListFilterChip[]>(
		() => Object.values(chips.value).flat(),
	)

	/**
	 * Filters sorted by order
	 */
	const sortedFilters = computed<IFileListFilter[]>(
		() => filters.value.sort((a, b) => a.order - b.order),
	)

	/**
	 * All filters that provide a UI for visual controlling the filter state
	 */
	const filtersWithUI = computed<Required<IFileListFilter>[]>(
		() => sortedFilters.value.filter(isFileListFilterWithUi)
	)

	/**
	 * Register a new filter on the store.
	 * This will subscribe the store to the filters events.
	 *
	 * @param filter The filter to add
	 */
	function addFilter(filter: IFileListFilter) {
		filter.addEventListener('update:chips', onFilterUpdateChips)
		filter.addEventListener('update:filter', onFilterUpdate)

		filters.value.push(filter)
		logger.debug('New file list filter registered', { id: filter.id })
	}

	/**
	 * Unregister a filter from the store.
	 * This will remove the filter from the store and unsubscribe the store from the filer events.
	 * @param filterId Id of the filter to remove
	 */
	function removeFilter(filterId: string) {
		const index = filters.value.findIndex(({ id }) => id === filterId)
		if (index > -1) {
			const [filter] = filters.value.splice(index, 1)
			filter.removeEventListener('update:chips', onFilterUpdateChips)
			filter.removeEventListener('update:filter', onFilterUpdate)
			logger.debug('Files list filter unregistered', { id: filterId })
		}
	}

	/**
	 * Event handler for filter update events
	 * @private
	 */
	function onFilterUpdate() {
		filtersChanged.value = true
	}

	/**
	 * Event handler for filter chips updates
	 * @param event The update event
	 * @private
	 */
	function onFilterUpdateChips(event: FilterUpdateChipsEvent) {
		const id = (event.target as IFileListFilter).id
		chips.value = {
			...chips.value,
			[id]: [...event.detail],
		}

		logger.debug('File list filter chips updated', { filter: id, chips: event.detail })
	}

	/**
	 * Event handler that resets all filters if the file list view was changed.
	 * @private
	 */
	function onViewChanged() {
		logger.debug('Reset all file list filters - view changed')

		for (const filter of filters.value) {
			if (filter.reset !== undefined) {
				filter.reset()
			}
		}
	}

	// Initialize the store
	subscribe('files:navigation:changed', onViewChanged)
	subscribe('files:filter:added', addFilter)
	subscribe('files:filter:removed', removeFilter)
	for (const filter of getFileListFilters()) {
		addFilter(filter)
	}

	return {
		// state
		chips,
		filters,
		filtersWithUI,
		filtersChanged,

		// getters / computed
		activeChips,
		sortedFilters,

		// actions / methods
		addFilter,
		removeFilter,
	}
})
