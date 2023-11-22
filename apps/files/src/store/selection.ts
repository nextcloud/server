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
import Vue from 'vue'
import { FileId, SelectionStore } from '../types'

export const useSelectionStore = defineStore('selection', {
	state: () => ({
		selected: [],
		lastSelection: [],
		lastSelectedIndex: null,
	} as SelectionStore),

	actions: {
		/**
		 * Set the selection of fileIds
		 */
		set(selection = [] as FileId[]) {
			Vue.set(this, 'selected', [...new Set(selection)])
		},

		/**
		 * Set the last selected index
		 */
		setLastIndex(lastSelectedIndex = null as FileId | null) {
			// Update the last selection if we provided a new selection starting point
			Vue.set(this, 'lastSelection', lastSelectedIndex ? this.selected : [])
			Vue.set(this, 'lastSelectedIndex', lastSelectedIndex)
		},

		/**
		 * Reset the selection
		 */
		reset() {
			Vue.set(this, 'selected', [])
			Vue.set(this, 'lastSelection', [])
			Vue.set(this, 'lastSelectedIndex', null)
		},
	},
})
