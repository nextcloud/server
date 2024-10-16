/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { defineStore } from 'pinia'
import { emit, subscribe } from '@nextcloud/event-bus'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'
import Vue from 'vue'

import type { ViewConfigs, ViewConfigStore, ViewId, ViewConfig } from '../types'

const viewConfig = loadState('files', 'viewConfigs', {}) as ViewConfigs

export const useViewConfigStore = function(...args) {
	const store = defineStore('viewconfig', {
		state: () => ({
			viewConfig,
		} as ViewConfigStore),

		getters: {
			getConfig: (state) => (view: ViewId): ViewConfig => state.viewConfig[view] || {},

			getConfigs: (state) => (): ViewConfigs => state.viewConfig,
		},

		actions: {
			/**
			 * Update the view config local store
			 * @param view
			 * @param key
			 * @param value
			 */
			onUpdate(view: ViewId, key: string, value: string | number | boolean) {
				if (!this.viewConfig[view]) {
					Vue.set(this.viewConfig, view, {})
				}
				Vue.set(this.viewConfig[view], key, value)
			},

			/**
			 * Update the view config local store AND on server side
			 * @param view
			 * @param key
			 * @param value
			 */
			async update(view: ViewId, key: string, value: string | number | boolean) {
				axios.put(generateUrl('/apps/files/api/v1/views'), {
					value,
					view,
					key,
				})

				emit('files:viewconfig:updated', { view, key, value })
			},

			/**
			 * Set the sorting key AND sort by ASC
			 * The key param must be a valid key of a File object
			 * If not found, will be searched within the File attributes
			 * @param key Key to sort by
			 * @param view View to set the sorting key for
			 */
			setSortingBy(key = 'basename', view = 'files') {
				// Save new config
				this.update(view, 'sorting_mode', key)
				this.update(view, 'sorting_direction', 'asc')
			},

			/**
			 * Toggle the sorting direction
			 * @param view view to set the sorting order for
			 */
			toggleSortingDirection(view = 'files') {
				const config = this.getConfig(view) || { sorting_direction: 'asc' }
				const newDirection = config.sorting_direction === 'asc' ? 'desc' : 'asc'

				// Save new config
				this.update(view, 'sorting_direction', newDirection)
			},
		},
	})

	const viewConfigStore = store(...args)

	// Make sure we only register the listeners once
	if (!viewConfigStore._initialized) {
		subscribe('files:viewconfig:updated', function({ view, key, value }: { view: ViewId, key: string, value: boolean }) {
			viewConfigStore.onUpdate(view, key, value)
		})
		viewConfigStore._initialized = true
	}

	return viewConfigStore
}
