/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { beforeEach, vi } from 'vitest'

// Ambient Nextcloud globals referenced by some components without importing them.
// Only set them when missing so the server's own test globals are never clobbered.
type NcGlobal = typeof globalThis & {
	t?: (app: string, text: string) => string
	n?: (app: string, singular: string, plural: string, count: number) => string
	OCA?: { Files?: Record<string, unknown> }
}

// jsdom does not implement ResizeObserver, which the viewer sets up on mount.
globalThis.ResizeObserver ??= class {
	observe() {}
	unobserve() {}
	disconnect() {}
}

const g = globalThis as NcGlobal
g.t ??= (_app: string, text: string) => text
g.n ??= (_app: string, singular: string, plural: string, count: number) => (count === 1 ? singular : plural)
g.OCA ??= {}
g.OCA.Files ??= {}

// Reset the viewer handler registry between tests so registrations never leak,
// and clear mock call history (shared manual mocks keep their implementation).
beforeEach(() => {
	vi.clearAllMocks()
	// @ts-expect-error test-only reset of the global handler map
	window._oca_viewer_handlers = new Map()
	// @ts-expect-error test-only reset of the global viewer instance
	window._oca_viewer_service = undefined
})
