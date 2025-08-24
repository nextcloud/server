/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { beforeAll, beforeEach, describe, expect, test, vi } from 'vitest'

import { action } from './acceptShareAction'
import { File, Permission, View, FileAction } from '@nextcloud/files'
import { ShareType } from '@nextcloud/sharing'
import * as eventBus from '@nextcloud/event-bus'
import axios from '@nextcloud/axios'

import '../main.ts'

vi.mock('@nextcloud/axios')

const view = {
	id: 'files',
	name: 'Files',
} as View

const pendingShareView = {
	id: 'pendingshares',
	name: 'Pending shares',
} as View

// Mock webroot variable
beforeAll(() => {
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	(window as any)._oc_webroot = ''
})

describe('Accept share action conditions tests', () => {
	test('Default values', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('accept-share')
		expect(action.displayName([file], pendingShareView)).toBe('Accept share')
		expect(action.iconSvgInline([file], pendingShareView)).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(1)
		expect(action.inline).toBeDefined()
		expect(action.inline!(file, pendingShareView)).toBe(true)
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

		expect(action.displayName([file1, file2], pendingShareView)).toBe('Accept shares')
	})
})

describe('Accept share action enabled tests', () => {
	test('Enabled with on pending shares view', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], pendingShareView)).toBe(true)
	})

	test('Disabled on wrong view', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([], view)).toBe(false)
	})

	test('Disabled without nodes', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([], pendingShareView)).toBe(false)
	})
})

describe('Accept share action execute tests', () => {
	beforeEach(() => { vi.resetAllMocks() })

	test('Accept share action', async () => {
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

		const exec = await action.exec(file, pendingShareView, '/')

		expect(exec).toBe(true)
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/shares/pending/123')

		expect(eventBus.emit).toBeCalledTimes(1)
		expect(eventBus.emit).toBeCalledWith('files:node:deleted', file)
	})

	test('Accept remote share action', async () => {
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
				remote: 3,
				share_type: ShareType.User,
			},
		})

		const exec = await action.exec(file, pendingShareView, '/')

		expect(exec).toBe(true)
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/remote_shares/pending/123')

		expect(eventBus.emit).toBeCalledTimes(1)
		expect(eventBus.emit).toBeCalledWith('files:node:deleted', file)
	})

	test('Accept share action batch', async () => {
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

		const exec = await action.execBatch!([file1, file2], pendingShareView, '/')

		expect(exec).toStrictEqual([true, true])
		expect(axios.post).toBeCalledTimes(2)
		expect(axios.post).toHaveBeenNthCalledWith(1, 'http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/shares/pending/123')
		expect(axios.post).toHaveBeenNthCalledWith(2, 'http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/shares/pending/456')

		expect(eventBus.emit).toBeCalledTimes(2)
		expect(eventBus.emit).toHaveBeenNthCalledWith(1, 'files:node:deleted', file1)
		expect(eventBus.emit).toHaveBeenNthCalledWith(2, 'files:node:deleted', file2)
	})

	test('Accept fails', async () => {
		vi.spyOn(axios, 'post').mockImplementation(() => { throw new Error('Mock error') })

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

		const exec = await action.exec(file, pendingShareView, '/')

		expect(exec).toBe(false)
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/shares/pending/123')

		expect(eventBus.emit).toBeCalledTimes(0)
	})
})
