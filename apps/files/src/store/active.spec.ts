/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Folder } from '@nextcloud/files'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import { nextTick } from 'vue'
import { useActiveStore } from './active.ts'

function buildFolder(id: number, name: string) {
	return new Folder({
		id,
		owner: 'test',
		source: `http://example.com/remote.php/dav/files/test/${name}`,
		root: '/files/test',
	})
}

describe('Active store syncs the route fileid with the active node', () => {
	let goToRoute: ReturnType<typeof vi.fn>

	beforeEach(() => {
		setActivePinia(createPinia())
		goToRoute = vi.fn()
		window.OCP = { Files: { Router: { goToRoute, params: {}, query: {} } } } as unknown as typeof window.OCP
	})

	test('rewrites a stale child fileid when the current folder becomes active', async () => {
		// The route still deep-links a child (79) left over from a previous
		// location, while the sidebar is being opened for the current folder (78).
		window.OCP.Files.Router.params = { fileid: '79' }

		const store = useActiveStore()
		const folder = buildFolder(78, 'parent')
		store.activeFolder = folder
		store.activeNode = folder
		await nextTick()

		expect(goToRoute).toHaveBeenCalledTimes(1)
		expect(goToRoute.mock.calls[0][1]).toMatchObject({ fileid: '78' })
	})

	test('does not touch the route when it already points at the active node', async () => {
		window.OCP.Files.Router.params = { fileid: '78' }

		const store = useActiveStore()
		const folder = buildFolder(78, 'parent')
		store.activeFolder = folder
		store.activeNode = folder
		await nextTick()

		expect(goToRoute).not.toHaveBeenCalled()
	})
})
