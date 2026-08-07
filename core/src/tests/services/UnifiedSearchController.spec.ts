/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { PAGE_SIZE, REVEAL_INTERVAL_MS, UnifiedSearchController } from '../../services/UnifiedSearchController.ts'

const service = vi.hoisted(() => ({
	search: vi.fn(),
	getProviders: vi.fn(),
	getContacts: vi.fn(),
}))
vi.mock('../../services/UnifiedSearchService.js', () => service)

/**
 * Deferred stand-in for a provider's `search()` return value. Resolve it to
 * make that provider arrive on demand.
 */
function deferredProvider() {
	const { promise, resolve, reject } = Promise.withResolvers<{ entries: unknown[] }>()
	return {
		cancel: vi.fn(),
		request: async () => {
			const { entries } = await promise
			// The real OCS endpoint returns a complete (non-paginated) result shape.
			return { data: { ocs: { data: { entries, cursor: null, isPaginated: false } } } }
		},
		resolve: (entries: unknown[] = []) => resolve({ entries }),
		reject,
	}
}

/**
 * Deferred stand-in that serves successive pages. Each `request()` call takes
 * the next page; a test resolves page N on demand with `resolvePage(n, data)`,
 * where `data` mirrors the OCS payload: `{ entries, cursor, isPaginated }`.
 */
function pagedProvider() {
	const pages: ReturnType<typeof Promise.withResolvers<{ entries: unknown[], cursor: string | number | null, isPaginated: boolean }>>[] = []
	const pageAt = (index: number) => (pages[index] ??= Promise.withResolvers())
	let call = 0
	return {
		cancel: vi.fn(),
		request: async () => {
			const data = await pageAt(call++).promise
			return { data: { ocs: { data } } }
		},
		resolvePage: (index: number, data: { entries: unknown[], cursor: string | number | null, isPaginated: boolean }) => pageAt(index).resolve(data),
		rejectPage: (index: number, reason?: unknown) => pageAt(index).reject(reason),
	}
}

/**
 * Register one deferred provider per category type on the mocked service.
 * Returns the map so a test can resolve/reject a specific category on demand,
 * e.g. `providers.deck.resolve(['a result'])`.
 */
function mockProviders(types: string[]) {
	const providers = Object.fromEntries(types.map((type) => [type, deferredProvider()]))
	service.search.mockImplementation(({ type }: { type: string }) => providers[type])
	return providers
}

/**
 * The initial per-category state before any provider has resolved. Identical
 * for every pending category, so tests assert against this shared shape.
 */
const loading = { status: 'loading', entries: [], cursor: null, hasMore: false, loadMoreFailed: false }

beforeEach(() => {
	vi.useFakeTimers()
})

afterEach(() => {
	vi.clearAllMocks()
	vi.useRealTimers()
})

describe('UnifiedSearchController', () => {
	describe('loading state', () => {
		it('sets loading state on all categories when a search is started', async () => {
			service.search.mockImplementation(() => deferredProvider())

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk'])

			expect(searchController.getSnapshot()).toEqual({
				files: loading,
				talk: loading,
			})
		})

		it('returns the results for a single category', async () => {
			const results = deferredProvider()
			service.search.mockReturnValueOnce(results)

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files'])

			results.resolve(['Some result'])

			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot()).toEqual({
				files: { status: 'loaded', entries: ['Some result'], cursor: null, hasMore: false, loadMoreFailed: false },
			})
		})
	})

	describe('ordering and blocking', () => {
		it('marks category as blocked if it arrived out of order', async () => {
			const providers = mockProviders(['files', 'talk', 'deck'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk', 'deck'])

			providers.deck.resolve(['Deck result'])

			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot()).toEqual({
				files: loading,
				talk: loading,
				deck: { status: 'blocked', entries: ['Deck result'], cursor: null, hasMore: false, loadMoreFailed: false },
			})
		})

		it('marks category as loaded if it is unblocked (i.e., previous categories are loaded)', async () => {
			const providers = mockProviders(['files', 'talk', 'deck'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk', 'deck'])

			providers.deck.resolve(['Deck result'])

			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot()).toEqual({
				files: loading,
				talk: loading,
				deck: { status: 'blocked', entries: ['Deck result'], cursor: null, hasMore: false, loadMoreFailed: false },
			})

			providers.files.resolve(['Files result'])
			providers.talk.resolve(['Talk result'])

			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot()).toEqual({
				files: { status: 'loaded', entries: ['Files result'], cursor: null, hasMore: false, loadMoreFailed: false },
				talk: { status: 'loaded', entries: ['Talk result'], cursor: null, hasMore: false, loadMoreFailed: false },
				deck: { status: 'loaded', entries: ['Deck result'], cursor: null, hasMore: false, loadMoreFailed: false },
			})
		})

		it('does not change the status of a category that has failed', async () => {
			const providers = mockProviders(['files', 'talk', 'deck'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk', 'deck'])

			providers.talk.resolve(['Talk result'])
			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot()).toEqual({
				files: loading,
				talk: { status: 'blocked', entries: ['Talk result'], cursor: null, hasMore: false, loadMoreFailed: false },
				deck: loading,
			})

			// Ensure that we also reconcile status on failure.
			// This is important because a category that has failed may have been blocking
			// other categories, and if it fails, those categories should be unblocked.
			providers.files.reject(['Files result'])
			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot()).toEqual({
				files: { status: 'failed', entries: [], cursor: null, hasMore: false, loadMoreFailed: false },
				talk: { status: 'loaded', entries: ['Talk result'], cursor: null, hasMore: false, loadMoreFailed: false },
				deck: loading,
			})
			// A failed category has nothing to show, so it never takes a display slot.
			expect(searchController.getRevealOrder()).toEqual(['talk'])
		})
	})

	describe('stale search guard', () => {
		it('ignores a stale response from a superseded search', async () => {
			const first = mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('first', ['files', 'talk'])

			// A newer search supersedes the first before it resolves.
			const second = mockProviders(['files', 'talk'])
			searchController.search('second', ['files', 'talk'])

			// The stale (first) responses arrive late and must be ignored.
			first.files.resolve(['Stale files'])
			first.talk.resolve(['Stale talk'])
			await vi.advanceTimersByTimeAsync(0)

			// State still reflects the second search: both categories pending.
			expect(searchController.getSnapshot()).toEqual({
				files: loading,
				talk: loading,
			})

			// The live (second) search still resolves normally.
			second.files.resolve(['Live files'])
			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot().files).toEqual({
				status: 'loaded',
				entries: ['Live files'],
				cursor: null,
				hasMore: false,
				loadMoreFailed: false,
			})
		})

		it('ignores a stale response for a category the newer search dropped', async () => {
			const first = mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('first', ['files', 'talk'])

			// A newer search with a completely different category set supersedes it.
			const second = mockProviders(['deck'])
			searchController.search('second', ['deck'])

			// The stale response is for 'files', which no longer exists in the
			// current search. Reconciling it must not throw on the missing category.
			first.files.resolve(['Stale files'])
			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot()).toEqual({
				deck: loading,
			})

			// The live search still resolves normally.
			second.deck.resolve(['Live deck'])
			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot()).toEqual({
				deck: { status: 'loaded', entries: ['Live deck'], cursor: null, hasMore: false, loadMoreFailed: false },
			})
		})
	})

	describe('reveal order', () => {
		it('keys the snapshot in priority order, regardless of which categories resolve first', async () => {
			const providers = mockProviders(['files', 'talk', 'deck'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk', 'deck'])

			// Resolve in reverse priority order: lowest-priority provider first,
			// highest-priority one last. If key order followed arrival, this would flip it.
			providers.deck.resolve(['deck result'])
			await vi.advanceTimersByTimeAsync(0)
			providers.talk.resolve(['talk result'])
			await vi.advanceTimersByTimeAsync(0)
			providers.files.resolve(['files result'])
			await vi.advanceTimersByTimeAsync(0)

			// Keys still follow the categories array: each category's slot is inserted
			// synchronously (the 'loading' patch) before any request resolves, so
			// arrival order cannot reorder the snapshot.
			//
			// Key order is the internal priority order: it decides who blocks whom and keeps a
			// batched flush preferred-ordered. Display order is getRevealOrder(), asserted in
			// the cases below, and the two deliberately disagree once anything arrives late.
			expect(Object.keys(searchController.getSnapshot())).toEqual(['files', 'talk', 'deck'])
		})

		it('appends a late high-priority category below the ones already revealed', async () => {
			const providers = mockProviders(['files', 'talk', 'deck'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk', 'deck'])

			// talk and deck settle while files (top priority) is still in flight, so both block.
			providers.talk.resolve(['Talk result'])
			providers.deck.resolve(['Deck result'])
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getRevealOrder()).toEqual([])

			// The tick gives up waiting for files and reveals them.
			await vi.advanceTimersByTimeAsync(REVEAL_INTERVAL_MS)
			expect(searchController.getRevealOrder()).toEqual(['talk', 'deck'])

			// files lands just after. It must go below what is already on screen: displacing
			// rendered results is the jump this whole mechanism exists to prevent.
			providers.files.resolve(['Files result'])
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getRevealOrder()).toEqual(['talk', 'deck', 'files'])
		})

		it('keeps priority order among categories revealed in the same flush', async () => {
			const providers = mockProviders(['files', 'talk', 'deck'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk', 'deck'])

			// deck arrives before talk, both blocked behind files.
			providers.deck.resolve(['Deck result'])
			await vi.advanceTimersByTimeAsync(0)
			providers.talk.resolve(['Talk result'])
			await vi.advanceTimersByTimeAsync(0)

			// Nothing was on screen before the flush, so there is nothing to displace and the
			// preferred order applies between them rather than the order they happened to arrive.
			await vi.advanceTimersByTimeAsync(REVEAL_INTERVAL_MS)
			expect(searchController.getRevealOrder()).toEqual(['talk', 'deck'])
		})

		it('restarts the reveal order from priority on a new query', async () => {
			const first = mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('old', ['files', 'talk'])

			// talk got on screen first, so this query renders talk above files.
			first.talk.resolve(['Old talk'])
			await vi.advanceTimersByTimeAsync(REVEAL_INTERVAL_MS)
			first.files.resolve(['Old files'])
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getRevealOrder()).toEqual(['talk', 'files'])

			// A new query hides everything: the results are about to be different, so there is
			// nothing on screen to protect and the next paint starts from priority order again.
			const second = mockProviders(['files', 'talk'])
			searchController.search('new', ['files', 'talk'])
			expect(searchController.getRevealOrder()).toEqual([])

			second.files.resolve(['New files'])
			second.talk.resolve(['New talk'])
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getRevealOrder()).toEqual(['files', 'talk'])
		})

		it('releases a slot when a category loses its results, and appends it again if it returns', async () => {
			const first = mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('a', ['files', 'talk'])
			first.files.resolve(['Files a'])
			first.talk.resolve(['Talk a'])
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getRevealOrder()).toEqual(['files', 'talk'])

			// files has nothing for the refined query, so it stops being visible and frees
			// its slot. talk closing the gap moves up, which is not an insertion above it.
			const second = mockProviders(['files', 'talk'])
			searchController.search('b', ['files', 'talk'])
			second.files.resolve([])
			second.talk.resolve(['Talk b'])
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getRevealOrder()).toEqual(['talk'])

			// The next query is a clean slate, so it comes back in preferred order rather than
			// staying demoted for the rest of the session.
			const third = mockProviders(['files', 'talk'])
			searchController.search('c', ['files', 'talk'])
			third.files.resolve(['Files c'])
			third.talk.resolve(['Talk c'])
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getRevealOrder()).toEqual(['files', 'talk'])
		})

		it('recovers preferred order after a provider filter round trip', async () => {
			const first = mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('foo', ['files', 'talk'])
			first.files.resolve(['Files result'])
			first.talk.resolve(['Talk result'])
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getRevealOrder()).toEqual(['files', 'talk'])

			// A provider filter narrows the search: files leaves the category list altogether.
			const second = mockProviders(['talk'])
			searchController.search('foo', ['talk'])
			second.talk.resolve(['Talk result'])
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getRevealOrder()).toEqual(['talk'])

			// The filter comes off. Each search stands on its own, so files is back on top
			// instead of being stuck below talk until the popover closes.
			const third = mockProviders(['files', 'talk'])
			searchController.search('foo', ['files', 'talk'])
			third.files.resolve(['Files result'])
			third.talk.resolve(['Talk result'])
			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getRevealOrder()).toEqual(['files', 'talk'])
		})

		it('never hands out a category the snapshot cannot index, part-way through a search', async () => {
			const first = mockProviders(['files', 'talk'])
			const unindexable: string[][] = []

			// Reads back through the controller the way the composable does: the callback only
			// runs during a search, so the reference is live by then.
			const searchController = new UnifiedSearchController((states) => {
				const missing = searchController.getRevealOrder().filter((category) => !(category in states))
				if (missing.length > 0) {
					unindexable.push(missing)
				}
			})

			searchController.search('foo', ['files', 'talk'])
			first.files.resolve(['Files result'])
			first.talk.resolve(['Talk result'])
			await vi.advanceTimersByTimeAsync(0)

			// A refined query empties the snapshot and reseeds one category at a time, notifying
			// after each. The order still holds both survivors throughout, so between those two
			// notifications the order names a category the snapshot has not got back yet. The
			// view maps the order straight onto the snapshot, so the accessor must never expose
			// that: a computed dereferencing a missing state throws inside the page header.
			const second = mockProviders(['files', 'talk'])
			searchController.search('bar', ['files', 'talk'])
			second.files.resolve(['Files 2'])
			second.talk.resolve(['Talk 2'])
			await vi.advanceTimersByTimeAsync(0)

			expect(unindexable).toEqual([])
		})
	})

	describe('resetting between searches', () => {
		it('drops categories that are not part of a newer, narrower search', async () => {
			const first = mockProviders(['files', 'talk', 'deck'])

			const searchController = new UnifiedSearchController()
			searchController.search('first', ['files', 'talk', 'deck'])
			first.files.resolve(['Files result'])
			first.talk.resolve(['Talk result'])
			await vi.advanceTimersByTimeAsync(0)

			// A narrower search replaces the first. The dropped categories must not linger in
			// the snapshot, and nothing from the previous query stays on screen.
			mockProviders(['files'])
			searchController.search('second', ['files'])

			expect(searchController.getSnapshot()).toEqual({
				files: loading,
			})
			expect(searchController.getRevealOrder()).toEqual([])
		})
	})

	describe('changing the query', () => {
		it('drops the previous results as soon as the query changes', async () => {
			const first = mockProviders(['files'])

			const searchController = new UnifiedSearchController()
			searchController.search('old', ['files'])
			first.files.resolve(['Old result'])
			await vi.advanceTimersByTimeAsync(0)

			// The new query is about to return different results, so keeping the old ones up
			// would only let them shift under the user once the real ones land. Hide, then show.
			const second = mockProviders(['files'])
			searchController.search('new', ['files'])
			expect(searchController.getSnapshot().files).toEqual({
				status: 'loading',
				entries: [],
				cursor: null,
				hasMore: false,
				loadMoreFailed: false,
			})

			second.files.resolve(['New result'])
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getSnapshot().files).toEqual({
				status: 'loaded',
				entries: ['New result'],
				cursor: null,
				hasMore: false,
				loadMoreFailed: false,
			})
		})

		it('puts every category back through the ordered reveal on a new query', async () => {
			const first = mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('old', ['files', 'talk'])
			first.files.resolve(['Old files'])
			await vi.advanceTimersByTimeAsync(0)
			first.talk.resolve(['Old talk'])
			await vi.advanceTimersByTimeAsync(0)

			// Refine. talk comes back first this time. Nothing is on screen to protect any more,
			// so it takes its turn in the queue again instead of skipping the reveal.
			const second = mockProviders(['files', 'talk'])
			searchController.search('new', ['files', 'talk'])
			second.talk.resolve(['New talk'])
			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot().talk.status).toBe('blocked')
			expect(searchController.getRevealOrder()).toEqual([])
		})
	})

	describe('cancellation', () => {
		it('cancels the previous search\'s in-flight requests when a new search starts', () => {
			const first = mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('first', ['files', 'talk'])

			// A new search supersedes the first while its requests are in flight.
			mockProviders(['files', 'talk'])
			searchController.search('second', ['files', 'talk'])

			expect(first.files.cancel).toHaveBeenCalledOnce()
			expect(first.talk.cancel).toHaveBeenCalledOnce()
		})
	})

	describe('dispose', () => {
		it('cancels in-flight requests when disposed', () => {
			const providers = mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk'])

			searchController.dispose()

			expect(providers.files.cancel).toHaveBeenCalledOnce()
			expect(providers.talk.cancel).toHaveBeenCalledOnce()
		})

		it('stops the reveal timer when disposed', () => {
			mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk'])

			// A search arms the reveal timer.
			expect(vi.getTimerCount()).toBe(1)

			searchController.dispose()

			expect(vi.getTimerCount()).toBe(0)
		})
	})

	describe('reset', () => {
		it('clears the current search state', async () => {
			const providers = mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk'])
			providers.files.resolve(['Files result'])
			await vi.advanceTimersByTimeAsync(0)

			searchController.reset()

			expect(searchController.getSnapshot()).toEqual({})
			// Closing the popover is the one point where display order re-derives from priority.
			expect(searchController.getRevealOrder()).toEqual([])
		})

		it('notifies with the empty snapshot when reset', async () => {
			const providers = mockProviders(['files'])
			const onChange = vi.fn()

			const searchController = new UnifiedSearchController(onChange)
			searchController.search('query', ['files'])
			providers.files.resolve(['Files result'])
			await vi.advanceTimersByTimeAsync(0)
			onChange.mockClear()

			searchController.reset()

			// The adapter must be told the results are gone, otherwise the reactive
			// mirror keeps the previous session's entries and they flash on reopen.
			expect(onChange).toHaveBeenCalledWith({})
		})

		it('cancels in-flight requests when reset', () => {
			const providers = mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk'])

			searchController.reset()

			expect(providers.files.cancel).toHaveBeenCalledOnce()
			expect(providers.talk.cancel).toHaveBeenCalledOnce()
		})

		it('stops the reveal timer when reset', () => {
			mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk'])

			// A search arms the reveal timer.
			expect(vi.getTimerCount()).toBe(1)

			searchController.reset()

			expect(vi.getTimerCount()).toBe(0)
		})

		it('drops a late response from a search that was reset', async () => {
			const providers = mockProviders(['files'])
			const onChange = vi.fn()

			const searchController = new UnifiedSearchController(onChange)
			searchController.search('query', ['files'])

			searchController.reset()
			onChange.mockClear()

			// A request from the pre-reset search resolves late. The generation bump
			// must drop it so it cannot repopulate the cleared state.
			providers.files.resolve(['Stale files'])
			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot()).toEqual({})
			expect(onChange).not.toHaveBeenCalled()
		})
	})

	describe('change notification', () => {
		it('notifies once the initial loading states are set, before any provider resolves', () => {
			service.search.mockImplementation(() => deferredProvider())
			const onChange = vi.fn()

			const searchController = new UnifiedSearchController(onChange)
			searchController.search('query', ['files', 'talk'])

			// The adapter must be able to paint the loading spinners straight away,
			// without waiting for the first response to land.
			expect(onChange).toHaveBeenCalled()
			expect(searchController.getSnapshot()).toEqual({ files: loading, talk: loading })
		})

		it('notifies when a category resolves', async () => {
			const providers = mockProviders(['files'])
			const onChange = vi.fn()

			const searchController = new UnifiedSearchController(onChange)
			searchController.search('query', ['files'])
			onChange.mockClear()

			providers.files.resolve(['Files result'])
			await vi.advanceTimersByTimeAsync(0)

			expect(onChange).toHaveBeenCalled()
		})

		it('notifies when loadMore appends a page', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)
			const onChange = vi.fn()

			const searchController = new UnifiedSearchController(onChange)
			searchController.search('query', ['files'])
			files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: true })
			await vi.advanceTimersByTimeAsync(0)
			onChange.mockClear()

			searchController.loadMore('files')
			files.resolvePage(1, { entries: ['b'], cursor: 'cursor-2', isPaginated: false })
			await vi.advanceTimersByTimeAsync(0)

			expect(onChange).toHaveBeenCalled()
		})

		it('notifies when the reveal timer unblocks a category', async () => {
			const providers = mockProviders(['files', 'talk'])
			const onChange = vi.fn()

			const searchController = new UnifiedSearchController(onChange)
			searchController.search('query', ['files', 'talk'])

			// talk arrives out of order and is blocked behind files (still loading).
			providers.talk.resolve(['Talk result'])
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getSnapshot().talk.status).toBe('blocked')
			onChange.mockClear()

			// The timer flush promotes it to loaded; the adapter must hear about it.
			await vi.advanceTimersByTimeAsync(REVEAL_INTERVAL_MS)
			expect(searchController.getSnapshot().talk.status).toBe('loaded')
			expect(onChange).toHaveBeenCalled()
		})

		it('works without an onChange callback', async () => {
			const providers = mockProviders(['files'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files'])
			providers.files.resolve(['Files result'])

			// The callback is optional; a headless controller must still run.
			await expect(vi.advanceTimersByTimeAsync(0)).resolves.not.toThrow()
			expect(searchController.getSnapshot().files.status).toBe('loaded')
		})
	})

	describe('pagination', () => {
		it('derives hasMore from the backend isPaginated + cursor, never a hasMore field', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files'])

			// A paginated result carrying a cursor can page further.
			files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: true })
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getSnapshot().files.hasMore).toBe(true)

			// A non-paginated (complete) result cannot, even with a cursor still present.
			searchController.loadMore('files')
			files.resolvePage(1, { entries: ['b'], cursor: 'cursor-2', isPaginated: false })
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getSnapshot().files.hasMore).toBe(false)
		})

		it('stops paging when a page yields nothing new, even if the provider echoes a cursor', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files'])

			files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: true })
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getSnapshot().files.hasMore).toBe(true)

			// The next page comes back empty but still carries a paginated cursor. Without
			// the guard this would leave hasMore true and a "Load more" button that no-ops.
			searchController.loadMore('files')
			files.resolvePage(1, { entries: [], cursor: 'cursor-2', isPaginated: true })
			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot().files).toEqual({
				status: 'loaded',
				entries: ['a'],
				cursor: 'cursor-2',
				hasMore: false,
				loadMoreFailed: false,
			})
		})

		it('appends the next page of results when loadMore is called', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files'])

			files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: true })
			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot().files).toEqual({
				status: 'loaded',
				entries: ['a'],
				cursor: 'cursor-1',
				hasMore: true,
				loadMoreFailed: false,
			})

			searchController.loadMore('files')

			files.resolvePage(1, { entries: ['b'], cursor: 'cursor-2', isPaginated: false })
			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot().files).toEqual({
				status: 'loaded',
				entries: ['a', 'b'],
				cursor: 'cursor-2',
				hasMore: false,
				loadMoreFailed: false,
			})
		})

		it('reports a loading state, with page 1 still visible, while the next page is in flight', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)
			const onChange = vi.fn()

			const searchController = new UnifiedSearchController(onChange)
			searchController.search('query', ['files'])

			files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: true })
			await vi.advanceTimersByTimeAsync(0)
			onChange.mockClear()

			// Page 2 is requested but has not resolved yet.
			searchController.loadMore('files')

			// The view needs a loading signal for the paging spinner, without losing
			// the results already on screen, and the adapter must be told at the start.
			expect(searchController.getSnapshot().files).toEqual({
				status: 'loading',
				entries: ['a'],
				cursor: 'cursor-1',
				hasMore: true,
				loadMoreFailed: false,
			})
			expect(onChange).toHaveBeenCalled()
			// Still visible, so it holds its display slot instead of dropping out and reappearing.
			expect(searchController.getRevealOrder()).toEqual(['files'])
		})

		it('re-dispatches with the stored cursor', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files'])

			files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: true })
			await vi.advanceTimersByTimeAsync(0)

			searchController.loadMore('files')

			expect(service.search).toHaveBeenLastCalledWith(expect.objectContaining({ type: 'files', query: 'query', cursor: 'cursor-1', limit: PAGE_SIZE }))
		})

		it('requests the configured page size on the initial category search', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files'])

			expect(service.search).toHaveBeenLastCalledWith(expect.objectContaining({ type: 'files', query: 'query', limit: PAGE_SIZE }))
		})

		it('flags a page-load failure without dropping the results already loaded', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files'])

			files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: true })
			await vi.advanceTimersByTimeAsync(0)

			searchController.loadMore('files')
			files.rejectPage(1, new Error('network'))
			await vi.advanceTimersByTimeAsync(0)

			// Results stay put, the category is still loaded, and hasMore stays true
			// so the next loadMore retries. The failure is surfaced on its own flag.
			expect(searchController.getSnapshot().files).toEqual({
				status: 'loaded',
				entries: ['a'],
				cursor: 'cursor-1',
				hasMore: true,
				loadMoreFailed: true,
			})
		})

		it('clears the failure flag when a later page loads successfully', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files'])

			files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: true })
			await vi.advanceTimersByTimeAsync(0)

			// A first loadMore fails and raises the flag.
			searchController.loadMore('files')
			files.rejectPage(1, new Error('network'))
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getSnapshot().files.loadMoreFailed).toBe(true)

			// Retrying succeeds and must clear the stale flag.
			searchController.loadMore('files')
			files.resolvePage(2, { entries: ['b'], cursor: 'cursor-2', isPaginated: false })
			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot().files).toEqual({
				status: 'loaded',
				entries: ['a', 'b'],
				cursor: 'cursor-2',
				hasMore: false,
				loadMoreFailed: false,
			})
		})

		it('does nothing when the category has no more pages', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files'])

			files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: false })
			await vi.advanceTimersByTimeAsync(0)

			searchController.loadMore('files')

			// The initial search is the only dispatch; loadMore must not fire another.
			expect(service.search).toHaveBeenCalledTimes(1)
		})

		it('does not mutate a snapshot already handed out when a later page loads', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files'])

			files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: true })
			await vi.advanceTimersByTimeAsync(0)

			// A consumer reads and holds the snapshot, the way the Vue adapter does.
			const firstPage = searchController.getSnapshot()

			searchController.loadMore('files')
			files.resolvePage(1, { entries: ['b'], cursor: 'cursor-2', isPaginated: false })
			await vi.advanceTimersByTimeAsync(0)

			// loadMore must not reach back into an array a previous getSnapshot()
			// already exposed; the earlier snapshot stays frozen.
			expect(firstPage.files.entries).toEqual(['a'])
		})

		it('hands out fresh state and entries references across a loadMore', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files'])

			files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: true })
			await vi.advanceTimersByTimeAsync(0)
			const before = searchController.getSnapshot()

			searchController.loadMore('files')
			files.resolvePage(1, { entries: ['b'], cursor: 'cursor-2', isPaginated: false })
			await vi.advanceTimersByTimeAsync(0)
			const after = searchController.getSnapshot()

			// A new object and array identity on each change is what lets the adapter's
			// ref reassignment re-render instead of aliasing a mutated object.
			expect(after.files).not.toBe(before.files)
			expect(after.files.entries).not.toBe(before.files.entries)
		})
	})

	describe('filter pass-through', () => {
		it('forwards per-category filter params on the initial search', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files'], {
				files: { since: '2026-01-01', until: '2026-02-01', person: 'alice', extraQueries: { tag: 'important' } },
			})

			expect(service.search).toHaveBeenCalledWith(expect.objectContaining({
				type: 'files',
				query: 'query',
				cursor: null,
				since: '2026-01-01',
				until: '2026-02-01',
				person: 'alice',
				extraQueries: { tag: 'important' },
			}))
		})

		it('reuses the stored filter params when paginating', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files'], {
				files: { since: '2026-01-01', until: '2026-02-01', person: 'alice', extraQueries: { tag: 'important' } },
			})

			files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: true })
			await vi.advanceTimersByTimeAsync(0)

			searchController.loadMore('files')

			// Page 2 must carry the same filters as page 1, not just type/query/cursor.
			expect(service.search).toHaveBeenLastCalledWith(expect.objectContaining({
				type: 'files',
				query: 'query',
				cursor: 'cursor-1',
				since: '2026-01-01',
				until: '2026-02-01',
				person: 'alice',
				extraQueries: { tag: 'important' },
			}))
		})

		it('dispatches the type override on both requests while keying state by category id', async () => {
			const files = pagedProvider()
			service.search.mockReturnValue(files)

			const searchController = new UnifiedSearchController()
			// 'in-folder' is a searchFrom-style alias that dispatches to the 'files' backend.
			searchController.search('query', ['in-folder'], { 'in-folder': { type: 'files' } })

			// The request dispatches to the override type...
			expect(service.search).toHaveBeenCalledWith(expect.objectContaining({ type: 'files' }))
			// ...but the category stays keyed by its own id, so two aliases can't collide.
			expect(Object.keys(searchController.getSnapshot())).toEqual(['in-folder'])

			files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: true })
			await vi.advanceTimersByTimeAsync(0)

			searchController.loadMore('in-folder')

			// loadMore carries the override too, not the category id.
			expect(service.search).toHaveBeenLastCalledWith(expect.objectContaining({ type: 'files', cursor: 'cursor-1' }))
		})
	})

	describe('reveal timer', () => {
		it('marks blocked categories as loaded after a certain amount of time has elapsed', async () => {
			const providers = mockProviders(['files', 'talk', 'deck'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk', 'deck'])

			providers.deck.resolve(['Deck result'])

			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot()).toEqual({
				files: loading,
				talk: loading,
				deck: { status: 'blocked', entries: ['Deck result'], cursor: null, hasMore: false, loadMoreFailed: false },
			})

			await vi.advanceTimersByTimeAsync(REVEAL_INTERVAL_MS)

			expect(searchController.getSnapshot()).toEqual({
				files: loading,
				talk: loading,
				deck: { status: 'loaded', entries: ['Deck result'], cursor: null, hasMore: false, loadMoreFailed: false },
			})
		})

		it('arms a fresh reveal window for each new search', async () => {
			const first = mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('first', ['files', 'talk'])

			// The first search spends its window on talk, then stands the timer down.
			first.talk.resolve(['First talk'])
			await vi.advanceTimersByTimeAsync(REVEAL_INTERVAL_MS)
			expect(searchController.getSnapshot().talk.status).toBe('loaded')
			expect(vi.getTimerCount()).toBe(0)

			// A new search must get its own window, otherwise its out-of-order categories
			// would stay blocked forever with no flush left to reveal them.
			const second = mockProviders(['files', 'talk'])
			searchController.search('second', ['files', 'talk'])
			second.talk.resolve(['Second talk'])
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getSnapshot().talk.status).toBe('blocked')

			await vi.advanceTimersByTimeAsync(REVEAL_INTERVAL_MS)
			expect(searchController.getSnapshot().talk.status).toBe('loaded')
		})

		it('stops the reveal timer once every category has resolved', async () => {
			const providers = mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk'])

			providers.files.resolve(['Files result'])
			providers.talk.resolve(['Talk result'])
			await vi.advanceTimersByTimeAsync(0)

			// Nothing is loading or blocked, so the next flush should not re-arm.
			await vi.advanceTimersByTimeAsync(REVEAL_INTERVAL_MS)
			expect(vi.getTimerCount()).toBe(0)
		})

		it('does not let a previous search\'s reveal timer fire against a new search', async () => {
			const first = mockProviders(['files', 'talk', 'deck'])

			const searchController = new UnifiedSearchController()
			searchController.search('first', ['files', 'talk', 'deck'])

			// First search: deck is blocked and its reveal timer is pending.
			first.deck.resolve(['First deck'])
			await vi.advanceTimersByTimeAsync(REVEAL_INTERVAL_MS - 500)
			expect(searchController.getSnapshot().deck.status).toBe('blocked')

			// A new search starts before the first timer fires. It must clear that
			// timer, otherwise the stale flush would reveal the new search's deck early.
			const second = mockProviders(['files', 'talk', 'deck'])
			searchController.search('second', ['files', 'talk', 'deck'])

			second.deck.resolve(['Second deck'])
			// Advance past when the first search's timer would have fired (500ms from
			// now) but before the second search's timer is due.
			await vi.advanceTimersByTimeAsync(REVEAL_INTERVAL_MS - 500)

			expect(searchController.getSnapshot().deck.status).toBe('blocked')
		})

		it('does not re-block a revealed category when a later category settles behind a still-loading one', async () => {
			const providers = mockProviders(['files', 'talk', 'deck'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk', 'deck'])

			// talk arrives out of order and is blocked behind files (still loading).
			providers.talk.resolve(['Talk result'])
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getSnapshot().talk.status).toBe('blocked')

			// The reveal timer promotes talk to loaded.
			await vi.advanceTimersByTimeAsync(REVEAL_INTERVAL_MS)
			expect(searchController.getSnapshot().talk.status).toBe('loaded')

			// deck now settles while files is still loading. Reconcile must not push the
			// already-revealed talk back to blocked (that flip-flop is the on-screen flicker);
			// talk stays loaded.
			providers.deck.resolve(['Deck result'])
			await vi.advanceTimersByTimeAsync(0)
			expect(searchController.getSnapshot().talk.status).toBe('loaded')
		})

		it('reveals a category that settles after the window closed straight away', async () => {
			const providers = mockProviders(['files', 'talk', 'deck', 'mail'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk', 'deck', 'mail'])

			// talk and deck settle behind the still-loading files and block.
			providers.talk.resolve(['Talk result'])
			providers.deck.resolve(['Deck result'])
			await vi.advanceTimersByTimeAsync(0)

			// The window closes and reveals them, with files and mail still in flight.
			await vi.advanceTimersByTimeAsync(REVEAL_INTERVAL_MS)
			expect(searchController.getRevealOrder()).toEqual(['talk', 'deck'])

			// mail lands right after. Ordered reveal is over, so it paints immediately at the
			// end instead of blocking behind files for another whole window.
			providers.mail.resolve(['Mail result'])
			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot().mail.status).toBe('loaded')
			expect(searchController.getRevealOrder()).toEqual(['talk', 'deck', 'mail'])
		})

		it('does not re-arm the reveal timer while a provider is still hung', async () => {
			const providers = mockProviders(['files', 'talk'])

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk'])

			providers.talk.resolve(['Talk result'])
			await vi.advanceTimersByTimeAsync(0)

			// The window reveals talk and closes for good. Nothing can block after that, so
			// there is nothing for a later cycle to do and the timer must stand down even
			// though files never resolved.
			await vi.advanceTimersByTimeAsync(REVEAL_INTERVAL_MS)

			expect(searchController.getSnapshot().files.status).toBe('loading')
			expect(vi.getTimerCount()).toBe(0)
		})

		it('does not block a category that settles while an earlier one is paging', async () => {
			const files = pagedProvider()
			const talk = deferredProvider()
			service.search.mockImplementation(({ type }: { type: string }) => (type === 'files' ? files : talk))

			const searchController = new UnifiedSearchController()
			searchController.search('query', ['files', 'talk'])

			// files lands with more pages to fetch; talk is still in flight when the window closes.
			files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: true })
			await vi.advanceTimersByTimeAsync(REVEAL_INTERVAL_MS)

			// The user pages files, which puts it back into 'loading' after the window closed.
			searchController.loadMore('files')

			// talk settles behind it. Ordered reveal is over, so a paging predecessor must not
			// block it: the window is one-shot, so nothing would ever release it again.
			talk.resolve(['Talk result'])
			await vi.advanceTimersByTimeAsync(0)

			expect(searchController.getSnapshot().talk.status).toBe('loaded')
			expect(searchController.getRevealOrder()).toEqual(['files', 'talk'])
		})
	})
})
