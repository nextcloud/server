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
import { action } from './restoreShareAction'
import { expect } from '@jest/globals'
import { File, Folder, Permission } from '@nextcloud/files'
import { FileAction } from '../../../files/src/services/FileAction'
import * as eventBus from '@nextcloud/event-bus'
import axios from '@nextcloud/axios'
import type { Navigation } from '../../../files/src/services/Navigation'
import '../main'

const view = {
	id: 'files',
	name: 'Files',
} as Navigation

const deletedShareView = {
	id: 'deletedshares',
	name: 'Deleted shares',
} as Navigation

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
		expect(action.iconSvgInline([file], deletedShareView)).toBe('<svg>SvgMock</svg>')
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
	test('Restore share action', async () => {
		jest.spyOn(axios, 'post')
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

		const exec = await action.exec(file, deletedShareView, '/')

		expect(exec).toBe(true)
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('http://localhost/ocs/v2.php/apps/files_sharing/api/v1/deletedshares/123')

		expect(eventBus.emit).toBeCalledTimes(1)
		expect(eventBus.emit).toBeCalledWith('files:node:deleted', file)
	})

	test('Restore share action batch', async () => {
		jest.spyOn(axios, 'post')
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

		const exec = await action.execBatch!([file1, file2], deletedShareView, '/')

		expect(exec).toStrictEqual([true, true])
		expect(axios.post).toBeCalledTimes(2)
		expect(axios.post).toHaveBeenNthCalledWith(1, 'http://localhost/ocs/v2.php/apps/files_sharing/api/v1/deletedshares/123')
		expect(axios.post).toHaveBeenNthCalledWith(2, 'http://localhost/ocs/v2.php/apps/files_sharing/api/v1/deletedshares/456')

		expect(eventBus.emit).toBeCalledTimes(2)
		expect(eventBus.emit).toHaveBeenNthCalledWith(1, 'files:node:deleted', file1)
		expect(eventBus.emit).toHaveBeenNthCalledWith(2, 'files:node:deleted', file2)
	})

	test('Restore fails', async () => {
		jest.spyOn(axios, 'post').mockImplementation(() => { throw new Error('Mock error') })

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

		const exec = await action.exec(file, deletedShareView, '/')

		expect(exec).toBe(false)
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('http://localhost/ocs/v2.php/apps/files_sharing/api/v1/deletedshares/123')

		expect(eventBus.emit).toBeCalledTimes(0)
	})
})
