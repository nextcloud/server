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
import { action } from './downloadAction'
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

describe('Download action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('download')
		expect(action.displayName([], view)).toBe('Download')
		expect(action.iconSvgInline([], view)).toBe('SvgMock')
		expect(action.default).toBeUndefined()
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
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(true)
	})

	test('Disabled without READ permissions', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.NONE,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Disabled if not all nodes have READ permissions', () => {
		const folder1 = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			permissions: Permission.READ,
		})
		const folder2 = new Folder({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Bar/',
			owner: 'admin',
			permissions: Permission.NONE,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([folder1], view)).toBe(true)
		expect(action.enabled!([folder2], view)).toBe(false)
		expect(action.enabled!([folder1, folder2], view)).toBe(false)
	})

	test('Disabled without nodes', () => {
		expect(action.enabled).toBeDefined()
		expect(action.enabled!([], view)).toBe(false)
	})
})

describe('Download action execute tests', () => {
	const link = {
		click: jest.fn(),
	} as unknown as HTMLAnchorElement

	beforeEach(() => {
		jest.spyOn(document, 'createElement').mockImplementation(() => link)
	})

	test('Download single file', async () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
		})

		const exec = await action.exec(file, view, '/')

		// Silent action
		expect(exec).toBe(null)
		expect(link.download).toEqual('')
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
		})

		const exec = await action.execBatch!([file], view, '/')

		// Silent action
		expect(exec).toStrictEqual([null])
		expect(link.download).toEqual('')
		expect(link.href).toEqual('https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt')
		expect(link.click).toHaveBeenCalledTimes(1)
	})

	test('Download single folder', async () => {
		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/FooBar/',
			owner: 'admin',
			permissions: Permission.READ,
		})

		const exec = await action.exec(folder, view, '/')

		// Silent action
		expect(exec).toBe(null)
		expect(link.download).toEqual('')
		expect(link.href.startsWith('/index.php/apps/files/ajax/download.php?dir=%2F&files=%5B%22FooBar%22%5D&downloadStartSecret=')).toBe(true)
		expect(link.click).toHaveBeenCalledTimes(1)
	})

	test('Download multiple nodes', async () => {
		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Dir/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
		})
		const file2 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Dir/bar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
		})

		const exec = await action.execBatch!([file1, file2], view, '/Dir')

		// Silent action
		expect(exec).toStrictEqual([null, null])
		expect(link.download).toEqual('')
		expect(link.href.startsWith('/index.php/apps/files/ajax/download.php?dir=%2FDir&files=%5B%22foo.txt%22%2C%22bar.txt%22%5D&downloadStartSecret=')).toBe(true)
		expect(link.click).toHaveBeenCalledTimes(1)
	})
})
