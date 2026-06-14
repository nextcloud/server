/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { DragAndDropStore, FileSource } from '../types.ts'

import { defineStore } from 'pinia'

export const useDragAndDropStore = defineStore('dragging', {
	state: () => ({
		dragging: [],
	} as DragAndDropStore),

	actions: {
		/**
		 * Set the selection of files being dragged currently
		 *
		 * @param selection array of node sources
		 */
		set(selection = [] as FileSource[]) {
			this.dragging = selection
		},

		/**
		 * Reset the selection
		 */
		reset() {
			this.dragging = []
		},
	},
})
