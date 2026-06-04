/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'

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
	var OC: { setNavigationCounter?: (id: string, count: number) => void }
}

// The id the bootstrap mounts into (must match main.ts).
function addContainer(): void {
	const container = document.createElement('nav')
	container.id = 'header-start__appmenu'
	document.body.appendChild(container)
}

describe('core: appmenu', () => {
	beforeEach(() => {
		document.body.innerHTML = ''
		globalThis.OC = {}
		vi.resetModules()
	})

	it('mounts AppMenu when the container is present', async () => {
		addContainer()

		await import('../appmenu.ts')

		// Vue 2 $mount replaces the container with AppMenu's root <nav class="app-menu">.
		expect(document.querySelector('.app-menu')).not.toBeNull()
	})

	it('no-ops when the container is missing', async () => {
		await import('../appmenu.ts')

		expect(document.body.children.length).toBe(0)
	})

	it('exposes OC.setNavigationCounter as a callable function', async () => {
		addContainer()

		await import('../appmenu.ts')

		expect(typeof globalThis.OC.setNavigationCounter).toBe('function')
	})
})
