/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h } from 'vue'

const service = vi.hoisted(() => ({
	search: vi.fn(),
	getProviders: vi.fn(),
	getContacts: vi.fn(),
}))
vi.mock('../../services/UnifiedSearchService.js', () => service)

import { useUnifiedSearch } from '../../composables/useUnifiedSearch.ts'

/**
 * Deferred stand-in for a provider's `search()` return value. Resolve it to
 * make that provider arrive on demand. Mirrors the controller spec's helper.
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
 * Deferred stand-in that serves successive pages, for exercising loadMore.
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
	}
}

/**
 * Register one deferred provider per category type on the mocked service.
 */
function mockProviders(types: string[]) {
	const providers = Object.fromEntries(types.map((type) => [type, deferredProvider()]))
	service.search.mockImplementation(({ type }: { type: string }) => providers[type])
	return providers
}

/**
 * Run the composable inside a real setup context (so onBeforeUnmount is wired)
 * and hand the returned API plus the wrapper back to the test.
 */
function mountComposable() {
	let api!: ReturnType<typeof useUnifiedSearch>
	const wrapper = mount(defineComponent({
		setup() {
			api = useUnifiedSearch()
			return () => h('div')
		},
	}))
	return { wrapper, api }
}

beforeEach(() => {
	vi.useFakeTimers()
})

afterEach(() => {
	vi.clearAllMocks()
	vi.useRealTimers()
})

describe('useUnifiedSearch', () => {
	it('exposes an empty snapshot before any search', () => {
		const { api } = mountComposable()

		expect(api.searchStates.value).toEqual({})
	})

	it('reflects controller state reactively as a search resolves', async () => {
		const providers = mockProviders(['files'])
		const { api } = mountComposable()

		api.search('query', ['files'])
		providers.files.resolve(['a result'])
		await vi.advanceTimersByTimeAsync(0)

		expect(api.searchStates.value.files).toMatchObject({
			status: 'loaded',
			entries: ['a result'],
		})
	})

	it('appends a page through loadMore and reflects it', async () => {
		const files = pagedProvider()
		service.search.mockReturnValue(files)
		const { api } = mountComposable()

		api.search('query', ['files'])
		files.resolvePage(0, { entries: ['a'], cursor: 'cursor-1', isPaginated: true })
		await vi.advanceTimersByTimeAsync(0)

		api.loadMore('files')
		files.resolvePage(1, { entries: ['b'], cursor: 'cursor-2', isPaginated: false })
		await vi.advanceTimersByTimeAsync(0)

		expect(api.searchStates.value.files).toMatchObject({
			status: 'loaded',
			entries: ['a', 'b'],
			hasMore: false,
		})
	})

	it('cancels in-flight requests when the component unmounts', () => {
		const providers = mockProviders(['files'])
		const { wrapper, api } = mountComposable()

		api.search('query', ['files'])
		wrapper.destroy()

		// Unmount must dispose the controller, which cancels the pending request.
		expect(providers.files.cancel).toHaveBeenCalledOnce()
	})
})
