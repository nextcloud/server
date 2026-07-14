/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import { useViewConfigStore } from './viewConfig.ts'

vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'test', displayName: 'Test' }),
}))
vi.mock('@nextcloud/axios', () => ({
	default: {
		put: vi.fn(),
	},
}))
vi.mock('@nextcloud/router', () => ({
	generateUrl: (url: string) => url,
}))
vi.mock('@nextcloud/initial-state', () => ({
	loadState: () => ({}),
}))

describe('View config store keeps sorting state consistent under slow requests', () => {
	// Captured resolvers for the pending PUT requests, so a test can decide when
	// (and in which order) the "server" responds.
	let putResolvers: Array<() => void>

	beforeEach(() => {
		setActivePinia(createPinia())
		putResolvers = []
		vi.mocked(axios.put).mockImplementation(() => new Promise((resolve) => {
			putResolvers.push(() => resolve({ data: {} } as never))
		}))
	})

	test('reflects a sorting change locally without waiting for the server round-trip', () => {
		const store = useViewConfigStore()

		store.setSortingBy('size', 'files')

		// The PUT requests are still pending, yet the local config is already
		// updated so the header and file list can react immediately.
		expect(putResolvers.length).toBeGreaterThan(0)
		expect(store.getConfig('files')).toMatchObject({
			sorting_mode: 'size',
			sorting_direction: 'asc',
		})
	})

	test('consecutive direction toggles read the freshly updated local state', () => {
		const store = useViewConfigStore()

		// Rapid header clicks: sort by size (asc), then flip twice. Each toggle
		// must read the direction the previous action just wrote locally, even
		// though none of the PUTs have resolved yet.
		store.setSortingBy('size', 'files')
		expect(store.getConfig('files').sorting_direction).toBe('asc')

		store.toggleSortingDirection('files')
		expect(store.getConfig('files').sorting_direction).toBe('desc')

		store.toggleSortingDirection('files')
		expect(store.getConfig('files').sorting_direction).toBe('asc')
	})

	test('an out-of-order server response does not clobber the local state', async () => {
		const store = useViewConfigStore()

		store.setSortingBy('size', 'files')
		store.toggleSortingDirection('files')
		store.toggleSortingDirection('files')

		expect(store.getConfig('files').sorting_direction).toBe('asc')

		// Let the queued PUTs resolve in reverse order: since the local store is
		// no longer driven by request resolution, the latest value must survive.
		for (const resolve of [...putResolvers].reverse()) {
			resolve()
		}
		await Promise.resolve()

		expect(store.getConfig('files').sorting_direction).toBe('asc')
	})
})
