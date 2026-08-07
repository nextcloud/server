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
 * Whether a category has anything for the user to look at. Blocked is deliberately
 * withheld, failed carries no entries, and a loading category keeps its previous page up
 * (stale-while-revalidate) so it stays visible through a refetch.
 *
 * Exported so the one definition also serves the Vue-side test doubles; the controller is
 * the only place that decides category-level visibility.
 *
 * @param state the category state to test
 */
export function isCategoryVisible(state: CategorySearchState): boolean {
	return state.entries.length > 0 && (state.status === 'loaded' || state.status === 'loading')
}

/**
 * Runs a unified search across categories in priority order, blocking
 * lower-priority results until their predecessors arrive or a timer reveals them.
 *
 * Priority decides who waits for whom. It has no say over what is already on screen:
 * see `getRevealOrder()`.
 */
export class UnifiedSearchController {
	private query: string = ''
	private params: Record<string, CategorySearchParams> = {}
	private searchStates: Record<string, CategorySearchState> = {}
	private revealOrder: string[] = []
	private revealWindowOpen: boolean = false
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
		// Prune rather than clear: survivors keep the slots they already hold, so refining a query
		// never re-sorts rendered results back to priority order. A category the new search
		// dropped is reseeded invisible if it ever returns, so it re-enters at the bottom. This
		// cannot cover the window while the states below are still being reseeded one at a time;
		// getRevealOrder() does that.
		this.revealOrder = this.revealOrder.filter((category) => categories.includes(category))
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
			const staleEntries = prev && isCategoryVisible(prev) ? prev.entries : []
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

	/**
	 * The ids of the categories currently on screen, in display order.
	 *
	 * Append-only, so a category never moves up into a slot another one already occupies: a
	 * result that arrives late renders below what the user is already reading, however high
	 * its priority. Read this rather than the snapshot's key order, which is the priority
	 * order and an input to blocking, not a rendering order.
	 *
	 * Every id is indexable in the same snapshot, so a caller can map without guarding.
	 *
	 * @return visible category ids, top to bottom
	 */
	getRevealOrder(): string[] {
		return this.revealOrder.filter((category) => category in this.searchStates)
	}

	dispose(): void {
		this.stopBackgroundWork()
	}

	reset(): void {
		this.stopBackgroundWork()
		this.searchStates = {}
		this.revealOrder = []
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

	/**
	 * Arm the one reveal window a search gets. Ordered reveal governs the first paint only:
	 * when the window closes everything blocked is shown and nothing may block again, so a
	 * category that lands later is revealed straight away, at the end. Only a new search
	 * opens another window.
	 */
	private startRevealTimer(): void {
		this.stopRevealTimer()
		this.revealWindowOpen = true
		this.revealTimer = setTimeout(() => {
			this.revealWindowOpen = false
			this.unblockAllCategories(Object.keys(this.searchStates))
		}, REVEAL_INTERVAL_MS)
	}

	private stopRevealTimer(): void {
		this.revealWindowOpen = false
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
		// Once the window has closed, ordered reveal is over for this search.
		if (!this.revealWindowOpen || !this.searchStates[category]) {
			return false
		}

		return categories.slice(0, categories.indexOf(category)).some((c) => {
			const categoryState = this.searchStates[c]
			return categoryState && ['loading', 'blocked'].includes(categoryState.status)
		})
	}

	/**
	 * Keep the display order in step with what is on screen. Losing its results frees a
	 * category's slot, so the list closes the gap instead of leaving a hole.
	 *
	 * @param category the category id that just changed
	 * @param state its merged state
	 */
	private syncRevealOrder(category: string, state: CategorySearchState): void {
		const at = this.revealOrder.indexOf(category)
		const visible = isCategoryVisible(state)
		if (visible && at === -1) {
			this.revealOrder.push(category)
		} else if (!visible && at !== -1) {
			this.revealOrder.splice(at, 1)
		}
	}

	private patchStates(next: Record<string, Partial<CategorySearchState>>): void {
		Object.keys(next).forEach((category) => {
			const categoryState = { ...this.searchStates[category], ...next[category] }
			this.searchStates[category] = categoryState
			this.syncRevealOrder(category, categoryState)
		})
		this.onChange?.(this.getSnapshot())
	}
}
