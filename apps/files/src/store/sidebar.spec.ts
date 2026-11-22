/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode, ISidebarAction, ISidebarTab, IView } from '@nextcloud/files'
import type { ISidebarDataProvider } from '../sidebar/types.ts'

import { emit } from '@nextcloud/event-bus'
import { File, Folder } from '@nextcloud/files'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import { nextTick, shallowRef } from 'vue'
import { resetSidebarDataProvider, setSidebarDataProvider } from '../sidebar/provider.ts'
import { logger } from '../utils/logger.ts'
import { useSidebarStore } from './sidebar.ts'

const registry = vi.hoisted(() => ({
	tabs: [] as ISidebarTab[],
	actions: [] as ISidebarAction[],
}))

vi.mock('@nextcloud/files', async (original) => ({
	...(await original()),
	getSidebarTabs: () => registry.tabs,
	getSidebarActions: () => registry.actions,
}))

vi.mock('@nextcloud/dialogs')

const folder = new Folder({
	id: 2,
	source: 'https://cloud.example.com/remote.php/dav/files/test/folder',
	owner: 'test',
	root: '/files/test',
})

/**
 * Create a node within the test user root.
 *
 * @param name - The basename of the node
 * @param id - The file id of the node
 */
function buildNode(name: string, id = 1): INode {
	return new File({
		id,
		source: `https://cloud.example.com/remote.php/dav/files/test/folder/${name}`,
		owner: 'test',
		mime: 'text/plain',
		root: '/files/test',
	})
}

/**
 * Create a sidebar tab.
 *
 * @param id - The id of the tab
 * @param order - The order of the tab
 * @param enabled - Optional callback to enable the tab
 */
function buildTab(id: string, order: number, enabled?: ISidebarTab['enabled']): ISidebarTab {
	return {
		id,
		order,
		enabled,
		displayName: id,
		iconSvgInline: '<svg></svg>',
		tagName: `test-${id}`,
	}
}

/**
 * Create a data provider which keeps its state in memory.
 */
function buildProvider() {
	const node = shallowRef<INode>()
	return {
		node,
		folder: shallowRef<IFolder>(),
		view: shallowRef<IView>(),
		setNode: vi.fn((newNode?: INode) => {
			node.value = newNode
		}),
		onOpenStateChanged: vi.fn(),
	} satisfies ISidebarDataProvider
}

describe('Sidebar store', () => {
	let provider: ReturnType<typeof buildProvider>
	let store: ReturnType<typeof useSidebarStore>

	beforeEach(() => {
		vi.restoreAllMocks()
		setActivePinia(createPinia())
		registry.tabs = [buildTab('sharing', 10), buildTab('versions', 90)]
		registry.actions = []

		provider = buildProvider()
		setSidebarDataProvider(provider)
		store = useSidebarStore()
	})

	afterEach(() => {
		resetSidebarDataProvider()
	})

	test('is closed and without context by default', () => {
		expect(store.isOpen).toBe(false)
		expect(store.hasContext).toBe(false)
		expect(store.currentNode).toBeUndefined()
		expect(store.node).toBeUndefined()
		expect(store.currentContext).toBeUndefined()
		expect(store.currentTabs).toEqual([])
	})

	test('opens for a node and activates the first tab', () => {
		const node = buildNode('file.txt')
		store.open(node)

		expect(store.isOpen).toBe(true)
		expect(store.hasContext).toBe(true)
		expect(store.currentNode).toBe(node)
		// alias for the `ISidebar` interface
		expect(store.node).toBe(node)
		expect(store.activeTab).toBe('sharing')
		expect(provider.setNode).toHaveBeenCalledWith(node)
	})

	test('opens for a requested tab', () => {
		store.open(buildNode('file.txt'), 'versions')

		expect(store.activeTab).toBe('versions')
	})

	test('falls back to the first tab if the requested one is not available', () => {
		vi.spyOn(logger, 'warn').mockImplementation(() => {})

		store.open(buildNode('file.txt'), 'unknown')

		expect(store.activeTab).toBe('sharing')
		expect(logger.warn).toHaveBeenCalledOnce()
	})

	test('only switches the tab if already open for the same node', () => {
		const node = buildNode('file.txt')
		store.open(node)
		provider.setNode.mockClear()

		store.open(node.clone(), 'versions')

		expect(provider.setNode).not.toHaveBeenCalled()
		expect(store.activeTab).toBe('versions')
	})

	test('provides the context of the data provider', () => {
		provider.folder.value = folder
		provider.view.value = { id: 'files' } as IView

		store.open(buildNode('file.txt'))

		expect(store.currentContext).toMatchObject({
			node: store.currentNode,
			folder,
			view: { id: 'files' },
		})
	})

	test('provides a context without folder and view', () => {
		const node = buildNode('file.txt')
		store.open(node)

		expect(store.currentContext).toStrictEqual({
			node,
			folder: undefined,
			view: undefined,
		})
	})

	test('filters tabs and actions for a context without folder and view', () => {
		registry.tabs = [
			buildTab('sharing', 10, ({ folder, view }) => folder === undefined && view === undefined),
			buildTab('versions', 90, () => false),
		]

		store.open(buildNode('file.txt'))

		expect(store.currentTabs.map(({ id }) => id)).toEqual(['sharing'])
	})

	test('skips a tab which cannot handle the context', () => {
		vi.spyOn(logger, 'error').mockImplementation(() => {})
		registry.tabs = [
			buildTab('sharing', 10),
			buildTab('broken', 20, ({ folder }) => folder!.permissions > 0),
		]

		store.open(buildNode('file.txt'))

		expect(store.currentTabs.map(({ id }) => id)).toEqual(['sharing'])
		expect(logger.error).toHaveBeenCalled()
	})

	test('sorts tabs by their order', () => {
		registry.tabs = [buildTab('last', 100), buildTab('first', 1)]

		store.open(buildNode('file.txt'))

		expect(store.currentTabs.map(({ id }) => id)).toEqual(['first', 'last'])
		expect(store.activeTab).toBe('first')
	})

	test('rejects setting a tab which is not available', () => {
		store.open(buildNode('file.txt'))

		expect(() => store.setActiveTab('unknown')).toThrow()
		expect(store.activeTab).toBe('sharing')
	})

	test('notifies the data provider about the open state', async () => {
		store.open(buildNode('file.txt'))
		await nextTick()
		expect(provider.onOpenStateChanged).toHaveBeenCalledWith(true)

		store.close()
		await nextTick()
		expect(provider.onOpenStateChanged).toHaveBeenCalledWith(false)
	})

	test('has no context while closed', () => {
		store.open(buildNode('file.txt'))
		store.close()

		expect(store.isOpen).toBe(false)
		expect(store.hasContext).toBe(false)
		expect(store.currentNode).toBeUndefined()
	})

	test('updates the current node when it was changed', () => {
		const node = buildNode('file.txt')
		store.open(node)

		const updated = node.clone()
		updated.attributes.favorite = 1
		emit('files:node:updated', updated)

		expect(provider.setNode).toHaveBeenCalledWith(updated)
		expect(store.currentNode?.attributes.favorite).toBe(1)
	})

	test('ignores updates of other nodes', () => {
		store.open(buildNode('file.txt'))
		provider.setNode.mockClear()

		emit('files:node:updated', buildNode('other.txt', 5))

		expect(provider.setNode).not.toHaveBeenCalled()
	})

	test('closes if the current node is deleted', () => {
		const node = buildNode('file.txt')
		store.open(node)

		emit('files:node:deleted', node)

		expect(store.isOpen).toBe(false)
	})

	test('renders fullscreen on request', () => {
		expect(store.isFullScreen).toBe(false)

		store.setFullScreenMode(true)
		expect(store.isFullScreen).toBe(true)

		store.setFullScreenMode(false)
		expect(store.isFullScreen).toBe(false)
	})
})

describe('Sidebar store without a data provider', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		resetSidebarDataProvider()
	})

	test('cannot be opened', () => {
		const store = useSidebarStore()

		expect(() => store.open(buildNode('file.txt'))).toThrow()
		expect(store.isOpen).toBe(false)
	})
})
