/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mapState } from 'pinia'
import Vue from 'vue'
import { useActiveStore } from '../store/active.ts'
import { useViewConfigStore } from '../store/viewConfig.ts'

export default Vue.extend({
	setup() {
		const activeStore = useActiveStore()

		return {
			activeStore,
		}
	},

	computed: {
		...mapState(useViewConfigStore, ['getConfig', 'setSortingBy', 'toggleSortingDirection']),

		/**
		 * Get the sorting mode for the current view
		 */
		sortingMode(): string {
			return this.getConfig(this.activeStore.activeView?.id)?.sorting_mode as string
				|| this.activeStore.activeView?.defaultSortKey
				|| 'basename'
		},

		/**
		 * Get the sorting direction for the current view
		 */
		isAscSorting(): boolean {
			const sortingDirection = this.getConfig(this.activeStore.activeView?.id)?.sorting_direction
			return sortingDirection !== 'desc'
		},
	},

	methods: {
		toggleSortBy(key: string) {
			// If we're already sorting by this key, flip the direction
			if (this.sortingMode === key) {
				this.toggleSortingDirection(this.activeStore.activeView?.id)
				return
			}
			// else sort ASC by this new key
			this.setSortingBy(key, this.activeStore.activeView?.id)
		},
	},
})
