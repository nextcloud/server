/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { File, Folder, Permission, View, FileAction } from '@nextcloud/files'
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'

import axios from '@nextcloud/axios'
import * as capabilities from '@nextcloud/capabilities'
import * as eventBus from '@nextcloud/event-bus'

import { action } from './deleteAction'
import logger from '../logger'

vi.mock('@nextcloud/auth')
vi.mock('@nextcloud/axios')
vi.mock('@nextcloud/capabilities')

const view = {
	id: 'files',
	name: 'Files',
} as View

const trashbinView = {
	id: 'trashbin',
	name: 'Trashbin',
} as View

describe('Delete action conditions tests', () => {
	beforeEach(() => {
		vi.restoreAllMocks()
	})

	const file = new File({
		id: 1,
		source: 'https://cloud.domain.com/remote.php/dav/files/test/foobar.txt',
		owner: 'test',
		mime: 'text/plain',
		permissions: Permission.ALL,
	})

	const file2 = new File({
		id: 1,
		source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
		owner: 'admin',
		mime: 'text/plain',
		permissions: Permission.ALL,
		attributes: {
			'is-mount-root': true,
			'mount-type': 'shared',
		},
	})

	const folder = new Folder({
		id: 1,
		source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo',
		owner: 'admin',
		mime: 'text/plain',
		permissions: Permission.ALL,
	})

	const folder2 = new Folder({
		id: 1,
		source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo',
		owner: 'admin',
		mime: 'text/plain',
		permissions: Permission.ALL,
		attributes: {
			'is-mount-root': true,
			'mount-type': 'shared',
		},
	})

	const folder3 = new Folder({
		id: 1,
		source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo',
		owner: 'admin',
		mime: 'text/plain',
		permissions: Permission.ALL,
		attributes: {
			'is-mount-root': true,
			'mount-type': 'external',
		},
	})

	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('delete')
		expect(action.displayName([file], view)).toBe('Delete file')
		expect(action.iconSvgInline([], view)).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(100)
	})

	test('Default folder displayName', () => {
		expect(action.displayName([folder], view)).toBe('Delete folder')
	})

	test('Default trashbin view displayName', () => {
		expect(action.displayName([file], trashbinView)).toBe('Delete permanently')
	})

	test('Trashbin disabled displayName', () => {
		vi.spyOn(capabilities, 'getCapabilities').mockImplementation(() => {
			return {
				files: {},
			}
		})
		expect(action.displayName([file], view)).toBe('Delete permanently')
		expect(capabilities.getCapabilities).toBeCalledTimes(1)
	})

	test('Shared root node displayName', () => {
		expect(action.displayName([file2], view)).toBe('Leave this share')
		expect(action.displayName([folder2], view)).toBe('Leave this share')
		expect(action.displayName([file2, folder2], view)).toBe('Leave these shares')
	})

	test('External storage root node displayName', () => {
		expect(action.displayName([folder3], view)).toBe('Disconnect storage')
		expect(action.displayName([folder3, folder3], view)).toBe('Disconnect storages')
	})

	test('Shared and owned nodes displayName', () => {
		expect(action.displayName([file, file2], view)).toBe('Delete and unshare')
	})
})

describe('Delete action enabled tests', () => {
	let initialState: HTMLInputElement

	afterEach(() => {
		document.body.removeChild(initialState)
	})

	beforeEach(() => {
		initialState = document.createElement('input')
		initialState.setAttribute('type', 'hidden')
		initialState.setAttribute('id', 'initial-state-files_trashbin-config')
		initialState.setAttribute('value', btoa(JSON.stringify({
			allow_delete: true,
		})))
		document.body.appendChild(initialState)
	})

	test('Enabled with DELETE permissions', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/foobar.txt',
			owner: 'test',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(true)
	})

	test('Disabled without DELETE permissions', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/foobar.txt',
			owner: 'test',
			mime: 'text/plain',
			permissions: Permission.READ,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Disabled without nodes', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([], view)).toBe(false)
	})

	test('Disabled if not all nodes can be deleted', () => {
		const folder1 = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/Foo/',
			owner: 'test',
			permissions: Permission.DELETE,
		})
		const folder2 = new Folder({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/Bar/',
			owner: 'test',
			permissions: Permission.READ,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([folder1], view)).toBe(true)
		expect(action.enabled!([folder2], view)).toBe(false)
		expect(action.enabled!([folder1, folder2], view)).toBe(false)
	})

	test('Disabled if not allowed', () => {
		initialState.setAttribute('value', btoa(JSON.stringify({
			allow_delete: false,
		})))

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([], view)).toBe(false)
	})
})

describe('Delete action execute tests', () => {
	afterEach(() => {
		vi.restoreAllMocks()
	})
	test('Delete action', async () => {
		vi.spyOn(axios, 'delete')
		vi.spyOn(eventBus, 'emit')

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/foobar.txt',
			owner: 'test',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const exec = await action.exec(file, view, '/')

		expect(exec).toBe(true)
		expect(axios.delete).toBeCalledTimes(1)
		expect(axios.delete).toBeCalledWith('https://cloud.domain.com/remote.php/dav/files/test/foobar.txt')

		expect(eventBus.emit).toBeCalledTimes(1)
		expect(eventBus.emit).toBeCalledWith('files:node:deleted', file)
	})

	test('Delete action batch', async () => {
		vi.spyOn(axios, 'delete')
		vi.spyOn(eventBus, 'emit')

		const confirmMock = vi.fn()
		// @ts-expect-error We only mock what needed
		window.OC = { dialogs: { confirmDestructive: confirmMock } }

		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/foo.txt',
			owner: 'test',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const file2 = new File({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/bar.txt',
			owner: 'test',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const exec = await action.execBatch!([file1, file2], view, '/')

		// Not enough nodes to trigger a confirmation dialog
		expect(confirmMock).toBeCalledTimes(0)

		expect(exec).toStrictEqual([true, true])
		expect(axios.delete).toBeCalledTimes(2)
		expect(axios.delete).toHaveBeenNthCalledWith(1, 'https://cloud.domain.com/remote.php/dav/files/test/foo.txt')
		expect(axios.delete).toHaveBeenNthCalledWith(2, 'https://cloud.domain.com/remote.php/dav/files/test/bar.txt')

		expect(eventBus.emit).toBeCalledTimes(2)
		expect(eventBus.emit).toHaveBeenNthCalledWith(1, 'files:node:deleted', file1)
		expect(eventBus.emit).toHaveBeenNthCalledWith(2, 'files:node:deleted', file2)
	})

	test('Delete action batch large set', async () => {
		vi.spyOn(axios, 'delete')
		vi.spyOn(eventBus, 'emit')

		// Emulate the confirmation dialog to always confirm
		const confirmMock = vi.fn().mockImplementation((a, b, c, resolve) => resolve(true))
		// @ts-expect-error We only mock what needed
		window.OC = { dialogs: { confirmDestructive: confirmMock } }

		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/foo.txt',
			owner: 'test',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const file2 = new File({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/bar.txt',
			owner: 'test',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const file3 = new File({
			id: 3,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/baz.txt',
			owner: 'test',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const file4 = new File({
			id: 4,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/qux.txt',
			owner: 'test',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const file5 = new File({
			id: 5,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/quux.txt',
			owner: 'test',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const exec = await action.execBatch!([file1, file2, file3, file4, file5], view, '/')

		// Enough nodes to trigger a confirmation dialog
		expect(confirmMock).toBeCalledTimes(1)

		expect(exec).toStrictEqual([true, true, true, true, true])
		expect(axios.delete).toBeCalledTimes(5)
		expect(axios.delete).toHaveBeenNthCalledWith(1, 'https://cloud.domain.com/remote.php/dav/files/test/foo.txt')
		expect(axios.delete).toHaveBeenNthCalledWith(2, 'https://cloud.domain.com/remote.php/dav/files/test/bar.txt')
		expect(axios.delete).toHaveBeenNthCalledWith(3, 'https://cloud.domain.com/remote.php/dav/files/test/baz.txt')
		expect(axios.delete).toHaveBeenNthCalledWith(4, 'https://cloud.domain.com/remote.php/dav/files/test/qux.txt')
		expect(axios.delete).toHaveBeenNthCalledWith(5, 'https://cloud.domain.com/remote.php/dav/files/test/quux.txt')

		expect(eventBus.emit).toBeCalledTimes(5)
		expect(eventBus.emit).toHaveBeenNthCalledWith(1, 'files:node:deleted', file1)
		expect(eventBus.emit).toHaveBeenNthCalledWith(2, 'files:node:deleted', file2)
		expect(eventBus.emit).toHaveBeenNthCalledWith(3, 'files:node:deleted', file3)
		expect(eventBus.emit).toHaveBeenNthCalledWith(4, 'files:node:deleted', file4)
		expect(eventBus.emit).toHaveBeenNthCalledWith(5, 'files:node:deleted', file5)
	})

	test('Delete action batch trashbin disabled', async () => {
		vi.spyOn(axios, 'delete')
		vi.spyOn(eventBus, 'emit')
		vi.spyOn(capabilities, 'getCapabilities').mockImplementation(() => {
			return {
				files: {},
			}
		})

		// Emulate the confirmation dialog to always confirm
		const confirmMock = vi.fn().mockImplementation((a, b, c, resolve) => resolve(true))
		// @ts-expect-error We only mock what needed
		window.OC = { dialogs: { confirmDestructive: confirmMock } }

		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/foo.txt',
			owner: 'test',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const file2 = new File({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/bar.txt',
			owner: 'test',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const exec = await action.execBatch!([file1, file2], view, '/')

		// Will trigger a confirmation dialog because trashbin app is disabled
		expect(confirmMock).toBeCalledTimes(1)

		expect(exec).toStrictEqual([true, true])
		expect(axios.delete).toBeCalledTimes(2)
		expect(axios.delete).toHaveBeenNthCalledWith(1, 'https://cloud.domain.com/remote.php/dav/files/test/foo.txt')
		expect(axios.delete).toHaveBeenNthCalledWith(2, 'https://cloud.domain.com/remote.php/dav/files/test/bar.txt')

		expect(eventBus.emit).toBeCalledTimes(2)
		expect(eventBus.emit).toHaveBeenNthCalledWith(1, 'files:node:deleted', file1)
		expect(eventBus.emit).toHaveBeenNthCalledWith(2, 'files:node:deleted', file2)
	})

	test('Delete fails', async () => {
		vi.spyOn(axios, 'delete').mockImplementation(() => { throw new Error('Mock error') })
		vi.spyOn(logger, 'error').mockImplementation(() => vi.fn())
		vi.spyOn(eventBus, 'emit')

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/foobar.txt',
			owner: 'test',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const exec = await action.exec(file, view, '/')

		expect(exec).toBe(false)
		expect(axios.delete).toBeCalledTimes(1)
		expect(axios.delete).toBeCalledWith('https://cloud.domain.com/remote.php/dav/files/test/foobar.txt')

		expect(eventBus.emit).toBeCalledTimes(0)
		expect(logger.error).toBeCalledTimes(1)
	})

	test('Delete is cancelled', async () => {
		vi.spyOn(axios, 'delete')
		vi.spyOn(eventBus, 'emit')
		vi.spyOn(capabilities, 'getCapabilities').mockImplementation(() => {
			return {
				files: {},
			}
		})

		// Emulate the confirmation dialog to always confirm
		const confirmMock = vi.fn().mockImplementation((a, b, c, resolve) => resolve(false))
		// @ts-expect-error We only mock what needed
		window.OC = { dialogs: { confirmDestructive: confirmMock } }

		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/test/foo.txt',
			owner: 'test',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const exec = await action.execBatch!([file1], view, '/')

		expect(confirmMock).toBeCalledTimes(1)

		expect(exec).toStrictEqual([null])
		expect(axios.delete).toBeCalledTimes(0)

		expect(eventBus.emit).toBeCalledTimes(0)
	})
})
