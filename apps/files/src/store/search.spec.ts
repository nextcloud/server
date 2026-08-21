/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { emit as Emit } from '@nextcloud/event-bus'

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'

vi.mock('@nextcloud/event-bus', () => ({ emit: vi.fn(), subscribe: vi.fn() }))

let emit: typeof Emit

describe('Search store on pages without a file list', () => {
	beforeEach(async () => {
		vi.resetModules()
		setActivePinia(createPinia())
		// other apps can load the files scripts without rendering a file list
		window.OCP = { Files: {} } as unknown as typeof window.OCP;
		({ emit } = await import('@nextcloud/event-bus'))
	})

	it('is set up without a router', async () => {
		const { useSearchStore } = await import('./search.ts')

		expect(() => useSearchStore()).not.toThrow()
	})

	it('only notifies about an updated search', async () => {
		const { useSearchStore } = await import('./search.ts')
		const store = useSearchStore()

		store.scope = 'globally'
		store.query = 'report'
		await nextTick()

		expect(emit).toHaveBeenCalledWith('files:search:updated', { query: 'report', scope: 'globally' })
	})
})
