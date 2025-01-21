/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'

import { mapState } from 'pinia'
import { useViewConfigStore } from '../store/viewConfig'
import { useNavigation } from '../composables/useNavigation'

export default Vue.extend({
	setup() {
		const { currentView } = useNavigation()

		return {
			currentView,
		}
	},

	computed: {
		...mapState(useViewConfigStore, ['getConfig', 'setSortingBy', 'toggleSortingDirection']),

		/**
		 * Get the sorting mode for the current view
		 */
		sortingMode(): string {
			return this.getConfig(this.currentView.id)?.sorting_mode as string
				|| this.currentView?.defaultSortKey
				|| 'basename'
		},

		/**
		 * Get the sorting direction for the current view
		 */
		isAscSorting(): boolean {
			const sortingDirection = this.getConfig(this.currentView.id)?.sorting_direction
			return sortingDirection !== 'desc'
		},
	},

	methods: {
		toggleSortBy(key: string) {
			// If we're already sorting by this key, flip the direction
			if (this.sortingMode === key) {
				this.toggleSortingDirection(this.currentView.id)
				return
			}
			// else sort ASC by this new key
			this.setSortingBy(key, this.currentView.id)
		},
	},
})
