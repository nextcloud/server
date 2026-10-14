/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { enableAutoUnmount, flushPromises, mount, shallowMount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

const service = vi.hoisted(() => ({
	defaultLimit: 2,
	minSearchLength: 1,
	enableLiveSearch: true,
	regexFilterIn: /(^|\s)in:([a-z_-]+)/ig,
	regexFilterNot: /(^|\s)-in:([a-z_-]+)/ig,
	getTypes: vi.fn(),
	search: vi.fn(),
}))
vi.mock('../../services/LegacyUnifiedSearchService.ts', () => service)
vi.mock('@nextcloud/event-bus', () => ({ emit: vi.fn(), subscribe: vi.fn(), unsubscribe: vi.fn() }))
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn() }))

import LegacyUnifiedSearch from '../../views/LegacyUnifiedSearch.vue'

interface Page {
	entries: { title: string, resourceUrl: string }[]
	cursor?: number | null
	isPaginated?: boolean
}

/**
 * Let the search of a provider answer with the given pages, one per request.
 *
 * @param pages - The pages per provider
 */
function respondWith(pages: Record<string, Page[]>) {
	service.search.mockImplementation(({ type }: { type: string }) => {
		const page = pages[type]!.shift()!
		return {
			request: () => Promise.resolve({ data: { ocs: { data: { cursor: null, isPaginated: false, ...page } } } }),
			cancel: vi.fn(),
		}
	})
}

/**
 * @param count - How many entries to create
 * @param prefix - Prefix of the entries
 */
function entries(count: number, prefix = 'file') {
	return Array.from({ length: count }, (_, index) => ({ title: `${prefix}-${index}`, resourceUrl: `/${prefix}/${index}` }))
}

/**
 * Mount the search and run a search for the query.
 *
 * @param query - The search query
 */
async function searchFor(query: string) {
	const wrapper = shallowMount(LegacyUnifiedSearch)
	await flushPromises()
	wrapper.vm.query = query
	await wrapper.vm.onInput()
	await flushPromises()
	return wrapper
}

enableAutoUnmount(afterEach)

beforeEach(() => {
	vi.clearAllMocks()
	window.OCP = { Accessibility: { disableKeyboardShortcuts: () => true } }
	service.getTypes.mockResolvedValue([{ id: 'files', name: 'Files' }, { id: 'mail', name: 'Mail' }])
})

describe('LegacyUnifiedSearch results', () => {
	it('keeps the results per provider and leaves out providers without any', async () => {
		respondWith({ files: [{ entries: entries(1) }], mail: [{ entries: [] }] })

		const wrapper = await searchFor('file')

		expect(Object.keys(wrapper.vm.results)).toEqual(['files'])
		expect(wrapper.vm.orderedResults).toEqual([{ type: 'files', list: entries(1) }])
		expect(wrapper.vm.isLoading).toBe(false)
	})

	it('pages a paginated provider through its cursor', async () => {
		respondWith({
			files: [{ entries: entries(2), cursor: 2, isPaginated: true }, { entries: entries(1, 'more'), cursor: 3, isPaginated: true }],
			mail: [{ entries: [] }],
		})
		const wrapper = await searchFor('file')
		expect(wrapper.vm.reached.files).toBeUndefined()

		await wrapper.vm.loadMore('files')

		expect(service.search).toHaveBeenLastCalledWith({ type: 'files', query: 'file', cursor: 2 })
		expect(wrapper.vm.results.files).toEqual([...entries(2), ...entries(1, 'more')])
		// A page shorter than the limit is the last one
		expect(wrapper.vm.reached.files).toBe(true)
	})

	it('reveals the results of a provider without pagination in steps of the limit', async () => {
		respondWith({ files: [{ entries: entries(5) }], mail: [{ entries: [] }] })
		const wrapper = await searchFor('file')

		expect(wrapper.vm.limitIfAny(wrapper.vm.results.files, 'files')).toHaveLength(2)

		await wrapper.vm.loadMore('files')
		expect(wrapper.vm.limitIfAny(wrapper.vm.results.files, 'files')).toHaveLength(4)
		expect(wrapper.vm.reached.files).toBeUndefined()

		await wrapper.vm.loadMore('files')
		expect(wrapper.vm.limitIfAny(wrapper.vm.results.files, 'files')).toHaveLength(5)
		expect(wrapper.vm.reached.files).toBe(true)
		// Everything was loaded with the first request
		expect(service.search).toHaveBeenCalledTimes(2)
	})

	it('only searches the providers the query is restricted to', async () => {
		respondWith({ mail: [{ entries: entries(1, 'mail') }] })

		const wrapper = await searchFor('hello in:mail')

		expect(service.search).toHaveBeenCalledExactlyOnceWith({ type: 'mail', query: 'hello' })
		expect(Object.keys(wrapper.vm.results)).toEqual(['mail'])
	})

	it('forgets the results once the query is too short again', async () => {
		respondWith({ files: [{ entries: entries(1) }], mail: [{ entries: [] }] })
		const wrapper = await searchFor('file')

		wrapper.vm.query = ''
		await wrapper.vm.onInput()

		expect(wrapper.vm.results).toEqual({})
		expect(wrapper.vm.hasResults).toBe(false)
	})
})

describe('LegacyUnifiedSearch menu', () => {
	it('focuses the search field once the menu is opened', async () => {
		const wrapper = mount(LegacyUnifiedSearch, { attachTo: document.body })
		await flushPromises()

		await wrapper.get('button').trigger('click')
		await flushPromises()
		await vi.waitFor(() => {
			expect(document.activeElement).toBe(wrapper.get('input').element)
		})
	})
})
