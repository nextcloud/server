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

import UnifiedSearchModal from '../../components/UnifiedSearch/UnifiedSearchModal.vue'

let searchSpy: ReturnType<typeof vi.fn>
let loadMoreSpy: ReturnType<typeof vi.fn>
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
	composable.api = { searchStates, search: searchSpy, loadMore: loadMoreSpy }
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

		expect(wrapper.findAll('.result-title').wrappers.map((w) => w.text())).toEqual(['Files'])
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
		// ...and the empty state reads as searching, not "no results".
		expect(wrapper.vm.emptyContentMessage).toContain('Searching')

		// Once initialized, the same query dispatches normally.
		wrapper.vm.initialized = true
		wrapper.vm.find('hello')
		expect(searchSpy).toHaveBeenCalledOnce()
		expect(searchSpy.mock.calls[0][1]).toEqual(['files'])
	})

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

	it('auto-selects the first result once results arrive', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: '/a' }, { resourceUrl: '/b' }])

		expect(wrapper.vm.activeIndex).toBe(0)
		expect(wrapper.vm.activeDescendantId).toBe('unified-search-result-files-0')
	})

	it('moves the selection down and up through the flat result list, clamping at the ends', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: '/a' }, { resourceUrl: '/b' }])

		wrapper.vm.moveActive('next')
		expect(wrapper.vm.activeIndex).toBe(1)
		wrapper.vm.moveActive('next')
		expect(wrapper.vm.activeIndex).toBe(1)
		wrapper.vm.moveActive('prev')
		expect(wrapper.vm.activeIndex).toBe(0)
		wrapper.vm.moveActive('prev')
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

		wrapper.vm.moveActive('next')
		// Second row lives in the next provider group.
		expect(wrapper.vm.activeDescendantId).toBe('unified-search-result-talk-0')
	})

	it('opens the active result by its resourceUrl on activate', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: '/a' }, { resourceUrl: '/b' }])
		const open = vi.spyOn(wrapper.vm, 'openResourceUrl').mockImplementation(() => {})

		wrapper.vm.moveActive('next')
		wrapper.vm.activateActive()

		expect(open).toHaveBeenCalledWith('/b')
	})

	it('does nothing on activate when there is no active row', () => {
		const wrapper = factory()
		const open = vi.spyOn(wrapper.vm, 'openResourceUrl').mockImplementation(() => {})

		wrapper.vm.activateActive()

		expect(open).not.toHaveBeenCalled()
	})

	it('emits the active descendant id upward for the input to reference', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: '/a' }])

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
		wrapper.vm.moveActive('next')
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

		wrapper.vm.moveActive('next')
		await wrapper.vm.$nextTick()

		expect(secondRow.scrollIntoView).toHaveBeenCalled()
		secondRow.remove()
	})

	it('exposes each result group as a listbox and marks the selected row as active', async () => {
		const wrapper = factory()
		await withRows(wrapper, [{ resourceUrl: '/a' }, { resourceUrl: '/b' }])

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

	it('exposes no navigable rows while the empty state is shown, even with stale results', async () => {
		const wrapper = factory()
		wrapper.vm.providers = [{ id: 'files', name: 'Files', order: 0 }]
		// Results linger in the controller from a previous query...
		searchStates.value = { files: loaded([{ resourceUrl: '/a' }]) }
		// ...but the query is empty, so the empty state renders and no rows exist.
		wrapper.vm.searchQuery = ''
		await wrapper.vm.$nextTick()

		expect(wrapper.vm.showEmptyContentInfo).toBe(true)
		expect(wrapper.vm.navigableRows).toEqual([])
		expect(wrapper.vm.activeDescendantId).toBeNull()
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
