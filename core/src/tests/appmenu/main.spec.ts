/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'

// Match every transitive dependency reachable from AppMenu.vue so its module
// graph resolves cleanly inside JSDOM. We do NOT mock `vue`: the legacy Vitest
// config aliases `vue` to Vue 2.7, which matches what the production webpack
// bundle resolves to. Using the real Vue exercises the actual mount path.
vi.mock('@nextcloud/initial-state', () => ({
	loadState: () => [],
}))
vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ isAdmin: false }),
}))
vi.mock('@nextcloud/event-bus', () => ({
	subscribe: () => undefined,
	unsubscribe: () => undefined,
}))
vi.mock('@nextcloud/l10n', () => ({
	isRTL: () => false,
	n: (_app: string, singular: string) => singular,
	t: (_app: string, text: string) => text,
}))
vi.mock('@nextcloud/router', () => ({
	generateUrl: (url: string) => url,
	imagePath: (_app: string, file: string) => `/img/${file}`,
}))

declare global {
	// eslint-disable-next-line no-var
	var OC: { setNavigationCounter?: (id: string, count: number) => void }
}

// The id the bootstrap mounts into (must match main.ts).
function addContainer(): void {
	const container = document.createElement('nav')
	container.id = 'header-start__appmenu'
	document.body.appendChild(container)
}

describe('appmenu/main', () => {
	beforeEach(() => {
		document.body.innerHTML = ''
		globalThis.OC = {}
		vi.resetModules()
	})

	it('mounts AppMenu when the container is present', async () => {
		addContainer()

		await import('../../appmenu/main.ts')

		// Vue 2 $mount replaces the container with AppMenu's root <nav class="app-menu">.
		expect(document.querySelector('.app-menu')).not.toBeNull()
	})

	it('no-ops when the container is missing', async () => {
		await import('../../appmenu/main.ts')

		expect(document.body.children.length).toBe(0)
	})

	it('exposes OC.setNavigationCounter as a callable function', async () => {
		addContainer()

		await import('../../appmenu/main.ts')

		expect(typeof globalThis.OC.setNavigationCounter).toBe('function')
	})
})
