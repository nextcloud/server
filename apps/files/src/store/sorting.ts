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

type direction = 'asc' | 'desc'

const saveUserConfig = (key: string, direction: direction) => {
	return axios.post(generateUrl('/apps/files/api/v1/sorting'), {
		mode: key,
		direction: direction as string,
	})
}

const defaultFileSorting = loadState('files', 'defaultFileSorting', 'basename')
const defaultFileSortingDirection = loadState('files', 'defaultFileSortingDirection', 'asc') as direction

export const useSortingStore = defineStore('sorting', {
	state: () => ({
		defaultFileSorting,
		defaultFileSortingDirection,
	}),

	getters: {
		isAscSorting: (state) => state.defaultFileSortingDirection === 'asc',
	},

	actions: {
		/**
		 * Set the sorting key AND sort by ASC
		 * The key param must be a valid key of a File object
		 * If not found, will be searched within the File attributes
		 */
		setSortingBy(key: string) {
			Vue.set(this, 'defaultFileSorting', key)
			Vue.set(this, 'defaultFileSortingDirection', 'asc')
			saveUserConfig(key, 'asc')
		},

		/**
		 * Toggle the sorting direction
		 */
		toggleSortingDirection() {
			const newDirection = this.defaultFileSortingDirection === 'asc' ? 'desc' : 'asc'
			Vue.set(this, 'defaultFileSortingDirection', newDirection)
			saveUserConfig(this.defaultFileSorting, newDirection)
		}
	}
})

