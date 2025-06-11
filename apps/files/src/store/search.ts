import type { INode } from '@nextcloud/files'
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

	watch(query, () => {
		emit('files:search:updated', { query: query.value, scope: scope.value })
	})

	return {
		base,
		query,
		scope,
	}
})
