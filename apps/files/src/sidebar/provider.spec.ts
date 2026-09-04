/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ISidebarDataProvider } from './types.ts'

import { beforeEach, describe, expect, test, vi } from 'vitest'
import { computed, shallowRef } from 'vue'
import {
	getSidebarDataProvider,
	hasSidebarDataProvider,
	resetSidebarDataProvider,
	setSidebarDataProvider,
} from './provider.ts'

/**
 * Create a minimal data provider.
 */
function buildProvider(): ISidebarDataProvider {
	return {
		node: shallowRef(),
		folder: shallowRef(),
		view: shallowRef(),
		setNode: vi.fn(),
	}
}

describe('Sidebar data provider registry', () => {
	beforeEach(() => {
		resetSidebarDataProvider()
	})

	test('has no provider by default', () => {
		expect(hasSidebarDataProvider()).toBe(false)
		expect(getSidebarDataProvider()).toBeUndefined()
	})

	test('registers a provider', () => {
		const provider = buildProvider()
		setSidebarDataProvider(provider)

		expect(hasSidebarDataProvider()).toBe(true)
		expect(getSidebarDataProvider()).toBe(provider)
	})

	test('only allows one provider', () => {
		const provider = buildProvider()
		setSidebarDataProvider(provider)

		expect(() => setSidebarDataProvider(buildProvider())).toThrow()
		expect(getSidebarDataProvider()).toBe(provider)
	})

	test('registration is reactive', () => {
		const provider = buildProvider()
		const spy = vi.fn(() => getSidebarDataProvider())

		// the sidebar store reads the provider from computed properties,
		// so a provider registered later must invalidate them
		const current = computed(spy)
		expect(current.value).toBeUndefined()

		setSidebarDataProvider(provider)
		expect(current.value).toBe(provider)
	})
})
