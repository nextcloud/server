/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import { action } from './rejectShareAction'
import { expect } from '@jest/globals'
import { File, Folder, Permission, View } from '@nextcloud/files'
import { FileAction } from '../../../files/src/services/FileAction'
import * as eventBus from '@nextcloud/event-bus'
import axios from '@nextcloud/axios'
import '../main'

const view = {
	id: 'files',
	name: 'Files',
} as View

const pendingShareView = {
	id: 'pendingshares',
	name: 'Pending shares',
} as View

describe('Reject share action conditions tests', () => {
	test('Default values', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('reject-share')
		expect(action.displayName([file], pendingShareView)).toBe('Reject share')
		expect(action.iconSvgInline([file], pendingShareView)).toBe('<svg>SvgMock</svg>')
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(2)
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

		expect(action.displayName([file1, file2], pendingShareView)).toBe('Reject shares')
	})
})

describe('Reject share action enabled tests', () => {
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

	test('Disabled if some nodes are remote group shares', () => {
		const folder1 = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			permissions: Permission.READ,
			attributes: {
				share_type: window.OC.Share.SHARE_TYPE_USER,
			},
		})
		const folder2 = new Folder({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Bar/',
			owner: 'admin',
			permissions: Permission.READ,
			attributes: {
				remote_id: 1,
				share_type: window.OC.Share.SHARE_TYPE_REMOTE_GROUP,
			},
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([folder1], pendingShareView)).toBe(true)
		expect(action.enabled!([folder2], pendingShareView)).toBe(false)
		expect(action.enabled!([folder1, folder2], pendingShareView)).toBe(false)
	})
})

describe('Reject share action execute tests', () => {
	test('Reject share action', async () => {
		jest.spyOn(axios, 'delete')
		jest.spyOn(eventBus, 'emit')

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			attributes: {
				id: 123,
				share_type: window.OC.Share.SHARE_TYPE_USER,
			},
		})

		const exec = await action.exec(file, pendingShareView, '/')

		expect(exec).toBe(true)
		expect(axios.delete).toBeCalledTimes(1)
		expect(axios.delete).toBeCalledWith('http://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares/123')

		expect(eventBus.emit).toBeCalledTimes(1)
		expect(eventBus.emit).toBeCalledWith('files:node:deleted', file)
	})

	test('Reject remote share action', async () => {
		jest.spyOn(axios, 'delete')
		jest.spyOn(eventBus, 'emit')

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			attributes: {
				id: 123,
				remote: 3,
				share_type: window.OC.Share.SHARE_TYPE_USER,
			},
		})

		const exec = await action.exec(file, pendingShareView, '/')

		expect(exec).toBe(true)
		expect(axios.delete).toBeCalledTimes(1)
		expect(axios.delete).toBeCalledWith('http://localhost/ocs/v2.php/apps/files_sharing/api/v1/remote_shares/123')

		expect(eventBus.emit).toBeCalledTimes(1)
		expect(eventBus.emit).toBeCalledWith('files:node:deleted', file)
	})

	test('Reject share action batch', async () => {
		jest.spyOn(axios, 'delete')
		jest.spyOn(eventBus, 'emit')

		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			attributes: {
				id: 123,
				share_type: window.OC.Share.SHARE_TYPE_USER,
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
				share_type: window.OC.Share.SHARE_TYPE_USER,
			},
		})

		const exec = await action.execBatch!([file1, file2], pendingShareView, '/')

		expect(exec).toStrictEqual([true, true])
		expect(axios.delete).toBeCalledTimes(2)
		expect(axios.delete).toHaveBeenNthCalledWith(1, 'http://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares/123')
		expect(axios.delete).toHaveBeenNthCalledWith(2, 'http://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares/456')

		expect(eventBus.emit).toBeCalledTimes(2)
		expect(eventBus.emit).toHaveBeenNthCalledWith(1, 'files:node:deleted', file1)
		expect(eventBus.emit).toHaveBeenNthCalledWith(2, 'files:node:deleted', file2)
	})

	test('Reject fails', async () => {
		jest.spyOn(axios, 'delete').mockImplementation(() => { throw new Error('Mock error') })

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
			attributes: {
				id: 123,
				share_type: window.OC.Share.SHARE_TYPE_USER,
			},
		})

		const exec = await action.exec(file, pendingShareView, '/')

		expect(exec).toBe(false)
		expect(axios.delete).toBeCalledTimes(1)
		expect(axios.delete).toBeCalledWith('http://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares/123')

		expect(eventBus.emit).toBeCalledTimes(0)
	})
})
