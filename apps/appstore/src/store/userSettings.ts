/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { LocationQuery } from 'vue-router'

import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

export const useUserSettingsStore = defineStore('userSettings', () => {
	const defaultGridSize = ref('')

	const isGridView = ref(false)
	const showIncompatible = ref(true)

	const gridSizePx = computed(() => {
		if (defaultGridSize.value === 'm') {
			return '468px'
		} else if (defaultGridSize.value === 'l') {
			return '512px'
		}
		return '320px'
	})

	/**
	 * Get the query parameters for the current settings
	 *
	 * @param gridMode Optional override for the grid mode, if not provided it will use the current setting
	 */
	function getQuery(gridMode?: boolean) {
		const route = useRoute() ?? {}
		return {
			...route.query,
			grid: (gridMode ?? isGridView.value) ? (defaultGridSize.value || null) : undefined,
			compatible: showIncompatible.value ? undefined : null,
		}
	}

	const router = useRouter()
	router.afterEach((to) => {
		updateFromQuery(to.query)
	})

	return {
		defaultGridSize,
		gridSizePx,

		isGridView,
		showIncompatible,

		getQuery,
	}

	/**
	 * Initializes the store with the current query parameters
	 *
	 * @param query The query parameters to initialize the store with
	 */
	function updateFromQuery(query: LocationQuery) {
		isGridView.value = 'grid' in query
		defaultGridSize.value = [query.grid ?? ''].flat()[0]!.toLowerCase()
		showIncompatible.value = !('compatible' in query)
	}
})
