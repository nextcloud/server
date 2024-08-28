/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { FileSource, SelectionStore } from '../types'
import { defineStore } from 'pinia'
import Vue from 'vue'

export const useSelectionStore = defineStore('selection', {
	state: () => ({
		selected: [],
		lastSelection: [],
		lastSelectedIndex: null,
	} as SelectionStore),

	actions: {
		/**
		 * Set the selection of fileIds
		 * @param selection
		 */
		set(selection = [] as FileSource[]) {
			Vue.set(this, 'selected', [...new Set(selection)])
		},

		/**
		 * Set the last selected index
		 * @param lastSelectedIndex
		 */
		setLastIndex(lastSelectedIndex = null as number | null) {
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
