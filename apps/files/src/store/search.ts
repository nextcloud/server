/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'
import type RouterService from '../services/RouterService'
import type { SearchScope } from '../types'

import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import { emit } from '@nextcloud/event-bus'

export const useSearchStore = defineStore('search', () => {
	/**
	 * The current search query
	 */
	const query = ref('')

	/**
	 * Where to start the search
	 */
	const base = ref<INode>()

	/**
	 * Scope of the search.
	 * Scopes:
	 * - filter: only filter current file list
	 * - locally: search from current location recursivly
	 * - globally: search everywhere
	 */
	const scope = ref<SearchScope>('filter')

	watch(query, (old, current) => {
		// skip if only whitespaces changed
		if (old.trim() === current.trim()) {
			return
		}

		// emit the search event to update the filter
		emit('files:search:updated', { query: query.value, scope: scope.value })

		if (scope.value === 'filter') {
			// all done for filtering
			return
		}

		const router = window.OCP.Files.Router as RouterService
		if (query.value === '' && router.params.view === 'search') {
			return router.goToRoute(
				undefined,
				{
					view: 'files',
				},
				{
					...router.query,
					query: undefined,
				},
			)
		}

		// we only use the directory if we search locally
		const dir = scope.value === 'locally' ? base.value?.path : undefined
		const isSearch = router.params.view === 'search'

		router.goToRoute(
			undefined,
			{
				view: 'search',
			},
			{
				query: query.value,
				dir,
			},
			isSearch,
		)
	})

	return {
		base,
		query,
		scope,
	}
})
