/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, test, vi } from 'vitest'
import { getSidebarSharedState } from './sharedState.ts'

describe('Sidebar shared state', () => {
	beforeEach(() => {
		vi.resetModules()
		delete window.OCA.Files
	})

	test('is stored on the sidebar namespace', () => {
		const state = getSidebarSharedState()

		expect(window.OCA.Files!.Sidebar!._sharedState).toBe(state)
		expect(state.dataProvider.value).toBeUndefined()
		expect(state.standaloneProvider).toBeUndefined()
		expect(state.instance).toBeUndefined()
	})

	test('is only created once', () => {
		expect(getSidebarSharedState()).toBe(getSidebarSharedState())
	})

	test('keeps the existing files namespace', () => {
		window.OCA.Files = { Settings: 'untouched' } as unknown as typeof window.OCA.Files

		getSidebarSharedState()

		expect(window.OCA.Files).toMatchObject({ Settings: 'untouched' })
		expect(window.OCA.Files!.Sidebar!._sharedState).toBeDefined()
	})

	test('is shared between entry points', async () => {
		// `files-main` and `files-sidebar` are separate Webpack bundles, so both
		// contain their own instance of this module - the state must still be the same
		const filesMain = await import('./sharedState.ts')
		vi.resetModules()
		const filesSidebar = await import('./sharedState.ts')

		expect(filesMain.getSidebarSharedState).not.toBe(filesSidebar.getSidebarSharedState)
		expect(filesMain.getSidebarSharedState()).toBe(filesSidebar.getSidebarSharedState())
	})
})
