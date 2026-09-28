/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { File, Permission, View, FileAction } from '@nextcloud/files'
import { ShareType } from '@nextcloud/sharing'
import { beforeAll, beforeEach, describe, expect, test, vi } from 'vitest'

import axios from '@nextcloud/axios'
import * as eventBus from '@nextcloud/event-bus'
import { action } from './restoreShareAction'
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
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
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
		})

		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('restore-share')
		expect(action.displayName([file], deletedShareView)).toBe('Restore share')
		expect(action.iconSvgInline([file], deletedShareView)).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(1)
		expect(action.inline).toBeDefined()
		expect(action.inline!(file, deletedShareView)).toBe(true)
	})

	test('Default values for multiple files', () => {
		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})
		const file2 = new File({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		expect(action.displayName([file1, file2], deletedShareView)).toBe('Restore shares')
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
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], deletedShareView)).toBe(true)
	})

	test('Disabled on wrong view', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([], view)).toBe(false)
	})

	test('Disabled without nodes', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([], deletedShareView)).toBe(false)
	})
})

describe('Restore share action execute tests', () => {
	beforeEach(() => { vi.resetAllMocks() })

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
		})

		const exec = await action.exec(file, deletedShareView, '/')

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
		})

		const exec = await action.execBatch!([file1, file2], deletedShareView, '/')

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
		})

		const exec = await action.exec(file, deletedShareView, '/')

		expect(exec).toBe(false)
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/deletedshares/123')

		expect(eventBus.emit).toBeCalledTimes(0)
	})
})
