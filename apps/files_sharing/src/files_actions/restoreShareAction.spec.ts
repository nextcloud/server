/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Folder, View } from '@nextcloud/files'

import axios from '@nextcloud/axios'
import * as eventBus from '@nextcloud/event-bus'
import { File, FileAction, Permission } from '@nextcloud/files'
import { ShareType } from '@nextcloud/sharing'
import { beforeAll, beforeEach, describe, expect, test, vi } from 'vitest'
import { action } from './restoreShareAction.ts'

import '../main.ts'

vi.mock('@nextcloud/auth')
vi.mock('@nextcloud/axios')

const view = {
	id: 'files',
	name: 'Files',
} as View

const deletedShareView = {
	id: 'deletedshares',
	name: 'Deleted shares',
} as View

// Mock webroot variable
beforeAll(() => {
	(window as any)._oc_webroot = ''
})

describe('Restore share action conditions tests', () => {
	test('Default values', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			root: '/files/admin',
		})

		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('restore-share')
		expect(action.displayName({
			view: deletedShareView,
			nodes: [file],
			folder: {} as Folder,
			contents: [],
		})).toBe('Restore share')
		expect(action.iconSvgInline({
			view: deletedShareView,
			nodes: [file],
			folder: {} as Folder,
			contents: [],
		})).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(1)
		expect(action.inline).toBeDefined()
		expect(action.inline!({
			view: deletedShareView,
			nodes: [file],
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})

	test('Default values for multiple files', () => {
		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			root: '/files/admin',
		})
		const file2 = new File({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			root: '/files/admin',
		})

		expect(action.displayName({
			view: deletedShareView,
			nodes: [file1, file2],
			folder: {} as Folder,
			contents: [],
		})).toBe('Restore shares')
	})
})

describe('Restore share action enabled tests', () => {
	test('Enabled with on pending shares view', () => {
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
			view: deletedShareView,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})

	test('Disabled on wrong view', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [],
			view,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	test('Disabled without nodes', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!({
			nodes: [],
			view: deletedShareView,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})
})

describe('Restore share action execute tests', () => {
	beforeEach(() => {
		vi.resetAllMocks()
	})

	test('Restore share action', async () => {
		vi.spyOn(axios, 'post')
		vi.spyOn(eventBus, 'emit')

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			attributes: {
				id: 123,
				share_type: ShareType.User,
			},
			root: '/files/admin',
		})

		const exec = await action.exec({
			nodes: [file],
			view: deletedShareView,
			folder: {} as Folder,
			contents: [],
		})

		expect(exec).toBe(true)
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/deletedshares/123')

		expect(eventBus.emit).toBeCalledTimes(1)
		expect(eventBus.emit).toBeCalledWith('files:node:deleted', file)
	})

	test('Restore share action batch', async () => {
		vi.spyOn(axios, 'post')
		vi.spyOn(eventBus, 'emit')

		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			attributes: {
				id: 123,
				share_type: ShareType.User,
			},
			root: '/files/admin',
		})

		const file2 = new File({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/bar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			attributes: {
				id: 456,
				share_type: ShareType.User,
			},
			root: '/files/admin',
		})

		const exec = await action.execBatch!({
			nodes: [file1, file2],
			view: deletedShareView,
			folder: {} as Folder,
			contents: [],
		})

		expect(exec).toStrictEqual([true, true])
		expect(axios.post).toBeCalledTimes(2)
		expect(axios.post).toHaveBeenNthCalledWith(1, 'http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/deletedshares/123')
		expect(axios.post).toHaveBeenNthCalledWith(2, 'http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/deletedshares/456')

		expect(eventBus.emit).toBeCalledTimes(2)
		expect(eventBus.emit).toHaveBeenNthCalledWith(1, 'files:node:deleted', file1)
		expect(eventBus.emit).toHaveBeenNthCalledWith(2, 'files:node:deleted', file2)
	})

	test('Restore fails', async () => {
		vi.spyOn(axios, 'post')
			.mockImplementation(() => { throw new Error('Mock error') })

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			attributes: {
				id: 123,
				share_type: ShareType.User,
			},
			root: '/files/admin',
		})

		const exec = await action.exec({
			nodes: [file],
			view: deletedShareView,
			folder: {} as Folder,
			contents: [],
		})

		expect(exec).toBe(false)
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/deletedshares/123')

		expect(eventBus.emit).toBeCalledTimes(0)
	})
})
