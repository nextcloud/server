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

export const REVEAL_INTERVAL_MS = 1000

/**
 * Results fetched per category per page. Sized for the detail view (which shows the
 * whole page); the aggregate caps to RESULTS_PER_CATEGORY. Server default 5, design 10.
 */
export const PAGE_SIZE = 10

/**
 * Whether a category has anything for the user to look at. Blocked is deliberately withheld
 * and failed carries no entries. Loading counts because paging keeps the pages already
 * fetched on screen while the next one is in flight; a new query has no entries to show, so
 * it reads as not visible until results actually land.
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
		// A new query hides everything the last one produced. Carrying results over would only
		// let them shift under the user once the real ones land, and the results are about to
		// differ anyway. So each search is a clean slate: empty screen, then a fresh ordered
		// reveal from priority order. Nothing is on screen, so nothing can be displaced.
		this.searchStates = {}
		this.revealOrder = []
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
	 * Append-only within a search, so a category never moves up into a slot another one already
	 * occupies: a result that arrives late renders below what the user is already reading,
	 * however high its priority. A new query starts over from priority order, since it clears
	 * the screen first and so has nothing to displace. Read this rather than the snapshot's key
	 * order, which is the priority order and an input to blocking, not a rendering order.
	 *
	 * Only ever names categories the current snapshot holds, so a caller can map without guarding.
	 *
	 * @return visible category ids, top to bottom
	 */
	getRevealOrder(): string[] {
		return [...this.revealOrder]
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
			// (never re-blocks), so this is the only place a category becomes blocked.
			this.patchStates({ [category]: {
				status: this.shouldBlockCategory(category, categories) ? 'blocked' : 'loaded',
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
