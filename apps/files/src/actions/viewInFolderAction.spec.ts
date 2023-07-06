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
import { action } from './viewInFolderAction'
import { expect } from '@jest/globals'
import { File, Folder, Node, Permission } from '@nextcloud/files'
import { FileAction } from '../services/FileAction'
import type { Navigation } from '../services/Navigation'

const view = {
	id: 'files',
	name: 'Files',
} as Navigation

describe('View in folder action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('view-in-folder')
		expect(action.displayName([], view)).toBe('View in folder')
		expect(action.iconSvgInline([], view)).toBe('<svg>SvgMock</svg>')
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(80)
	})
})

describe('View in folder action enabled tests', () => {
	test('Enabled for files', () => {
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

	test('Disabled for non-dav ressources', () => {
		const file = new File({
			id: 1,
			source: 'https://domain.com/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Disabled if more than one node', () => {
		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
		})
		const file2 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/bar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file1, file2], view)).toBe(false)
	})

	test('Disabled for folders', () => {
		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/FooBar/',
			owner: 'admin',
			permissions: Permission.READ,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([folder], view)).toBe(false)
	})
})

describe('View in folder action execute tests', () => {
	test('View in folder', async () => {
		const goToRouteMock = jest.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

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
		expect(goToRouteMock).toBeCalledTimes(1)
		expect(goToRouteMock).toBeCalledWith(null, { fileid: 1, view: 'files' }, { fileid: 1, dir: '/' })
	})

	test('View in (sub) folder', async () => {
		const goToRouteMock = jest.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/Bar/foobar.txt',
			root: '/files/admin',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
		})

		const exec = await action.exec(file, view, '/')
		// Silent action
		expect(exec).toBe(null)
		expect(goToRouteMock).toBeCalledTimes(1)
		expect(goToRouteMock).toBeCalledWith(null, { fileid: 1, view: 'files' }, { fileid: 1, dir: '/Foo/Bar' })
	})

	test('View in folder fails without node', async () => {
		const goToRouteMock = jest.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const exec = await action.exec(null as unknown as Node, view, '/')
		expect(exec).toBe(false)
		expect(goToRouteMock).toBeCalledTimes(0)
	})

	test('View in folder fails without File', async () => {
		const goToRouteMock = jest.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
		})

		const exec = await action.exec(folder, view, '/')
		expect(exec).toBe(false)
		expect(goToRouteMock).toBeCalledTimes(0)
	})
})
