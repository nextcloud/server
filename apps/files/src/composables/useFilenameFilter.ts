/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { registerFileListFilter, unregisterFileListFilter } from '@nextcloud/files'
import { watchThrottled } from '@vueuse/core'
import { onMounted, onUnmounted, ref } from 'vue'
import { FilenameFilter } from '../filters/FilenameFilter'

/**
 * This is for the `Navigation` component to provide a filename filter
 */
export function useFilenameFilter() {
	const searchQuery = ref('')
	const filenameFilter = new FilenameFilter()

	/**
	 * Updating the search query ref from the filter
	 * @param event The update:query event
	 */
	function updateQuery(event: CustomEvent) {
		if (event.type === 'update:query') {
			searchQuery.value = event.detail
			event.stopPropagation()
		}
	}

	onMounted(() => {
		filenameFilter.addEventListener('update:query', updateQuery)
		registerFileListFilter(filenameFilter)
	})
	onUnmounted(() => {
		filenameFilter.removeEventListener('update:query', updateQuery)
		unregisterFileListFilter(filenameFilter.id)
	})

	// Update the query on the filter, but throttle to max. every 800ms
	// This will debounce the filter refresh
	watchThrottled(searchQuery, () => {
		filenameFilter.updateQuery(searchQuery.value)
	}, { throttle: 800 })

	return {
		searchQuery,
	}
}
