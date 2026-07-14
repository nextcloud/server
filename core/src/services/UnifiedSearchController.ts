/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { search as unifiedSearch } from './UnifiedSearchService.js'

type CategorySearchStatus = 'loading' | 'loaded' | 'failed' | 'blocked'

export interface CategorySearchState {
	status: CategorySearchStatus
	entries: unknown[]
	cursor: string | null
	hasMore: boolean
	loadMoreFailed: boolean
}

export interface CategorySearchParams {
	type?: string
	since?: string
	until?: string
	person?: string
	extraQueries?: object
}

export const REVEAL_INTERVAL_MS = 1500

/**
 * Runs a unified search across categories in priority order, blocking
 * lower-priority results until their predecessors arrive or a timer reveals them.
 */
export class UnifiedSearchController {
	private query: string = ''
	private params: Record<string, CategorySearchParams> = {}
	private searchStates: Record<string, CategorySearchState> = {}
	private searchGeneration: number = 0
	private revealTimer: ReturnType<typeof setTimeout> | null = null
	private pendingCancels: (() => void)[] = []

	constructor(private onChange?: (states: Record<string, CategorySearchState>) => void) {}

	/**
	 * Start a search. Cancels and replaces any search already in flight.
	 *
	 * @param query the search term
	 * @param categories category ids in priority order
	 * @param params optional per-category search parameters
	 * @return resolves once every category has settled
	 */
	async search(query: string, categories: string[], params?: Record<string, CategorySearchParams>): Promise<void> {
		this.cancelPendingRequests()
		this.searchStates = {}
		this.searchGeneration++
		const generation = this.searchGeneration
		this.query = query
		this.params = params || {}

		this.startRevealTimer()

		await Promise.allSettled(categories.map((category) => this.searchCategory(category, generation, categories)))
	}

	/**
	 * Fetch the next page for one category and append it. A no-op unless the
	 * category is loaded with more pages. On failure the existing results stay
	 * and `loadMoreFailed` is raised, so calling again retries.
	 *
	 * @param category the category id to page
	 */
	async loadMore(category: string): Promise<void> {
		const generation = this.searchGeneration
		const categoryState = { ...this.searchStates[category] }
		if (!categoryState.hasMore || categoryState.status !== 'loaded') {
			return
		}

		this.patchStates({ [category]: { status: 'loading', loadMoreFailed: false } })

		const { request, cancel } = unifiedSearch({
			type: category,
			query: this.query,
			cursor: categoryState.cursor,
			...this.params[category],
		})

		this.pendingCancels.push(cancel)

		try {
			const response = await request()
			if (this.searchGeneration !== generation) {
				return
			}
			const { entries, cursor, hasMore } = response.data.ocs.data

			this.patchStates({[category]: {
				entries: [...categoryState.entries, ...entries],
				cursor,
				hasMore,
				status: 'loaded',
			}})
		} catch {
			if (this.searchGeneration !== generation) {
				return
			}
			this.patchStates({ [category]: { status: 'loaded', loadMoreFailed: true } })
		}
	}

	/**
	 * A shallow copy of the current per-category state, safe to read for rendering.
	 *
	 * @return the current search states keyed by category id
	 */
	getSnapshot(): Record<string, CategorySearchState> {
		return { ...this.searchStates }
	}

	/**
	 * Tear down on unmount: cancels in-flight requests and stops the reveal timer.
	 */
	dispose(): void {
		this.cancelPendingRequests()
		this.stopRevealTimer()
	}

	private async searchCategory(
		category: string,
		generation: number,
		categories: string[],
	): Promise<void> {
		this.patchStates({ [category]: {
			status: 'loading',
			entries: [],
			cursor: null,
			hasMore: false,
			loadMoreFailed: false,
		} })

		const { request, cancel } = unifiedSearch({
			type: category,
			query: this.query,
			cursor: null,
			...this.params[category],
		})

		this.pendingCancels.push(cancel)

		try {
			const response = await request()
			if (this.searchGeneration !== generation) {
				// A new search has been started, ignore this result
				return
			}

			const { entries, cursor, hasMore } = response.data.ocs.data
			// Decide blocked-vs-loaded once, at settle time. From here reconcile only ever
			// promotes (blocked -> loaded); it never re-blocks, so this is the single point
			// where a category can enter the blocked state.
			this.patchStates({ [category]: {
				status: this.shouldBlockCategory(category, categories) ? 'blocked' : 'loaded',
				entries,
				cursor,
				hasMore,
				loadMoreFailed: false,
			} })
		} catch {
			if (this.searchGeneration !== generation) {
				return
			}
			this.patchStates({ [category]: {
				status: 'failed',
				entries: [],
				cursor: null,
				hasMore: false,
				loadMoreFailed: false,
			}})
		}

		this.reconcileCategoryStatuses(categories)
	}

	private reconcileCategoryStatuses(categories: string[]): void {
		categories.forEach((category) => {
			// Promotion only: reveal a blocked category once no higher-priority category is
			// still pending. Never demote a loaded/failed/loading category. Once a category is
			// revealed it must stay revealed, otherwise it flickers when a slower, later
			// category settles and momentarily re-blocks it.
			if (this.searchStates[category].status !== 'blocked') {
				return
			}
			if (!this.shouldBlockCategory(category, categories)) {
				this.patchStates({ [category]: { status: 'loaded' } })
			}
		})
	}

	private startRevealTimer(): void {
		this.stopRevealTimer()
		this.revealTimer = setTimeout(() => {
			const categories = Object.keys(this.searchStates)
			const hasPendingCategories = categories.some((category) => ['loading', 'blocked'].includes(this.searchStates[category].status))
			this.unblockAllCategories(categories)
			if (hasPendingCategories) {
				this.startRevealTimer()
			}
		}, REVEAL_INTERVAL_MS)
	}

	private stopRevealTimer(): void {
		if (this.revealTimer) {
			clearTimeout(this.revealTimer)
			this.revealTimer = null
		}
	}

	private cancelPendingRequests(): void {
		this.pendingCancels.forEach((cancel) => cancel())
		this.pendingCancels = []
	}

	private unblockAllCategories(categories: string[]): void {
		categories.forEach((category) => {
			if (this.searchStates[category].status === 'blocked') {
				this.patchStates({ [category]: { status: 'loaded' } })
			}
		})
	}

	private shouldBlockCategory(category: string, categories: string[]): boolean {
		if (!this.searchStates[category]) {
			return false
		}

		return categories.slice(0, categories.indexOf(category)).some((c) => {
			const categoryState = this.searchStates[c]
			return categoryState && ['loading', 'blocked'].includes(categoryState.status)
		})
	}

	private patchStates(next: Record<string, Partial<CategorySearchState>>): void {
		Object.keys(next).forEach((category) => {
			const categoryState = { ...this.searchStates[category], ...next[category] }
			this.searchStates[category] = categoryState
		})
		this.onChange?.(this.getSnapshot())
	}
}
