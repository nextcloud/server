/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

/**
 * Integration test for the public share initialisation.
 *
 * Unlike `openfile.spec.ts` (which unit-tests the query helper), this spec
 * imports the real `init.ts` and asserts on the arguments the page actually
 * hands to the router. That is the behaviour described in
 * https://github.com/nextcloud/server/issues/65814: the query of the incoming
 * URL must survive the navigation to the file list route.
 */

const goToRouteMock = vi.fn()
const setActiveMock = vi.fn()

// Mutable state the mocks below read lazily, so each test can set up its own
// incoming route (query/params) before `init.ts` is imported.
let mockQuery: Record<string, string> = {}
let mockParams: Record<string, string> = {}

vi.mock('@nextcloud/event-bus', () => ({
	emit: vi.fn(),
	subscribe: vi.fn(),
	unsubscribe: vi.fn(),
}))

vi.mock('@nextcloud/files', () => ({
	getNavigation: () => ({ setActive: setActiveMock }),
}))

vi.mock('@nextcloud/initial-state', () => ({
	loadState: (app: string, key: string, fallback?: unknown) => {
		switch (key) {
		case 'fileId': return 1234567
		case 'sharingToken': return 'AbCdE1234567890'
		case 'view': return 'files'
		default: return fallback
		}
	},
}))

vi.mock('../services/logger.ts', () => ({
	default: { debug: vi.fn(), error: vi.fn(), warn: vi.fn(), info: vi.fn() },
}))

vi.mock('./router.ts', () => ({
	default: {
		isReady: () => Promise.resolve(),
		push: vi.fn(() => Promise.resolve()),
		replace: vi.fn(() => Promise.resolve()),
		currentRoute: { query: {}, params: {} },
	},
}))

vi.mock('./views/publicFileDrop.ts', () => ({ default: () => {} }))
vi.mock('./views/publicFileShare.ts', () => ({ default: () => {} }))
vi.mock('./views/publicShare.ts', () => ({ default: () => {} }))

vi.mock('~/apps/files/src/services/RouterService.ts', () => ({
	default: class RouterServiceMock {
		constructor(router: unknown) {
			this.router = router
		}

		router: unknown

		get params(): Record<string, string> {
			return mockParams
		}

		get query(): Record<string, string> {
			return mockQuery
		}

		goToRoute = goToRouteMock
	},
}))

/**
 * Import `init.ts` fresh so its module level side effects run against the
 * mocks configured by the current test.
 */
async function importInit() {
	vi.resetModules()
	await import('./init.ts')
	// `init.ts` navigates in a `router.isReady().then(...)` continuation.
	await vi.waitFor(() => expect(goToRouteMock).toHaveBeenCalled())
}

describe('public share init (openfile handling)', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		mockQuery = {}
		mockParams = {}
		// `init.ts` assigns `window.OCP.Files.Router`; make sure a clean
		// `window.OCP` object exists for every test.
		window.OCP = { ...(window.OCP ?? {}) } as typeof window.OCP
	})

	it('navigates to the file list route for a single file share', async () => {
		await importInit()

		expect(goToRouteMock).toHaveBeenCalledTimes(1)
		const [name, params] = goToRouteMock.mock.calls[0]
		expect(name).toBe('filelist')
		expect(params).toEqual({ token: 'AbCdE1234567890', fileid: '1234567' })
	})

	it('defaults openfile to true when the URL has no openfile parameter', async () => {
		mockQuery = { dir: '/' }

		await importInit()

		const query = goToRouteMock.mock.calls[0][2]
		expect(query).toEqual({ dir: '/', openfile: 'true' })
	})

	it('keeps openfile=false from the incoming URL (regression #65814)', async () => {
		// This is the bug: the page used to always navigate with
		// `openfile: 'true'`, so a shared video was always autoplayed.
		mockQuery = { dir: '/', editing: 'false', openfile: 'false' }

		await importInit()

		const query = goToRouteMock.mock.calls[0][2]
		expect(query.openfile).toBe('false')
		expect(query).toEqual({ dir: '/', editing: 'false', openfile: 'false' })
	})

	it('keeps an explicit openfile=true from the incoming URL', async () => {
		mockQuery = { openfile: 'true' }

		await importInit()

		expect(goToRouteMock.mock.calls[0][2]).toEqual({ openfile: 'true' })
	})
})