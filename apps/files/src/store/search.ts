/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode, View } from '@nextcloud/files'
import type RouterService from '../services/RouterService'
import type { SearchScope } from '../types'

import { emit, subscribe } from '@nextcloud/event-bus'
import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import { VIEW_ID } from '../views/search'
import logger from '../logger'
import debounce from 'debounce'

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

	// reset the base if query is cleared
	watch(scope, () => {
		if (scope.value !== 'locally') {
			base.value = undefined
		}

		updateSearch()
	})

	watch(query, (old, current) => {
		// skip if only whitespaces changed
		if (old.trim() === current.trim()) {
			return
		}

		updateSearch()
	})

	// initialize the search store
	initialize()

	/**
	 * Debounced update of the current route
	 * @private
	 */
	const updateRouter = debounce((isSearch: boolean, fileid?: number) => {
		const router = window.OCP.Files.Router as RouterService
		router.goToRoute(
			undefined,
			{
				view: VIEW_ID,
				...(fileid === undefined ? {} : { fileid: String(fileid) }),
			},
			{
				query: query.value,
			},
			isSearch,
		)
	})

	/**
	 * Handle updating the filter if needed.
	 * Also update the search view by updating the current route if needed.
	 *
	 * @private
	 */
	function updateSearch() {
		// emit the search event to update the filter
		emit('files:search:updated', { query: query.value, scope: scope.value })

		const router = window.OCP.Files.Router as RouterService

		// if we are on the search view and the query was unset or scope was set to 'filter' we need to move back to the files view
		if (router.params.view === VIEW_ID && (query.value === '' || scope.value === 'filter')) {
			scope.value = 'filter'
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

		// for the filter scope we do not need to adjust the current route anymore
		// also if the query is empty we do not need to do anything
		if (scope.value === 'filter' || !query.value) {
			return
		}

		// we only use the directory if we search locally
		const fileid = scope.value === 'locally' ? base.value?.fileid : undefined
		const isSearch = router.params.view === VIEW_ID

		logger.debug('Update route for updated search query', { query: query.value, fileid, isSearch })
		updateRouter(isSearch, fileid)
	}

	/**
	 * Event handler that resets the store if the file list view was changed.
	 *
	 * @param view - The new view that is active
	 * @private
	 */
	function onViewChanged(view: View) {
		if (view.id !== VIEW_ID) {
			query.value = ''
			scope.value = 'filter'
		}
	}

	/**
	 * Initialize the store from the router if needed
	 */
	function initialize() {
		subscribe('files:navigation:changed', onViewChanged)

		const router = window.OCP.Files.Router as RouterService
		// if we initially load the search view (e.g. hard page refresh)
		// then we need to initialize the store from the router
		if (router.params.view === VIEW_ID) {
			query.value = [router.query.query].flat()[0] ?? ''

			if (query.value) {
				scope.value = 'globally'
				logger.debug('Directly navigated to search view', { query: query.value })
			} else {
				// we do not have any query so we need to move to the files list
				logger.info('Directly navigated to search view without any query, redirect to files view.')
				router.goToRoute(
					undefined,
					{
						...router.params,
						view: 'files',
					},
					{
						...router.query,
						query: undefined,
					},
					true,
				)
			}
		}
	}

	return {
		base,
		query,
		scope,
	}
})
