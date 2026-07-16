/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { shallowMount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'

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
