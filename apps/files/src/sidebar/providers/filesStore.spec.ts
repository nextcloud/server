/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { emit as Emit } from '@nextcloud/event-bus'
import type { Pinia } from 'pinia'
import type { fetchNode as FetchNode } from '../../services/WebdavClient.ts'
import type { useActiveStore as UseActiveStore } from '../../store/active.ts'
import type { useFilesStore as UseFilesStore } from '../../store/files.ts'
import type { useSidebarStore as UseSidebarStore } from '../../store/sidebar.ts'
import type { logger as Logger } from '../../utils/logger.ts'
import type { ISidebarDataProvider } from '../types.ts'

import { File, Folder } from '@nextcloud/files'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, test, vi } from 'vitest'

vi.mock('@nextcloud/auth')
vi.mock('../../services/WebdavClient.ts', () => ({ fetchNode: vi.fn() }))

const folder = new Folder({
	id: 2,
	source: 'https://cloud.example.com/remote.php/dav/files/test/folder',
	owner: 'test',
	root: '/files/test',
})

const node = new File({
	id: 1,
	source: 'https://cloud.example.com/remote.php/dav/files/test/folder/file.txt',
	owner: 'test',
	mime: 'text/plain',
	root: '/files/test',
})

// the node as fetched from the WebDAV API, to tell it apart from the loaded one
const fetchedNode = new File({
	id: 1,
	source: 'https://cloud.example.com/remote.php/dav/files/test/folder/file.txt',
	owner: 'test',
	mime: 'text/plain',
	root: '/files/test',
	attributes: { fetched: true },
})

// the provider subscribes to the event bus, so every test needs a fresh module state
let emit: typeof Emit
let fetchNode: typeof FetchNode
let logger: typeof Logger
let useActiveStore: typeof UseActiveStore
let useFilesStore: typeof UseFilesStore
let useSidebarStore: typeof UseSidebarStore

describe('Files app sidebar data provider', () => {
	let pinia: Pinia
	let provider: ISidebarDataProvider
	let router: { goToRoute: ReturnType<typeof vi.fn>, params: object, query: object }

	beforeEach(async () => {
		vi.restoreAllMocks()
		vi.resetModules()
		// the event bus is stored on the window, so it outlives `resetModules()`
		// and would keep the providers of previous tests subscribed
		Reflect.deleteProperty(window, '_nc_event_bus')
		pinia = createPinia()
		setActivePinia(pinia)

		router = { goToRoute: vi.fn(), params: {}, query: {} }
		window.OCP = { Files: { Router: router } } as unknown as typeof window.OCP;

		({ emit } = await import('@nextcloud/event-bus'));
		({ fetchNode } = await import('../../services/WebdavClient.ts'));
		({ logger } = await import('../../utils/logger.ts'));
		({ useActiveStore } = await import('../../store/active.ts'));
		({ useFilesStore } = await import('../../store/files.ts'));
		({ useSidebarStore } = await import('../../store/sidebar.ts'))
		vi.mocked(fetchNode).mockResolvedValue(fetchedNode)

		const { createFilesStoreDataProvider } = await import('./filesStore.ts')
		const { setSidebarDataProvider } = await import('../provider.ts')
		provider = createFilesStoreDataProvider(pinia)
		setSidebarDataProvider(provider)
	})

	test('provides the active node, folder and view', () => {
		const activeStore = useActiveStore(pinia)
		activeStore.activeFolder = folder
		activeStore.activeView = { id: 'files' } as never
		provider.setNode(node)

		expect(provider.node.value).toBe(node)
		expect(provider.folder.value).toBe(folder)
		expect(provider.view.value).toMatchObject({ id: 'files' })
	})

	test('sets the node as active node of the files app', () => {
		provider.setNode(node)
		expect(useActiveStore(pinia).activeNode).toBe(node)

		provider.setNode()
		expect(useActiveStore(pinia).activeNode).toBeUndefined()
	})

	describe('Nodes requested by the Viewer', () => {
		test('opens the sidebar for a node of the current view', async () => {
			useFilesStore(pinia).updateNodes([node])

			emit('viewer:sidebar:open', node)
			await vi.waitUntil(() => useSidebarStore(pinia).isOpen)

			// the Viewer only provides a partial node, so the loaded one is used
			expect(useSidebarStore(pinia).currentNode).toBe(node)
			expect(fetchNode).not.toHaveBeenCalled()
		})

		test('fetches a node which is not loaded', async () => {
			emit('viewer:sidebar:open', node)
			await vi.waitUntil(() => useSidebarStore(pinia).isOpen)

			expect(fetchNode).toHaveBeenCalledWith(node.path)
			expect(useSidebarStore(pinia).currentNode).toBe(fetchedNode)
		})

		test('reports a node which cannot be resolved', async () => {
			vi.spyOn(logger, 'error').mockImplementation(() => {})
			vi.mocked(fetchNode).mockRejectedValue(new Error('Not found'))

			emit('viewer:sidebar:open', node)
			await vi.waitUntil(() => vi.mocked(logger.error).mock.calls.length > 0)

			expect(useSidebarStore(pinia).isOpen).toBe(false)
		})
	})

	describe('URL synchronization', () => {
		test('adds the "opendetails" parameter when opened', () => {
			provider.onOpenStateChanged!(true)

			expect(router.goToRoute).toHaveBeenCalledOnce()
			expect(router.goToRoute.mock.calls[0][2]).toStrictEqual({ opendetails: 'true' })
		})

		test('removes the "opendetails" parameter when closed', () => {
			router.query = { opendetails: 'true', dir: '/folder' }

			provider.onOpenStateChanged!(false)

			expect(router.goToRoute).toHaveBeenCalledOnce()
			expect(router.goToRoute.mock.calls[0][2]).toStrictEqual({ dir: '/folder' })
		})

		test('does not touch the URL if it already matches the open state', () => {
			router.query = { opendetails: 'true' }
			provider.onOpenStateChanged!(true)
			expect(router.goToRoute).not.toHaveBeenCalled()

			router.query = {}
			provider.onOpenStateChanged!(false)
			expect(router.goToRoute).not.toHaveBeenCalled()
		})
	})
})
