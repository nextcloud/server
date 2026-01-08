/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { View } from '@nextcloud/files'

import axios from '@nextcloud/axios'
import * as dialogs from '@nextcloud/dialogs'
import * as eventBus from '@nextcloud/event-bus'
import { DefaultType, File, FileAction, Folder, Permission } from '@nextcloud/files'
import { beforeAll, beforeEach, describe, expect, test, vi } from 'vitest'
import { action } from './downloadAction.ts'

vi.mock('@nextcloud/axios')
vi.mock('@nextcloud/dialogs')
vi.mock('@nextcloud/event-bus')

const view = {
	id: 'files',
	name: 'Files',
} as View

// Mock webroot variable
beforeAll(() => {
	(window as any)._oc_webroot = ''
})

describe('Download action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('download')
		expect(action.displayName({
			nodes: [],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe('Download')
		expect(action.iconSvgInline({
			nodes: [],
			view,
			folder: {} as Folder,
			contents: [],
		})).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBe(DefaultType.DEFAULT)
		expect(action.order).toBe(30)
	})
})

describe('Download action enabled tests', () => {
	test('Enabled with READ permissions', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})

	test('Disabled without READ permissions', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.NONE,
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled if not all nodes have READ permissions', () => {
		const folder1 = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			permissions: Permission.READ,
			root: '/files/admin',
		})
		const folder2 = new Folder({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Bar/',
			owner: 'admin',
			permissions: Permission.NONE,
			root: '/files/admin',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [folder1],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
		expect(action.enabled!({
			nodes: [folder2],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
		expect(action.enabled!({
			nodes: [folder1, folder2],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled without nodes', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})
})

describe('Download action execute tests', () => {
	const link = {
		click: vi.fn(),
	} as unknown as HTMLAnchorElement

	beforeEach(() => {
		vi.resetAllMocks()
		vi.spyOn(document, 'createElement').mockImplementation(() => link)
	})

	test('Download single file', async () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			root: '/files/admin',
		})

		const exec = await action.exec({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})

		// Silent action
		expect(exec).toBe(null)
		expect(link.download).toBe('foobar.txt')
		expect(link.href).toEqual('https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt')
		expect(link.click).toHaveBeenCalledTimes(1)
	})

	test('Download single file with batch', async () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			root: '/files/admin',
		})

		const exec = await action.execBatch!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})

		// Silent action
		expect(exec).toStrictEqual([null])
		expect(link.download).toEqual('foobar.txt')
		expect(link.href).toEqual('https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt')
		expect(link.click).toHaveBeenCalledTimes(1)
	})

	test('Download single file with displayname set', async () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			displayname: 'baz.txt',
			permissions: Permission.READ,
			root: '/files/admin',
		})

		const exec = await action.execBatch!({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})

		// Silent action
		expect(exec).toStrictEqual([null])
		expect(link.download).toEqual('baz.txt')
		expect(link.href).toEqual('https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt')
		expect(link.click).toHaveBeenCalledTimes(1)
	})

	test('Download single folder', async () => {
		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/FooBar/',
			owner: 'admin',
			permissions: Permission.READ,
			root: '/files/admin',
		})

		const exec = await action.exec({
			nodes: [folder],
			view,
			folder: {} as Folder,
			contents: [],
		})

		// Silent action
		expect(exec).toBe(null)
		expect(link.download).toEqual('')
		expect(link.href).toMatch('https://cloud.domain.com/remote.php/dav/files/admin/FooBar/?accept=zip')
		expect(link.click).toHaveBeenCalledTimes(1)
	})

	test('Download multiple nodes', async () => {
		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Dir/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			root: '/files/admin',
		})
		const file2 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Dir/bar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			root: '/files/admin',
		})

		const exec = await action.execBatch!({
			nodes: [file1, file2],
			view,
			folder: {} as Folder,
			contents: [],
		})

		// Silent action
		expect(exec).toStrictEqual([null, null])
		expect(link.download).toEqual('')
		expect(link.href).toMatch('https://cloud.domain.com/remote.php/dav/files/admin/Dir/?accept=zip&files=%5B%22foo.txt%22%2C%22bar.txt%22%5D')
		expect(link.click).toHaveBeenCalledTimes(1)
	})

	test('Download fails with error', async () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			root: '/files/admin',
		})
		vi.spyOn(axios, 'head').mockRejectedValue(new Error('File not found'))

		const errorSpy = vi.spyOn(dialogs, 'showError')
		const exec = await action.exec({
			nodes: [file],
			view,
			folder: {} as Folder,
			contents: [],
		})
		expect(exec).toBe(null)
		expect(errorSpy).toHaveBeenCalledWith('The requested file is not available.')
		expect(link.click).not.toHaveBeenCalled()
	})

	test('Download batch fails with error', async () => {
		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			root: '/files/admin',
		})
		const file2 = new File({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/bar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			root: '/files/admin',
		})
		vi.spyOn(axios, 'head').mockRejectedValue(new Error('File not found'))
		vi.spyOn(eventBus, 'emit').mockImplementation(() => {})

		const errorSpy = vi.spyOn(dialogs, 'showError')
		const exec = await action.execBatch!({
			nodes: [file1, file2],
			view,
			folder: {} as Folder,
			contents: [],
		})
		expect(exec).toStrictEqual([null, null])
		expect(errorSpy).toHaveBeenCalledWith('The requested files are not available.')
		expect(link.click).not.toHaveBeenCalled()
	})
})
