/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { FileSource } from '../types'
import { defineStore } from 'pinia'
import Vue from 'vue'

export const useDragAndDropStore = defineStore('dragging', {
	state: () => ({
		dragging: [] as string[],
	}),

	getters: {
		/**
		 * Is the user currently dragging files
		 */
		isDragging(): boolean {
			return this.dragging.length > 0
		},
	},

	actions: {
		/**
		 * Set the selection of fileIds
		 * @param selection
		 */
		set(selection = [] as FileSource[]) {
			Vue.set(this, 'dragging', selection)
		},

		/**
		 * Reset the selection
		 */
		reset() {
			Vue.set(this, 'dragging', [])
		},
	},
})
