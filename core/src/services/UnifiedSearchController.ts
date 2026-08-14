/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { search as unifiedSearch } from './UnifiedSearchService.js'

type CategorySearchStatus = 'loading' | 'loaded' | 'failed' | 'blocked'

export interface CategorySearchState {
	status: CategorySearchStatus
	entries: unknown[]
	cursor: string | number | null
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
 * Results fetched per category per page. Sized for the detail view (which shows the
 * whole page); the aggregate caps to RESULTS_PER_CATEGORY. Server default 5, design 10.
 */
export const PAGE_SIZE = 10

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
		// Stale-while-revalidate: keep the previous page on screen while the new search is in
		// flight, so refining a query swaps results in place instead of flashing an empty panel.
		// Each recurring category is reseeded with its prior entries below; dropped ones vanish.
		const previous = this.searchStates
		this.searchStates = {}
		this.searchGeneration++
		const generation = this.searchGeneration
		this.query = query
		this.params = params || {}

		this.startRevealTimer()

		await Promise.allSettled(categories.map((category) => {
			const prev = previous[category]
			// Only entries that were actually on screen seed the stale view. A blocked or failed
			// category's entries were fetched but never rendered, so they must not carry over
			// (and must not let the category skip the ordered reveal).
			const staleEntries = prev && (prev.status === 'loaded' || prev.status === 'loading') ? prev.entries : []
			return this.searchCategory(category, generation, categories, staleEntries)
		}))
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
			limit: PAGE_SIZE,
			...this.params[category],
		})

		this.pendingCancels.push(cancel)

		try {
			const response = await request()
			if (this.searchGeneration !== generation) {
				return
			}
			const { entries, cursor, isPaginated } = response.data.ocs.data

			// A provider can echo a non-null cursor on an empty page, keeping hasMore true and
			// leaving a dead "Load more" button. An empty page means exhausted, cursor or not.
			const reachedEnd = entries.length === 0

			this.patchStates({[category]: {
				entries: [...categoryState.entries, ...entries],
				cursor,
				hasMore: !reachedEnd && this.hasMorePages(isPaginated, cursor),
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

	dispose(): void {
		this.stopBackgroundWork()
	}

	reset(): void {
		this.stopBackgroundWork()
		this.searchStates = {}
		this.query = ''
		this.params = {}
		this.searchGeneration++
		this.onChange?.(this.getSnapshot())
	}

	private async searchCategory(
		category: string,
		generation: number,
		categories: string[],
		staleEntries: unknown[] = [],
	): Promise<void> {
		// Seed with the prior page (stale-while-revalidate) so it stays visible under the
		// spinner until the fresh page replaces it. Empty on a first search.
		this.patchStates({ [category]: {
			status: 'loading',
			entries: staleEntries,
			cursor: null,
			hasMore: false,
			loadMoreFailed: false,
		} })

		const { request, cancel } = unifiedSearch({
			type: category,
			query: this.query,
			cursor: null,
			limit: PAGE_SIZE,
			...this.params[category],
		})

		this.pendingCancels.push(cancel)

		try {
			const response = await request()
			if (this.searchGeneration !== generation) {
				// A new search has been started, ignore this result
				return
			}

			const { entries, cursor, isPaginated } = response.data.ocs.data
			// Decide blocked vs loaded once, here at settle. Reconcile only promotes after this
			// (never re-blocks), so this is the only place a category becomes blocked. A category
			// that carried stale results skips blocking: it is already on screen, so blocking it
			// would blink it off until its predecessors clear. Ordered reveal is only for the
			// first paint, when nothing is shown yet.
			this.patchStates({ [category]: {
				status: (staleEntries.length === 0 && this.shouldBlockCategory(category, categories)) ? 'blocked' : 'loaded',
				entries,
				cursor,
				hasMore: this.hasMorePages(isPaginated, cursor),
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
			// Promotion only: reveal a blocked category once its predecessors clear, never demote.
			// A revealed category must stay revealed, else it flickers when a slower one settles.
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

	private stopBackgroundWork(): void {
		this.cancelPendingRequests()
		this.stopRevealTimer()
	}

	private unblockAllCategories(categories: string[]): void {
		categories.forEach((category) => {
			if (this.searchStates[category].status === 'blocked') {
				this.patchStates({ [category]: { status: 'loaded' } })
			}
		})
	}

	/**
	 * Whether a category can page further. The backend never sends a "has more"
	 * flag, only `isPaginated` and a `cursor`, so derive it: a category has more
	 * pages when it paginates and handed back a cursor to continue from.
	 *
	 * @param isPaginated whether the provider returned a paginated result
	 * @param cursor the cursor to continue from, or null when there is none
	 */
	private hasMorePages(isPaginated: boolean, cursor: string | number | null): boolean {
		return isPaginated && cursor !== null
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
