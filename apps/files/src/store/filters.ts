/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { FilterUpdateChipsEvent, IFileListFilter, IFileListFilterChip } from '@nextcloud/files'
import { subscribe } from '@nextcloud/event-bus'
import { getFileListFilters } from '@nextcloud/files'
import { defineStore } from 'pinia'
import logger from '../logger'

export const useFiltersStore = defineStore('filters', {
	state: () => ({
		chips: {} as Record<string, IFileListFilterChip[]>,
		filters: [] as IFileListFilter[],
		filtersChanged: false,
	}),

	getters: {
		/**
		 * Currently active filter chips
		 * @param state Internal state
		 */
		activeChips(state): IFileListFilterChip[] {
			return Object.values(state.chips).flat()
		},

		/**
		 * Filters sorted by order
		 * @param state Internal state
		 */
		sortedFilters(state): IFileListFilter[] {
			return state.filters.sort((a, b) => a.order - b.order)
		},

		/**
		 * All filters that provide a UI for visual controlling the filter state
		 */
		filtersWithUI(): Required<IFileListFilter>[] {
			return this.sortedFilters.filter((filter) => 'mount' in filter) as Required<IFileListFilter>[]
		},
	},

	actions: {
		addFilter(filter: IFileListFilter) {
			filter.addEventListener('update:chips', this.onFilterUpdateChips)
			filter.addEventListener('update:filter', this.onFilterUpdate)
			this.filters.push(filter)
			logger.debug('New file list filter registered', { id: filter.id })
		},

		removeFilter(filterId: string) {
			const index = this.filters.findIndex(({ id }) => id === filterId)
			if (index > -1) {
				const [filter] = this.filters.splice(index, 1)
				filter.removeEventListener('update:chips', this.onFilterUpdateChips)
				filter.removeEventListener('update:filter', this.onFilterUpdate)
				logger.debug('Files list filter unregistered', { id: filterId })
			}
		},

		onFilterUpdate() {
			this.filtersChanged = true
		},

		onFilterUpdateChips(event: FilterUpdateChipsEvent) {
			const id = (event.target as IFileListFilter).id
			this.chips = { ...this.chips, [id]: [...event.detail] }

			logger.debug('File list filter chips updated', { filter: id, chips: event.detail })
		},

		init() {
			subscribe('files:filter:added', this.addFilter)
			subscribe('files:filter:removed', this.removeFilter)
			for (const filter of getFileListFilters()) {
				this.addFilter(filter)
			}
		},
	},
})
