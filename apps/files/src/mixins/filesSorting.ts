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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import Vue from 'vue'

import { useViewConfigStore } from '../store/viewConfig'
import type { Navigation  } from '../services/Navigation'

export default Vue.extend({
	setup() {
		const viewConfigStore = useViewConfigStore()
		return {
			viewConfigStore,
		}
	},

	computed: {
		currentView(): Navigation {
			return this.$navigation.active
		},

		/**
		 * Get the sorting mode for the current view
		 */
		sortingMode(): string {
			return this.viewConfigStore.getConfig(this.currentView.id)?.sorting_mode
				|| this.currentView?.defaultSortKey
				|| 'basename'
		},

		/**
		 * Get the sorting direction for the current view
		 */
		isAscSorting(): boolean {
			const sortingDirection = this.viewConfigStore.getConfig(this.currentView.id)?.sorting_direction
			return sortingDirection === 'asc'
		},
	},

	methods: {
		toggleSortBy(key: string) {
			// If we're already sorting by this key, flip the direction
			if (this.sortingMode === key) {
				this.viewConfigStore.toggleSortingDirection(this.currentView.id)
				return
			}
			// else sort ASC by this new key
			this.viewConfigStore.setSortingBy(key, this.currentView.id)
		},
	},
})
