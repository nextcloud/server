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
/* eslint-disable */
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import Vue from 'vue'
import axios from '@nextcloud/axios'
import type { direction, SortingStore } from '../types'

const saveUserConfig = (mode: string, direction: direction, view: string) => {
	return axios.post(generateUrl('/apps/files/api/v1/sorting'), {
		mode,
		direction,
		view,
	})
}

const filesSortingConfig = loadState('files', 'filesSortingConfig', {}) as SortingStore

export const useSortingStore = defineStore('sorting', {
	state: () => ({
		filesSortingConfig,
	}),

	getters: {
		isAscSorting: (state) => (view: string = 'files') => state.filesSortingConfig[view]?.direction !== 'desc',
		getSortingMode: (state) => (view: string = 'files') => state.filesSortingConfig[view]?.mode,
	},

	actions: {
		/**
		 * Set the sorting key AND sort by ASC
		 * The key param must be a valid key of a File object
		 * If not found, will be searched within the File attributes
		 */
		setSortingBy(key: string = 'basename', view: string = 'files') {
			const config = this.filesSortingConfig[view] || {}
			config.mode = key
			config.direction = 'asc'

			// Save new config
			Vue.set(this.filesSortingConfig, view, config)
			saveUserConfig(config.mode, config.direction, view)
		},

		/**
		 * Toggle the sorting direction
		 */
		toggleSortingDirection(view: string = 'files') {
			const config = this.filesSortingConfig[view] || { 'direction': 'asc' }
			const newDirection = config.direction === 'asc' ? 'desc' : 'asc'
			config.direction = newDirection

			// Save new config
			Vue.set(this.filesSortingConfig, view, config)
			saveUserConfig(config.mode, config.direction, view)
		}
	}
})

