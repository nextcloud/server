/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { shallowMount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'

// @nextcloud/vue's Window._nc_focus_trap augmentation is not in this test's program,
// so reach the shared trap stack through a cast. onEscapeKey only compares identity,
// so the seeded traps need no real focus-trap type.
function setTrapStack(traps: unknown[]) {
	(window as unknown as { _nc_focus_trap: unknown[] })._nc_focus_trap = traps
}

const mobile = ref(false)
vi.mock('@nextcloud/vue/composables/useIsMobile', () => ({
	useIsSmallMobile: () => mobile,
	useIsMobile: () => ref(false),
}))
// Controllable stand-in for the composable so tests can drive searchStates and
// spy on the commands the modal issues to the controller.
const composable = vi.hoisted(() => ({ api: {} as Record<string, unknown> }))
vi.mock('../../composables/useUnifiedSearch.ts', () => ({
	useUnifiedSearch: () => composable.api,
}))
vi.mock('@nextcloud/event-bus', () => ({ subscribe: vi.fn(), emit: vi.fn() }))
vi.mock('@nextcloud/initial-state', () => ({
	loadState: vi.fn((_app: string, _key: string, fallback: unknown) => fallback),
}))
vi.mock('../../services/UnifiedSearchService.js', () => ({
	getProviders: vi.fn(() => Promise.resolve([])),
	getContacts: vi.fn(() => Promise.resolve([])),
	search: vi.fn(() => ({ request: () => Promise.resolve({ data: { ocs: { data: { entries: [] } } } }), cancel: vi.fn() })),
}))
vi.mock('../../store/unified-search-external-filters.js', () => ({
	useSearchStore: () => ({ externalFilters: [], scopeToApp: false }),
}))
// The real module builds a logger at import time (detectUser() and all), and the modal
// logs on provider init. Stubbed to keep the unit off that dependency and out of the
// test output; the vi.fn()s also leave log calls assertable if a test ever needs them.
vi.mock('../../logger.js', () => ({
	unifiedSearchLogger: { debug: vi.fn(), error: vi.fn() },
}))

import UnifiedSearchModal from '../../components/UnifiedSearch/UnifiedSearchModal.vue'

let searchSpy: ReturnType<typeof vi.fn>
let loadMoreSpy: ReturnType<typeof vi.fn>
let resetSpy: ReturnType<typeof vi.fn>
let searchStates: ReturnType<typeof ref>

// VTU v1 (the legacy Vue 2.7 project) has no flushPromises export; drain the
// microtask + timer queue so resolved provider fetches and their .then run.
const flushPromises = () => new Promise((resolve) => setTimeout(resolve))

/**
 * A loaded, non-empty category state for the snapshot.
 */
function loaded(entries: unknown[], hasMore = false) {
	return { status: 'loaded', entries, cursor: hasMore ? 'cursor-1' : null, hasMore, loadMoreFailed: false }
}

function factory(open = true) {
	return shallowMount(UnifiedSearchModal, {
		propsData: { open, query: '', localSearch: false },
		global: { mocks: { t: (_: string, s: string) => s, n: (_: string, s: string) => s } },
	})
}

beforeEach(() => {
	mobile.value = false
	searchSpy = vi.fn()
	loadMoreSpy = vi.fn()
	searchStates = ref({})
	// Faithful stand-in for the composable's reset: like the real one, it empties
	// the reactive snapshot the modal renders from.
	resetSpy = vi.fn(() => {
		searchStates.value = {}
	})
	composable.api = { searchStates, search: searchSpy, loadMore: loadMoreSpy, reset: resetSpy }
})
afterEach(() => vi.clearAllMocks())

describe('UnifiedSearchModal mobile input', () => {
	it('renders the search field only on mobile', () => {
		mobile.value = true
		expect(factory().findComponent({ name: 'NcTextField' }).exists()).toBe(true)
	})

	it('has no in-modal search field on desktop', () => {
		expect(factory().findComponent({ name: 'NcTextField' }).exists()).toBe(false)
	})

	it('mobile close button requests close', async () => {
		mobile.value = true
		const wrapper = factory()
		wrapper.find('.unified-search-modal__mobile-input').findComponent({ name: 'NcButton' }).vm.$emit('click')
		await wrapper.vm.$nextTick()
		expect(wrapper.emitted('update:open')?.at(-1)).toEqual([false])
	})

	// Guards the kebab event binding: Vue 2.7 does not normalize v-on names, so a
	// camelCase listener would silently miss NcTextField's `trailing-button-click`.
	it('mobile clear (trailing) button empties the query', async () => {
		mobile.value = true
		const wrapper = factory()
		wrapper.vm.searchQuery = 'hello'
		await wrapper.vm.$nextTick()
		wrapper.findComponent({ name: 'NcTextField' }).vm.$emit('trailing-button-click')
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.searchQuery).toBe('')
	})

	it('onMobileSearchInput normalises the emitted value to a string', () => {
		mobile.value = true
		const wrapper = factory()
		wrapper.vm.onMobileSearchInput(5)
		expect(wrapper.vm.searchQuery).toBe('5')
	})
})

describe('UnifiedSearchModal controller wiring', () => {
	it('renders a group per loaded, non-empty category and withholds the rest', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [
			{ id: 'files', name: 'Files', order: 0 },
			{ id: 'talk', name: 'Talk', order: 1 },
			{ id: 'deck', name: 'Deck', order: 2 },
		]
		searchStates.value = {
			files: loaded([{ resourceUrl: '/a' }]),
			talk: { status: 'blocked', entries: [{ resourceUrl: '/b' }], cursor: null, hasMore: false, loadMoreFailed: false },
			deck: loaded([]),
		}
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()

		// files: loaded + non-empty -> shown. talk: blocked -> withheld. deck: empty -> dropped.
		const titles = wrapper.findAll('.result-title').wrappers.map((w) => w.text())
		expect(titles).toEqual(['Files'])
	})

	it('keeps a paging category on screen while its next page loads', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		// loadMore keeps page 1 visible but flips the category to 'loading' for the
		// paging spinner. The group (and its rows) must not disappear during the refetch.
		searchStates.value = {
			files: { status: 'loading', entries: [{ resourceUrl: '/a' }], cursor: 'cursor-1', hasMore: true, loadMoreFailed: false },
		}
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()

		// The already-loaded row stays on screen while the next page loads.
		expect(wrapper.findAllComponents({ name: 'SearchResult' })).toHaveLength(1)
		expect(wrapper.vm.showEmptyContentInfo).toBe(false)
	})

	it('find() searches the provider ids in order and delegates to the controller', () => {
		const wrapper = factory()
		wrapper.vm.providers = [
			{ id: 'files', name: 'Files', order: 0 },
			{ id: 'talk', name: 'Talk', order: 1 },
		]
		wrapper.vm.initialized = true
		wrapper.vm.searchQuery = 'hello'
		wrapper.vm.find('hello')

		expect(searchSpy).toHaveBeenCalledOnce()
		const [query, categories] = searchSpy.mock.calls[0]
		expect(query).toBe('hello')
		expect(categories).toEqual(['files', 'talk'])
	})

	it('find() forwards a searchFrom type override and extraQueries per provider', () => {
		const wrapper = factory()
		wrapper.vm.providers = [
			{ id: 'files', name: 'Files', order: 0, extraParams: { foo: 'bar' } },
			{ id: 'in-folder', name: 'In folder', order: 1, searchFrom: 'files' },
		]
		wrapper.vm.initialized = true
		wrapper.vm.searchQuery = 'hello'
		wrapper.vm.find('hello')

		const params = searchSpy.mock.calls[0][2]
		// searchFrom alias dispatches to 'files' via the type override...
		expect(params['in-folder']).toMatchObject({ type: 'files' })
		// ...a plain provider carries no override, and extraParams flow through as extraQueries.
		expect(params.files).toMatchObject({ extraQueries: { foo: 'bar' } })
		expect(params.files.type).toBeUndefined()
	})

	it('find() converts the date filter to ISO strings and forwards person for compatible providers', () => {
		const wrapper = factory()
		wrapper.vm.providers = [
			{ id: 'files', name: 'Files', order: 0, filters: { since: true, until: true, person: true } },
		]
		wrapper.vm.initialized = true
		wrapper.vm.dateFilter = {
			id: 'date',
			type: 'date',
			text: '',
			startFrom: new Date('2026-01-01T00:00:00.000Z'),
			endAt: new Date('2026-02-01T00:00:00.000Z'),
		}
		wrapper.vm.personFilter = { id: 'person', type: 'person', name: 'Alice', user: 'alice' }
		wrapper.vm.filters = [wrapper.vm.dateFilter, wrapper.vm.personFilter]
		wrapper.vm.searchQuery = 'hello'
		wrapper.vm.find('hello')

		expect(searchSpy.mock.calls[0][2].files).toMatchObject({
			since: '2026-01-01T00:00:00.000Z',
			until: '2026-02-01T00:00:00.000Z',
			person: 'alice',
		})
	})

	it('find() omits external providers that were not manually selected', () => {
		const wrapper = factory()
		wrapper.vm.providers = [
			{ id: 'files', name: 'Files', order: 0 },
			{ id: 'ext', name: 'External', order: 1, isExternalProvider: true },
		]
		wrapper.vm.initialized = true
		wrapper.vm.searchExternalResources = false
		wrapper.vm.filteredProviders = []
		wrapper.vm.searchQuery = 'hello'
		wrapper.vm.find('hello')

		expect(searchSpy.mock.calls[0][1]).toEqual(['files'])
	})

	it('loadMore delegates to the controller with the provider id', () => {
		const wrapper = factory()
		wrapper.vm.loadMoreResultsForProvider({ id: 'files' })

		expect(loadMoreSpy).toHaveBeenCalledWith('files')
	})

	it('hides stale results and shows the prompt when the query drops below the minimum length', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		wrapper.vm.minSearchLength = 3
		// The controller still holds results from a previous, longer query...
		searchStates.value = { files: loaded([{ resourceUrl: '/a' }]) }
		wrapper.vm.searchQuery = 'ab'
		await wrapper.vm.$nextTick()

		// ...but they must not render for a query too short to have produced them.
		expect(wrapper.findAll('.result-title')).toHaveLength(0)
		expect(wrapper.vm.showEmptyContentInfo).toBe(true)
	})

	it('keeps the minimum-length prompt over "searching" when a query shrinks mid-flight', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		wrapper.vm.minSearchLength = 3
		// A search started for a longer query is still loading when the query shrinks.
		searchStates.value = { files: { status: 'loading', entries: [], cursor: null, hasMore: false, loadMoreFailed: false } }
		wrapper.vm.searchQuery = 'ab'
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.searching).toBe(true)
		// The too-short prompt wins over the "searching" message.
		expect(wrapper.vm.emptyContentMessage).toContain('Minimum search length')
	})

	it('defers a search until providers are initialized and reports it as searching', () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		wrapper.vm.searchQuery = 'hello'

		// Providers have not loaded yet: a search here would dispatch against an empty
		// list and settle instantly into "no results". It must be withheld instead...
		wrapper.vm.find('hello')
		expect(searchSpy).not.toHaveBeenCalled()
		// ...and it reads as busy (input spinner), with no in-modal loading text.
		expect(wrapper.vm.isBusy).toBe(true)
		expect(wrapper.vm.showEmptyContentInfo).toBe(false)

		// Once initialized, the same query dispatches normally.
		wrapper.vm.initialized = true
		wrapper.vm.find('hello')
		expect(searchSpy).toHaveBeenCalledOnce()
		expect(searchSpy.mock.calls[0][1]).toEqual(['files'])
	})
})

describe('UnifiedSearchModal reset on close', () => {
	it('clears the controller results when the modal closes, so nothing stale renders on the next open', async () => {
		const wrapper = factory() // starts open
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		// A previous search left results in the still-mounted controller.
		searchStates.value = { files: loaded([{ resourceUrl: '/a' }]) }
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.results).toHaveLength(1)

		// Closing must reset the controller (the modal never unmounts, so dispose never
		// runs). The next open then starts empty instead of flashing the old results.
		await wrapper.setProps({ open: false })

		expect(resetSpy).toHaveBeenCalledOnce()
		expect(wrapper.vm.results).toEqual([])
	})

	it('stops reporting busy and cancels the pending search when it closes with the query kept', async () => {
		const wrapper = factory() // starts open
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		wrapper.vm.initialized = true
		// Typing schedules the debounced search; pendingSearch reports busy across the gap.
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.isBusy).toBe(true)

		// The pending debounce must be cancelled on close so it can't dispatch for a shut modal.
		const cancelPending = vi.spyOn(wrapper.vm.debouncedFind, 'clear')

		// searchLocally-style close: keep the query, just shut the popover.
		await wrapper.setProps({ open: false })

		expect(cancelPending).toHaveBeenCalled()
		// A closed modal must not report busy, or the always-mounted header input keeps spinning.
		expect(wrapper.vm.isBusy).toBe(false)
	})
})

describe('UnifiedSearchModal filter triggers', () => {
	// A category trigger is "active" when a filter of its kind is applied. The
	// provider/type bucket is anything that is not a date or person filter.
	it('derives per-category active flags from the applied filters', async () => {
		const wrapper = factory()
		expect(wrapper.vm.providerFilterActive).toBe(false)
		expect(wrapper.vm.dateFilterActive).toBe(false)
		expect(wrapper.vm.personFilterActive).toBe(false)

		wrapper.vm.filters = [
			{ id: 'date', type: 'date', text: 'Today' },
			{ id: 'alice', type: 'person', name: 'Alice' },
			{ id: 'files', type: 'provider', name: 'Files' },
		]
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.dateFilterActive).toBe(true)
		expect(wrapper.vm.personFilterActive).toBe(true)
		expect(wrapper.vm.providerFilterActive).toBe(true)
	})

	it('turns the Date trigger primary when a date filter is applied', async () => {
		const wrapper = factory()
		const dateTrigger = () => wrapper.findAllComponents({ name: 'NcActions' }).wrappers
			.find((w) => w.attributes('data-cy-unified-search-filter') === 'date')

		// Gray (secondary) with no date filter...
		expect(dateTrigger()!.props('variant')).toBe('secondary')

		wrapper.vm.filters = [{ id: 'date', type: 'date', text: 'Today' }]
		await wrapper.vm.$nextTick()

		// ...blue (primary) once one is applied.
		expect(dateTrigger()!.props('variant')).toBe('primary')
	})

	it('clicking a provider entry applies it via addProviderFilter', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', icon: '' }]
		await wrapper.vm.$nextTick()

		const spy = vi.spyOn(wrapper.vm, 'addProviderFilter')
		const providerEntry = wrapper.findAllComponents({ name: 'NcActionButton' }).wrappers
			.find((w) => w.text().includes('Files'))
		providerEntry!.vm.$emit('click')

		expect(spy).toHaveBeenCalledOnce()
		expect(wrapper.vm.filteredProviders.map((p) => p.id)).toContain('files')
	})
})

describe('UnifiedSearchModal filter row reveal', () => {
	const filterRow = (wrapper) => wrapper.find('[data-cy-unified-search-filters]')

	it('hides the filter row on a focused-but-empty query until the funnel reveals it', async () => {
		const wrapper = factory() // open, empty query, filtersRevealed defaults to false
		expect(wrapper.vm.showFilterRow).toBe(false)
		expect(filterRow(wrapper).isVisible()).toBe(false)

		// The header funnel relays through the parent as this prop.
		await wrapper.setProps({ filtersRevealed: true })
		expect(wrapper.vm.showFilterRow).toBe(true)
		expect(filterRow(wrapper).isVisible()).toBe(true)
	})

	it('shows the filter row as soon as there is a query', async () => {
		const wrapper = factory()
		wrapper.vm.searchQuery = 'abc'
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.showFilterRow).toBe(true)
		expect(filterRow(wrapper).isVisible()).toBe(true)
	})

	it('keeps the filter row shown when a filter is active on an empty query', async () => {
		const wrapper = factory()
		wrapper.vm.filters = [{ id: 'date', type: 'date', text: 'Today' }]
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.showFilterRow).toBe(true)
	})

	it('always shows the filter row on mobile', async () => {
		mobile.value = true
		const wrapper = factory()
		expect(wrapper.vm.showFilterRow).toBe(true)
	})
})

describe('UnifiedSearchModal header visibility', () => {
	const header = (wrapper) => wrapper.find('.unified-search-modal__header')

	/**
	 * Mount and let the provider init settle, so its logging cannot land after teardown.
	 */
	async function mounted(open = true) {
		const wrapper = factory(open)
		await flushPromises()
		return wrapper
	}

	// The header is padded, so leaving it mounted while empty puts a dead strip above
	// the content. It only takes space when it has the mobile field or the filter row.
	it('hides the header on a resting empty query', async () => {
		const wrapper = await mounted() // desktop, empty query, filtersRevealed defaults to false
		expect(wrapper.vm.showHeader).toBe(false)
		expect(header(wrapper).isVisible()).toBe(false)
	})

	it('shows the header once the funnel reveals the filter row', async () => {
		const wrapper = await mounted()
		await wrapper.setProps({ filtersRevealed: true })
		expect(wrapper.vm.showHeader).toBe(true)
		expect(header(wrapper).isVisible()).toBe(true)
	})

	// The detail view drops the filters, leaving the desktop header with nothing to show.
	it('hides the header in the desktop detail view', async () => {
		const wrapper = await mounted()
		await wrapper.setProps({ filtersRevealed: true })
		expect(wrapper.vm.showHeader).toBe(true)

		wrapper.vm.detailCategory = 'files'
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.showHeader).toBe(false)
		expect(header(wrapper).isVisible()).toBe(false)
	})

	// Mobile keeps it either way: the header carries the only search field on that layout.
	it('keeps the header on mobile even in the detail view', async () => {
		mobile.value = true
		const wrapper = await mounted()
		wrapper.vm.detailCategory = 'files'
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.showHeader).toBe(true)
		expect(header(wrapper).isVisible()).toBe(true)
	})
})

describe('UnifiedSearchModal controller wiring (init)', () => {
	it('runs a query typed before providers finished loading, once initialized', async () => {
		const { getProviders } = await import('../../services/UnifiedSearchService.js')
		;(getProviders as unknown as ReturnType<typeof vi.fn>).mockResolvedValueOnce([{ id: 'files', name: 'Files', order: 0 }])

		// Open with a query already present: the open() handler starts the async provider
		// fetch and calls find() before it resolves, so nothing is dispatched yet.
		const wrapper = shallowMount(UnifiedSearchModal, {
			propsData: { open: false, query: 'hello', localSearch: false },
			global: { mocks: { t: (_: string, s: string) => s, n: (_: string, s: string) => s } },
		})
		// The focus trap needs a tabbable node the stubbed panel lacks; skip it here.
		vi.spyOn(wrapper.vm, 'activateFocusTrap').mockImplementation(() => {})
		await wrapper.setProps({ open: true })
		expect(searchSpy).not.toHaveBeenCalled()

		// Providers resolve -> the deferred query runs on its own, no extra keystroke.
		await flushPromises()
		expect(searchSpy).toHaveBeenCalledOnce()
		expect(searchSpy.mock.calls[0][0]).toBe('hello')
		expect(searchSpy.mock.calls[0][1]).toEqual(['files'])
	})
})

describe('UnifiedSearchModal keyboard selection', () => {
	/**
	 * Seed the modal with one provider and the given rows, then let it settle.
	 */
	async function withRows(wrapper: ReturnType<typeof factory>, rows: unknown[]) {
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		searchStates.value = { files: loaded(rows) }
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()
	}

	it('has no active row before any results', () => {
		const wrapper = factory()

		expect(wrapper.vm.activeIndex).toBe(-1)
		expect(wrapper.vm.activeDescendantId).toBeNull()
	})

	it('auto-selects the first result', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: '/a' }, { resourceUrl: '/b' }])

		// The first row is selected as soon as results arrive (keyboard users act immediately).
		expect(wrapper.vm.activeIndex).toBe(0)
		expect(wrapper.vm.activeDescendantId).toBe('unified-search-result-files-0')
	})

	it('moves the selection down and up through the flat result list, clamping at the ends', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: '/a' }, { resourceUrl: '/b' }])

		expect(wrapper.vm.activeIndex).toBe(0) // first row auto-selected
		wrapper.vm.moveActive('next') // 0 → 1
		expect(wrapper.vm.activeIndex).toBe(1)
		wrapper.vm.moveActive('next') // clamp at the last row
		expect(wrapper.vm.activeIndex).toBe(1)
		wrapper.vm.moveActive('prev') // 1 → 0
		expect(wrapper.vm.activeIndex).toBe(0)
		wrapper.vm.moveActive('prev') // clamp at the first row
		expect(wrapper.vm.activeIndex).toBe(0)
	})

	it('jumps to the first and last rows', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: '/a' }, { resourceUrl: '/b' }, { resourceUrl: '/c' }])

		wrapper.vm.moveActive('last')
		expect(wrapper.vm.activeIndex).toBe(2)
		wrapper.vm.moveActive('first')
		expect(wrapper.vm.activeIndex).toBe(0)
	})

	it('flattens the selection index across provider groups in render order', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [
			{ id: 'files', name: 'Files', order: 0 },
			{ id: 'talk', name: 'Talk', order: 1 },
		]
		searchStates.value = {
			files: loaded([{ resourceUrl: '/a' }]),
			talk: loaded([{ resourceUrl: '/b' }]),
		}
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()

		// From the auto-selected first row (files-0), the next move crosses into the next group.
		wrapper.vm.moveActive('next') // 0 (files-0) → 1 (talk-0)
		expect(wrapper.vm.activeDescendantId).toBe('unified-search-result-talk-0')
	})

	it('opens the active result by its resourceUrl on activate', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: '/a' }, { resourceUrl: '/b' }])
		const open = vi.spyOn(wrapper.vm, 'openResourceUrl').mockImplementation(() => {})

		wrapper.vm.moveActive('next') // 0 (/a) → 1 (/b)
		wrapper.vm.activateActive()

		expect(open).toHaveBeenCalledWith('/b')
	})

	it('opens the first result on activate when nothing has been navigated to', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: '/a' }, { resourceUrl: '/b' }])
		const open = vi.spyOn(wrapper.vm, 'openResourceUrl').mockImplementation(() => {})

		// The first row is auto-selected, so Enter opens the top hit without any navigation.
		wrapper.vm.activateActive()

		expect(open).toHaveBeenCalledWith('/a')
	})

	it('does nothing on activate when there are no results', () => {
		const wrapper = factory()
		const open = vi.spyOn(wrapper.vm, 'openResourceUrl').mockImplementation(() => {})

		wrapper.vm.activateActive()

		expect(open).not.toHaveBeenCalled()
	})

	it('emits the active descendant id upward for the input to reference', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: '/a' }])
		wrapper.vm.moveActive('next')
		await wrapper.vm.$nextTick()

		expect(wrapper.emitted('update:activeDescendant')?.at(-1)).toEqual(['unified-search-result-files-0'])
	})

	it('keeps the selection on the same row when a later group settles below it', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [
			{ id: 'files', name: 'Files', order: 0 },
			{ id: 'talk', name: 'Talk', order: 1 },
		]
		searchStates.value = { files: loaded([{ resourceUrl: '/a' }, { resourceUrl: '/b' }]) }
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()
		wrapper.vm.moveActive('next') // 0 → 1
		expect(wrapper.vm.activeDescendantId).toBe('unified-search-result-files-1')

		// A lower-priority group arrives below; the selected row keeps its identity.
		searchStates.value = {
			files: loaded([{ resourceUrl: '/a' }, { resourceUrl: '/b' }]),
			talk: loaded([{ resourceUrl: '/c' }]),
		}
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.activeDescendantId).toBe('unified-search-result-files-1')
	})

	it('falls back to the first row when the selected row disappears', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: '/a' }, { resourceUrl: '/b' }, { resourceUrl: '/c' }])
		wrapper.vm.moveActive('last')
		expect(wrapper.vm.activeIndex).toBe(2)

		searchStates.value = { files: loaded([{ resourceUrl: '/a' }]) }
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.activeIndex).toBe(0)
	})

	it('scrolls the active row into view as the selection moves past the fold', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: '/a' }, { resourceUrl: '/b' }])
		// The row lives outside the stubbed SearchResult, so stand in a real element
		// with the option id the modal will look up.
		const secondRow = document.createElement('li')
		secondRow.id = 'unified-search-result-files-1'
		secondRow.scrollIntoView = vi.fn()
		document.body.appendChild(secondRow)

		wrapper.vm.moveActive('next') // -1 → 0
		wrapper.vm.moveActive('next') // 0 → 1 (below the fold)
		await wrapper.vm.$nextTick()

		expect(secondRow.scrollIntoView).toHaveBeenCalled()
		secondRow.remove()
	})

	it('exposes each result group as a listbox and marks the selected row as active', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: '/a' }, { resourceUrl: '/b' }])

		// The first row is highlighted on its own (auto-selected).
		expect(wrapper.findAll('[role=listbox]')).toHaveLength(1)
		const rows = wrapper.findAllComponents({ name: 'SearchResult' })
		expect(rows.at(0).props('elementId')).toBe('unified-search-result-files-0')
		expect(rows.at(0).props('active')).toBe(true)
		expect(rows.at(1).props('active')).toBe(false)
	})

	it('flattens filtered rows then partial-match rows with section-scoped ids', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [
			{ id: 'files', name: 'Files', order: 0, filters: { since: true, until: true } },
			{ id: 'talk', name: 'Talk', order: 1 },
		]
		searchStates.value = {
			files: loaded([{ resourceUrl: '/f1' }]),
			talk: loaded([{ resourceUrl: '/t1' }]),
		}
		// An active date filter splits the incompatible provider (talk) into the
		// partial-matches section, exercising the filtered-then-unfiltered concat.
		wrapper.vm.dateFilter = { id: 'date', type: 'date', text: '', startFrom: new Date('2026-01-01'), endAt: new Date('2026-02-01') }
		wrapper.vm.filters = [wrapper.vm.dateFilter]
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.navigableRows.map((row: { id: string }) => row.id)).toEqual([
			'unified-search-result-files-0',
			'unified-search-result-unfiltered-talk-0',
		])
	})

	it('does nothing on activate when the active row has no url', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: null }])
		const open = vi.spyOn(wrapper.vm, 'openResourceUrl').mockImplementation(() => {})

		wrapper.vm.activateActive()

		expect(open).not.toHaveBeenCalled()
	})

	it('keeps stale results out of an empty query: no placeholder, no navigable rows', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		// Results linger in the controller from a previous query...
		searchStates.value = { files: loaded([{ resourceUrl: '/a' }]) }
		// ...but nothing has been typed. The modal stays clean (no empty-state
		// placeholder, so the filter row can show pre-typing) and the stale results
		// never reach keyboard navigation.
		wrapper.vm.searchQuery = ''
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.showEmptyContentInfo).toBe(false)
		expect(wrapper.vm.navigableRows).toEqual([])
		expect(wrapper.vm.activeDescendantId).toBeNull()
	})

	// min-search-length can be 0, so "too short" is false for an empty query; the empty
	// case must still clear stale results (isSearchQueryTooShort alone would not).
	it('keeps stale results out of an empty query even when min-search-length is 0', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		wrapper.vm.minSearchLength = 0
		searchStates.value = { files: loaded([{ resourceUrl: '/a' }]) }
		wrapper.vm.searchQuery = ''
		await wrapper.vm.$nextTick()

		expect(wrapper.findAll('.result-title')).toHaveLength(0)
		expect(wrapper.vm.navigableRows).toEqual([])
	})

	it('does not select a row or expose a listbox on mobile', async () => {
		mobile.value = true
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		searchStates.value = { files: loaded([{ resourceUrl: '/a' }, { resourceUrl: '/b' }]) }
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()

		// No combobox on mobile, so nothing should be auto-highlighted...
		expect(wrapper.vm.activeIndex).toBe(-1)
		// ...and the group must not advertise a listbox no combobox owns.
		expect(wrapper.findAll('[role=listbox]')).toHaveLength(0)
	})
})

describe('UnifiedSearchModal live region', () => {
	it('announces progress while a category is still loading', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		wrapper.vm.initialized = true
		searchStates.value = { files: { status: 'loading', entries: [], cursor: null, hasMore: false, loadMoreFailed: false } }
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.liveMessage).toContain('Searching')
	})

	it('announces the count once the search settles with results', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		wrapper.vm.initialized = true
		searchStates.value = { files: loaded([{ resourceUrl: '/a' }, { resourceUrl: '/b' }]) }
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.liveMessage).toContain('result')
		expect(wrapper.vm.liveMessage).not.toContain('Searching')
	})

	it('announces no results when a settled search is empty', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		wrapper.vm.initialized = true
		searchStates.value = { files: loaded([]) }
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.liveMessage).toBe('No matching results')
	})

	it('stays silent when the modal is closed', () => {
		const wrapper = factory(false)

		expect(wrapper.vm.liveMessage).toBe('')
	})
})

describe('UnifiedSearchModal escape to close', () => {
	it('closes the search on Escape when no sub-overlay is open', () => {
		const wrapper = factory()

		wrapper.vm.onEscapeKey(new KeyboardEvent('keydown', { key: 'Escape', cancelable: true }))

		expect(wrapper.emitted('update:open')?.at(-1)).toEqual([false])
	})

	it('leaves Escape to an open action menu (Type / Date)', () => {
		// The Type/Date NcActions menus pause the trap stack without joining it, so the
		// stack-top check can't see them; onEscapeKey reads their open state directly.
		const wrapper = factory()
		wrapper.vm.dateActionMenuIsOpen = true

		wrapper.vm.onEscapeKey(new KeyboardEvent('keydown', { key: 'Escape', cancelable: true }))

		expect(wrapper.emitted('update:open')).toBeUndefined()
	})

	it('leaves Escape to an overlay sitting on top of the shared focus-trap stack', () => {
		// The People popover, date-range dialog and file picker push their own trap onto
		// window._nc_focus_trap. While one is on top of ours, Escape belongs to it.
		const wrapper = factory()
		const ourTrap = {} as unknown as NonNullable<typeof wrapper.vm.focusTrap>
		const overlayTrap = {} as typeof ourTrap
		wrapper.vm.focusTrap = ourTrap
		setTrapStack([ourTrap, overlayTrap])

		wrapper.vm.onEscapeKey(new KeyboardEvent('keydown', { key: 'Escape', cancelable: true }))

		expect(wrapper.emitted('update:open')).toBeUndefined()
		setTrapStack([])
	})

	it('closes on Escape when our trap is the top of the stack (no overlay open)', () => {
		const wrapper = factory()
		const ourTrap = {} as unknown as NonNullable<typeof wrapper.vm.focusTrap>
		wrapper.vm.focusTrap = ourTrap
		setTrapStack([ourTrap])

		wrapper.vm.onEscapeKey(new KeyboardEvent('keydown', { key: 'Escape', cancelable: true }))

		expect(wrapper.emitted('update:open')?.at(-1)).toEqual([false])
		setTrapStack([])
	})
})

describe('UnifiedSearchModal People filter', () => {
	// The @item-selected listener must catch SearchableList's emit (both kebab-case in
	// Vue 2.7); a casing mismatch would silently never apply the picked person.
	it('applies a person filter when the People popover reports a selection', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		wrapper.vm.initialized = true
		await wrapper.vm.$nextTick()

		wrapper.findComponent({ name: 'SearchableList' }).vm.$emit('item-selected', { id: 'u1', user: 'alice', displayName: 'Alice' })
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.filters.some((f: { type: string }) => f.type === 'person')).toBe(true)
	})
})

describe('UnifiedSearchModal result presentation', () => {
	/**
	 * Seed one category with the given rows and settle.
	 */
	async function withGroup(wrapper: ReturnType<typeof factory>, id: string, entries: unknown[], hasMore = false) {
		wrapper.vm.providers = [{ id, name: 'Files', order: 0 }]
		wrapper.vm.initialized = true
		searchStates.value = { [id]: loaded(entries, hasMore) }
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()
	}

	const rows = (n: number) => Array.from({ length: n }, (_, i) => ({ resourceUrl: `/r${i}` }))

	// The overflow heading is the only NcButton carrying the group's heading id, so we
	// find the "More from" control by that rather than a style class.
	const moreFromButton = (wrapper: ReturnType<typeof factory>) => wrapper.findAllComponents({ name: 'NcButton' }).wrappers
		.find((w) => w.attributes('id') === 'unified-search-result-files')

	const buttonWithText = (wrapper: ReturnType<typeof factory>, text: string) => wrapper.findAllComponents({ name: 'NcButton' }).wrappers
		.find((w) => w.text().includes(text))

	// Find the back control by its ariaLabel ("Back to all results").
	const backButton = (wrapper: ReturnType<typeof factory>) => wrapper.findAllComponents({ name: 'NcButton' }).wrappers
		.find((w) => w.props('ariaLabel') === 'Back to all results')

	it('caps a category at three rows in the aggregate view, and navigableRows follows', async () => {
		const wrapper = factory()
		await withGroup(wrapper, 'files', rows(5))

		expect(wrapper.findAllComponents({ name: 'SearchResult' })).toHaveLength(3)
		expect(wrapper.vm.navigableRows).toHaveLength(3)
	})

	it('flags overflow purely on the fetched count, not on the pagination cursor', async () => {
		const wrapper = factory()

		// More than the cap fetched: "More from" button.
		await withGroup(wrapper, 'files', rows(4))
		expect(wrapper.vm.renderedGroups[0].overflow).toBe(true)
		expect(moreFromButton(wrapper)).toBeTruthy()

		// At the cap but the provider still advertises a cursor (some do past their last page).
		// The button must NOT show: opening the detail view would be a dead end.
		await withGroup(wrapper, 'files', rows(2), true)
		expect(wrapper.vm.renderedGroups[0].overflow).toBe(false)
		expect(moreFromButton(wrapper)).toBeUndefined()

		// Exactly the cap and nothing more: plain title, no "More from".
		await withGroup(wrapper, 'files', rows(3), false)
		expect(wrapper.vm.renderedGroups[0].overflow).toBe(false)
		expect(moreFromButton(wrapper)).toBeUndefined()
	})

	it('opens the uncapped detail view from "More from" and back returns to the capped list', async () => {
		const wrapper = factory()
		await withGroup(wrapper, 'files', rows(5))

		moreFromButton(wrapper)!.vm.$emit('click')
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.detailCategory).toBe('files')
		// Uncapped in detail, and navigableRows matches the rendered rows (lockstep).
		expect(wrapper.findAllComponents({ name: 'SearchResult' })).toHaveLength(5)
		expect(wrapper.vm.navigableRows).toHaveLength(5)
		expect(wrapper.vm.liveMessage).toContain('Showing')

		backButton(wrapper)!.vm.$emit('click')
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.detailCategory).toBeNull()
		expect(wrapper.findAllComponents({ name: 'SearchResult' })).toHaveLength(3)
		expect(wrapper.vm.navigableRows).toHaveLength(3)
	})

	it('scrolls the results back to the top when returning from the detail view', async () => {
		const wrapper = factory()
		await withGroup(wrapper, 'files', rows(5))
		wrapper.vm.openDetailView({ id: 'files' })
		await wrapper.vm.$nextTick()

		// jsdom has no layout, so stand in for the scroll container and capture writes.
		const scrollWrites: number[] = []
		Object.defineProperty(wrapper.vm.$refs.resultsContainer, 'scrollTop', {
			configurable: true,
			get: () => 400,
			set: (value) => scrollWrites.push(value),
		})

		wrapper.vm.closeDetailView()
		await wrapper.vm.$nextTick()
		await wrapper.vm.$nextTick()

		expect(scrollWrites).toContain(0)
	})

	it('drops the filter row and titles the detail view with the category name', async () => {
		const wrapper = factory()
		await withGroup(wrapper, 'files', rows(5))

		moreFromButton(wrapper)!.vm.$emit('click')
		await wrapper.vm.$nextTick()

		// Filters are hidden while viewing one category's full results.
		expect(wrapper.vm.showFilterRow).toBe(false)
		// The category name titles the detail view (shown once, in the header).
		const title = wrapper.find('.unified-search-modal__detail-title')
		expect(title.exists()).toBe(true)
		expect(title.text()).toBe('Files')
	})

	it('pages the detail view through the controller loadMore', async () => {
		const wrapper = factory()
		// Over the cap so the aggregate shows "More from", and still paginating so the
		// detail view offers "Load more results".
		await withGroup(wrapper, 'files', rows(5), true)

		moreFromButton(wrapper)!.vm.$emit('click')
		await wrapper.vm.$nextTick()

		buttonWithText(wrapper, 'Load more results')!.vm.$emit('click')
		expect(loadMoreSpy).toHaveBeenCalledWith('files')
	})

	it('keeps the selected-row highlight working in the detail view', async () => {
		const wrapper = factory()
		await withGroup(wrapper, 'files', rows(5))
		moreFromButton(wrapper)!.vm.$emit('click')
		await wrapper.vm.$nextTick()

		// The first row is auto-selected; arrow keys drive the highlight in the detail view too.
		wrapper.vm.moveActive('next') // 0 → 1
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.activeDescendantId).toBe('unified-search-result-files-1')
		const second = wrapper.findAllComponents({ name: 'SearchResult' }).at(1)
		expect(second.props('active')).toBe(true)
	})

	it('leaves the detail view on a query change, a filter change, or a services toggle', async () => {
		const wrapper = factory()

		await withGroup(wrapper, 'files', rows(4))
		wrapper.vm.openDetailView({ id: 'files' })
		await wrapper.vm.$nextTick()
		wrapper.vm.searchQuery = 'other'
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.detailCategory).toBeNull()

		await withGroup(wrapper, 'files', rows(4))
		wrapper.vm.openDetailView({ id: 'files' })
		await wrapper.vm.$nextTick()
		wrapper.vm.filters = [{ id: 'date', type: 'date', text: 'Today' }]
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.detailCategory).toBeNull()

		await withGroup(wrapper, 'files', rows(4))
		wrapper.vm.openDetailView({ id: 'files' })
		await wrapper.vm.$nextTick()
		wrapper.vm.toggleExternalResources()
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.detailCategory).toBeNull()
	})

	it('leaves the detail view if the open category drops out of the results', async () => {
		const wrapper = factory()
		await withGroup(wrapper, 'files', rows(4))
		wrapper.vm.openDetailView({ id: 'files' })
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.detailCategory).toBe('files')

		searchStates.value = {}
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.detailCategory).toBeNull()
	})

	it('labels the connected-services button by the toggle state and re-runs find on toggle', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [
			{ id: 'files', name: 'Files', order: 0 },
			{ id: 'ext', name: 'External', order: 1, isExternalProvider: true },
		]
		wrapper.vm.initialized = true
		searchStates.value = { files: loaded([{ resourceUrl: '/a' }]) }
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()
		// Settle the debounced search so the button's !isBusy gate opens (pendingSearch clears).
		wrapper.vm.find('query')
		await wrapper.vm.$nextTick()

		expect(buttonWithText(wrapper, 'More from connected services')).toBeTruthy()

		wrapper.vm.toggleExternalResources()
		await wrapper.vm.$nextTick()

		expect(searchSpy).toHaveBeenCalled()
		expect(buttonWithText(wrapper, 'Less from connected services')).toBeTruthy()
	})

	it('returns focus to the search input after toggling connected services', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [
			{ id: 'files', name: 'Files', order: 0 },
			{ id: 'ext', name: 'External', order: 1, isExternalProvider: true },
		]
		wrapper.vm.initialized = true
		searchStates.value = { files: loaded([{ resourceUrl: '/a' }]) }
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()
		wrapper.vm.find('query')
		await wrapper.vm.$nextTick()

		// The toggle re-runs the search, which unmounts the button that held focus. In
		// shallowMount focusSearchInput can't move real DOM focus, so assert the modal
		// re-homes focus onto the input (the same recovery the detail-view controls use).
		const focusSpy = vi.spyOn(wrapper.vm, 'focusSearchInput')
		wrapper.vm.toggleExternalResources()
		await wrapper.vm.$nextTick()

		expect(focusSpy).toHaveBeenCalled()
	})

	it('offers the connected-services opt-in even when a query returns no results', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'ext', name: 'External', order: 0, isExternalProvider: true }]
		wrapper.vm.initialized = true
		searchStates.value = {}
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()
		// Dispatch the debounced search; it settles with no results, when the empty state should show.
		wrapper.vm.find('query')
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.showEmptyContentInfo).toBe(true)
		expect(buttonWithText(wrapper, 'connected services')).toBeTruthy()
	})

	it('no longer renders the connected-services switch in the filter row', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'ext', name: 'External', order: 0, isExternalProvider: true }]
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()

		expect(wrapper.findComponent({ name: 'NcCheckboxRadioSwitch' }).exists()).toBe(false)
	})
})

describe('UnifiedSearchModal loading state', () => {
	const loadingState = { status: 'loading', entries: [], cursor: null, hasMore: false, loadMoreFailed: false }

	it('is busy while a category loads, with no in-modal loading text, and settles when done', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		wrapper.vm.initialized = true
		searchStates.value = { files: loadingState }
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()
		// The debounce fires and dispatches the real search, clearing the pending flag; from
		// here the controller's loading state alone drives busy.
		wrapper.vm.find('query')
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.isBusy).toBe(true)
		// The empty-content block stays hidden while busy.
		expect(wrapper.vm.showEmptyContentInfo).toBe(false)

		searchStates.value = { files: loaded([{ resourceUrl: '/a' }]) }
		await wrapper.vm.$nextTick()
		expect(wrapper.vm.isBusy).toBe(false)
	})

	it('stays busy through the debounce window so it does not flash the empty state', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		wrapper.vm.initialized = true
		// A fresh query, but the debounced find() (and thus the loading state) has not run yet.
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.pendingSearch).toBe(true)
		expect(wrapper.vm.isBusy).toBe(true)
		expect(wrapper.vm.showEmptyContentInfo).toBe(false)

		// When the search actually dispatches, the pending window ends and searching takes over.
		wrapper.vm.find('query')
		expect(wrapper.vm.pendingSearch).toBe(false)
	})

	it('is not busy for an empty or too-short query even if a stale request is loading', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		wrapper.vm.initialized = true
		wrapper.vm.minSearchLength = 3
		searchStates.value = { files: loadingState }
		wrapper.vm.searchQuery = 'ab'
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.isBusy).toBe(false)
	})

	it('emits update:loading as the busy state changes', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		wrapper.vm.initialized = true
		searchStates.value = { files: loadingState }
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()
		// The debounce fires and dispatches; the pending flag clears and the loading category
		// alone keeps it busy.
		wrapper.vm.find('query')
		await wrapper.vm.$nextTick()
		expect(wrapper.emitted('update:loading')?.at(-1)).toEqual([true])

		searchStates.value = { files: loaded([{ resourceUrl: '/a' }]) }
		await wrapper.vm.$nextTick()
		expect(wrapper.emitted('update:loading')?.at(-1)).toEqual([false])
	})

	it('shows a spinner in the mobile input while busy', async () => {
		mobile.value = true
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		wrapper.vm.initialized = true
		searchStates.value = { files: loadingState }
		wrapper.vm.searchQuery = 'query'
		await wrapper.vm.$nextTick()

		expect(wrapper.findComponent({ name: 'NcLoadingIcon' }).exists()).toBe(true)
	})
})
