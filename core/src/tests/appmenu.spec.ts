/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@nextcloud/initial-state', () => ({
	loadState: (_app: string, key: string, fallback: unknown) => key === 'apps'
		? [{ id: 'files', app: 'files', active: false, order: 0, href: '/apps/files', icon: '/apps/files/img/app.svg', type: 'link', name: 'Files', unread: 0 }]
		: fallback,
}))
vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ isAdmin: false }),
}))
vi.mock('@nextcloud/event-bus', () => ({
	subscribe: () => undefined,
	unsubscribe: () => undefined,
}))
vi.mock('@nextcloud/router', () => ({
	generateUrl: (path: string) => path,
	imagePath: (app: string, file: string) => `/${app}/img/${file}`,
}))

const logger = vi.hoisted(() => ({
	warn: vi.fn(),
}))
vi.mock('../utils/logger.ts', () => ({ logger }))

type SetNavigationCounter = (id: string, counter: number) => void

/**
 * Get the counter setter the app menu adds to the OC global, if any.
 */
function getSetNavigationCounter(): SetNavigationCounter | undefined {
	return (window.OC as { setNavigationCounter?: SetNavigationCounter }).setNavigationCounter
}

/**
 * Add the placeholder the app menu is mounted in place of.
 */
function addPlaceholder(): void {
	const placeholder = document.createElement('nav')
	placeholder.id = 'header-start__appmenu'
	document.body.appendChild(placeholder)
}

describe('core: appmenu', () => {
	beforeEach(() => {
		document.body.innerHTML = ''
		window.OC = {} as typeof window.OC
		logger.warn.mockClear()
		vi.resetModules()
	})

	it('mounts the app menu in place of the placeholder', async () => {
		addPlaceholder()

		await import('../appmenu.ts')

		expect(document.querySelector('.app-menu')).not.toBeNull()
		expect(document.getElementById('header-start__appmenu')).toBeNull()
	})

	it('does nothing without the placeholder', async () => {
		await import('../appmenu.ts')

		expect(document.body.children.length).toBe(0)
		expect(getSetNavigationCounter()).toBeUndefined()
	})

	it('forwards OC.setNavigationCounter to the app menu', async () => {
		addPlaceholder()

		await import('../appmenu.ts')

		const setNavigationCounter = getSetNavigationCounter()!
		setNavigationCounter('files', 3)
		expect(logger.warn).not.toHaveBeenCalled()

		setNavigationCounter('unknown', 3)
		expect(logger.warn).toHaveBeenCalledOnce()
	})
})
