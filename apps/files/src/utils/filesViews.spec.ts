/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, test } from 'vitest'
import { defaultView, hasPersonalFilesView } from './filesViews.ts'

describe('hasPersonalFilesView', () => {
	beforeEach(() => removeInitialState())

	test('enabled if user has unlimited quota', () => {
		mockInitialState('files', 'storageStats', { quota: -1 })
		expect(hasPersonalFilesView()).toBe(true)
	})

	test('enabled if user has limited quota', () => {
		mockInitialState('files', 'storageStats', { quota: 1234 })
		expect(hasPersonalFilesView()).toBe(true)
	})

	test('disabled if user has no quota', () => {
		mockInitialState('files', 'storageStats', { quota: 0 })
		expect(hasPersonalFilesView()).toBe(false)
	})
})

describe('defaultView', () => {
	beforeEach(removeInitialState)

	test('Returns files view if set', () => {
		mockInitialState('files', 'config', { default_view: 'files' })
		expect(defaultView()).toBe('files')
	})

	test('Returns personal view if set and enabled', () => {
		mockInitialState('files', 'config', { default_view: 'personal' })
		mockInitialState('files', 'storageStats', { quota: -1 })
		expect(defaultView()).toBe('personal')
	})

	test('Falls back to files if personal view is disabled', () => {
		mockInitialState('files', 'config', { default_view: 'personal' })
		mockInitialState('files', 'storageStats', { quota: 0 })
		expect(defaultView()).toBe('files')
	})
})

/**
 * Remove the mocked initial state
 */
function removeInitialState(): void {
	document.querySelectorAll('input[type="hidden"]').forEach((el) => {
		el.remove()
	})
	// clear the cache
	delete globalThis._nc_initial_state
}

/**
 * Helper to mock an initial state value
 * @param app - The app
 * @param key - The key
 * @param value - The value
 */
function mockInitialState(app: string, key: string, value: unknown): void {
	const el = document.createElement('input')
	el.value = btoa(JSON.stringify(value))
	el.id = `initial-state-${app}-${key}`
	el.type = 'hidden'

	document.head.appendChild(el)
}
