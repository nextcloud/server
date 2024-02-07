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
import { action } from './deleteAction'
import { expect } from '@jest/globals'
import { File, Folder, Permission, View, FileAction } from '@nextcloud/files'
import * as eventBus from '@nextcloud/event-bus'
import axios from '@nextcloud/axios'

import logger from '../logger'

const view = {
	id: 'files',
	name: 'Files',
} as View

const trashbinView = {
	id: 'trashbin',
	name: 'Trashbin',
} as View

describe('Delete action conditions tests', () => {
	afterEach(() => {
		jest.restoreAllMocks()
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
		expect(action.iconSvgInline([], view)).toBe('<svg>SvgMock</svg>')
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(100)
	})

	test('Default folder displayName', () => {
		expect(action.displayName([folder], view)).toBe('Delete folder')
	})

	test('Default trashbin view displayName', () => {
		expect(action.displayName([file], trashbinView)).toBe('Delete permanently')
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
})

describe('Delete action execute tests', () => {
	test('Delete action', async () => {
		jest.spyOn(axios, 'delete')
		jest.spyOn(eventBus, 'emit')

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
		jest.spyOn(axios, 'delete')
		jest.spyOn(eventBus, 'emit')

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

		expect(exec).toStrictEqual([true, true])
		expect(axios.delete).toBeCalledTimes(2)
		expect(axios.delete).toHaveBeenNthCalledWith(1, 'https://cloud.domain.com/remote.php/dav/files/test/foo.txt')
		expect(axios.delete).toHaveBeenNthCalledWith(2, 'https://cloud.domain.com/remote.php/dav/files/test/bar.txt')

		expect(eventBus.emit).toBeCalledTimes(2)
		expect(eventBus.emit).toHaveBeenNthCalledWith(1, 'files:node:deleted', file1)
		expect(eventBus.emit).toHaveBeenNthCalledWith(2, 'files:node:deleted', file2)
	})

	test('Delete fails', async () => {
		jest.spyOn(axios, 'delete').mockImplementation(() => { throw new Error('Mock error') })
		jest.spyOn(logger, 'error').mockImplementation(() => jest.fn())

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
})
