/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, beforeEach, test, expect } from '@jest/globals'
import { setActivePinia, createPinia } from 'pinia'
import { usePathsStore } from './paths.ts'
import { emit } from '@nextcloud/event-bus'
import { File, Folder } from '@nextcloud/files'
import { useFilesStore } from './files.ts'

describe('Path store', () => {

	let store: ReturnType<typeof usePathsStore>
	let files: ReturnType<typeof useFilesStore>
	let root: Folder & { _children?: string[] }

	beforeEach(() => {
		setActivePinia(createPinia())

		root = new Folder({ owner: 'test', source: 'http://example.com/remote.php/dav/files/test/', id: 1 })
		files = useFilesStore()
		files.setRoot({ service: 'files', root })

		store = usePathsStore()
	})

	test('Folder is created', () => {
		// no defined paths
		expect(store.paths).toEqual({})

		// create the folder
		const node = new Folder({ owner: 'test', source: 'http://example.com/remote.php/dav/files/test/folder', id: 2 })
		emit('files:node:created', node)

		// see that the path is added
		expect(store.paths).toEqual({ files: { [node.path]: node.source } })

		// see that the node is added
		expect(root._children).toEqual([node.source])
	})

	test('File is created', () => {
		// no defined paths
		expect(store.paths).toEqual({})

		// create the file
		const node = new File({ owner: 'test', source: 'http://example.com/remote.php/dav/files/test/file.txt', id: 2, mime: 'text/plain' })
		emit('files:node:created', node)

		// see that there are still no paths
		expect(store.paths).toEqual({})

		// see that the node is added
		expect(root._children).toEqual([node.source])
	})

	test('Existing file is created', () => {
		// no defined paths
		expect(store.paths).toEqual({})

		// create the file
		const node1 = new File({ owner: 'test', source: 'http://example.com/remote.php/dav/files/test/file.txt', id: 2, mime: 'text/plain' })
		emit('files:node:created', node1)

		// see that there are still no paths
		expect(store.paths).toEqual({})

		// see that the node is added
		expect(root._children).toEqual([node1.source])

		// create the same named file again
		const node2 = new File({ owner: 'test', source: 'http://example.com/remote.php/dav/files/test/file.txt', id: 2, mime: 'text/plain' })
		emit('files:node:created', node2)

		// see that there are still no paths and the children are not duplicated
		expect(store.paths).toEqual({})
		expect(root._children).toEqual([node1.source])

	})

	test('Existing folder is created', () => {
		// no defined paths
		expect(store.paths).toEqual({})

		// create the file
		const node1 = new Folder({ owner: 'test', source: 'http://example.com/remote.php/dav/files/test/folder', id: 2 })
		emit('files:node:created', node1)

		// see the path is added
		expect(store.paths).toEqual({ files: { [node1.path]: node1.source } })

		// see that the node is added
		expect(root._children).toEqual([node1.source])

		// create the same named file again
		const node2 = new Folder({ owner: 'test', source: 'http://example.com/remote.php/dav/files/test/folder', id: 2 })
		emit('files:node:created', node2)

		// see that there is still only one paths and the children are not duplicated
		expect(store.paths).toEqual({ files: { [node1.path]: node1.source } })
		expect(root._children).toEqual([node1.source])
	})

	test('Folder is deleted', () => {
		const node = new Folder({ owner: 'test', source: 'http://example.com/remote.php/dav/files/test/folder', id: 2 })
		emit('files:node:created', node)
		// see that the path is added and the children are set-up
		expect(store.paths).toEqual({ files: { [node.path]: node.source } })
		expect(root._children).toEqual([node.source])

		emit('files:node:deleted', node)
		// See the path is removed
		expect(store.paths).toEqual({ files: {} })
		// See the child is removed
		expect(root._children).toEqual([])
	})

	test('File is deleted', () => {
		const node = new File({ owner: 'test', source: 'http://example.com/remote.php/dav/files/test/file.txt', id: 2, mime: 'text/plain' })
		emit('files:node:created', node)
		// see that the children are set-up
		expect(root._children).toEqual([node.source])

		emit('files:node:deleted', node)
		// See the child is removed
		expect(root._children).toEqual([])
	})
})
