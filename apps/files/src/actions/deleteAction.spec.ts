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
import { File, Folder, Permission } from '@nextcloud/files'
import { FileAction } from '../services/FileAction'
import * as eventBus from '@nextcloud/event-bus'
import axios from '@nextcloud/axios'
import type { Navigation } from '../services/Navigation'
import logger from '../logger'

const view = {
	id: 'files',
	name: 'Files',
} as Navigation

const trashbinView = {
	id: 'trashbin',
	name: 'Trashbin',
} as Navigation

describe('Delete action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('delete')
		expect(action.displayName([], view)).toBe('Delete')
		expect(action.iconSvgInline([], view)).toBe('SvgMock')
		expect(action.default).toBe(false)
		expect(action.order).toBe(100)
	})

	test('Default trashbin view values', () => {
		expect(action.displayName([], trashbinView)).toBe('Delete permanently')
	})
})

describe('Delete action enabled tests', () => {
	test('Enabled with DELETE permissions', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(true)
	})

	test('Disabled without DELETE permissions', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
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
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			permissions: Permission.DELETE,
		})
		const folder2 = new Folder({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Bar/',
			owner: 'admin',
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
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const exec = await action.exec(file, view, '/')

		expect(exec).toBe(true)
		expect(axios.delete).toBeCalledTimes(1)
		expect(axios.delete).toBeCalledWith('https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt')

		expect(eventBus.emit).toBeCalledTimes(1)
		expect(eventBus.emit).toBeCalledWith('files:node:deleted', file)
	})

	test('Delete action batch', async () => {
		jest.spyOn(axios, 'delete')
		jest.spyOn(eventBus, 'emit')

		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const file2 = new File({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/bar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const exec = await action.execBatch!([file1, file2], view, '/')

		expect(exec).toStrictEqual([true, true])
		expect(axios.delete).toBeCalledTimes(2)
		expect(axios.delete).toHaveBeenNthCalledWith(1, 'https://cloud.domain.com/remote.php/dav/files/admin/foo.txt')
		expect(axios.delete).toHaveBeenNthCalledWith(2, 'https://cloud.domain.com/remote.php/dav/files/admin/bar.txt')

		expect(eventBus.emit).toBeCalledTimes(2)
		expect(eventBus.emit).toHaveBeenNthCalledWith(1, 'files:node:deleted', file1)
		expect(eventBus.emit).toHaveBeenNthCalledWith(2, 'files:node:deleted', file2)
	})

	test('Delete fails', async () => {
		jest.spyOn(axios, 'delete').mockImplementation(() => { throw new Error('Mock error') })
		jest.spyOn(logger, 'error').mockImplementation(() => jest.fn())

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})

		const exec = await action.exec(file, view, '/')

		expect(exec).toBe(false)
		expect(axios.delete).toBeCalledTimes(1)
		expect(axios.delete).toBeCalledWith('https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt')

		expect(eventBus.emit).toBeCalledTimes(0)
		expect(logger.error).toBeCalledTimes(1)
	})
})
