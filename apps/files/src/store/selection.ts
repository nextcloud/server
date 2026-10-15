/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { FileSource, SelectionStore } from '../types.ts'

import { defineStore } from 'pinia'

export const useSelectionStore = defineStore('selection', {
	state: () => ({
		selected: [],
		lastSelection: [],
		lastSelectedIndex: null,
	} as SelectionStore),

	actions: {
		/**
		 * Set the selection of fileIds
		 *
		 * @param selection
		 */
		set(selection = [] as FileSource[]) {
			this.selected = [...new Set(selection)]
		},

		/**
		 * Set the last selected index
		 *
		 * @param lastSelectedIndex
		 */
		setLastIndex(lastSelectedIndex = null as number | null) {
			// Update the last selection if we provided a new selection starting point
			this.lastSelection = lastSelectedIndex ? this.selected : []
			this.lastSelectedIndex = lastSelectedIndex
		},

		/**
		 * Reset the selection
		 */
		reset() {
			this.selected = []
			this.lastSelection = []
			this.lastSelectedIndex = null
		},
	},
})
