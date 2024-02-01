/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
		},

		actions: {
			/**
			 * Update the view config local store
			 */
			onUpdate(view: ViewId, key: string, value: string | number | boolean) {
				if (!this.viewConfig[view]) {
					Vue.set(this.viewConfig, view, {})
				}
				Vue.set(this.viewConfig[view], key, value)
			},

			/**
			 * Update the view config local store AND on server side
			 */
			async update(view: ViewId, key: string, value: string | number | boolean) {
				axios.put(generateUrl(`/apps/files/api/v1/views/${view}/${key}`), {
					value,
				})

				emit('files:viewconfig:updated', { view, key, value })
			},

			/**
			 * Set the sorting key AND sort by ASC
			 * The key param must be a valid key of a File object
			 * If not found, will be searched within the File attributes
			 */
			setSortingBy(key = 'basename', view = 'files') {
				// Save new config
				this.update(view, 'sorting_mode', key)
				this.update(view, 'sorting_direction', 'asc')
			},

			/**
			 * Toggle the sorting direction
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
