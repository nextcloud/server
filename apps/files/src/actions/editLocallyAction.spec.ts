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
import { action } from './editLocallyAction'
import { expect } from '@jest/globals'
import { File, Permission } from '@nextcloud/files'
import { DefaultType, FileAction } from '../services/FileAction'
import * as ncDialogs from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import type { Navigation } from '../services/Navigation'

const view = {
	id: 'files',
	name: 'Files',
} as Navigation

describe('Edit locally action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('edit-locally')
		expect(action.displayName([], view)).toBe('Edit locally')
		expect(action.iconSvgInline([], view)).toBe('<svg>SvgMock</svg>')
		expect(action.default).toBe(DefaultType.DEFAULT)
		expect(action.order).toBe(25)
	})
})

describe('Edit locally action enabled tests', () => {
	test('Enabled for file with UPDATE permission', () => {
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
			source: 'https://domain.com/data/foobar.txt',
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
			permissions: Permission.ALL,
		})
		const file2 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/bar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file1, file2], view)).toBe(false)
	})

	test('Disabled for files', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			mime: 'text/plain',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Disabled without UPDATE permissions', () => {
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
})

describe('Edit locally action execute tests', () => {
	test('Edit locally opens proper URL', async () => {
		jest.spyOn(axios, 'post').mockImplementation(async () => ({ data: { ocs: { data: { token: 'foobar' } } } }))
		jest.spyOn(ncDialogs, 'showError')

		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.UPDATE,
		})

		const exec = await action.exec(file, view, '/')

		// Silent action
		expect(exec).toBe(null)
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('http://localhost/ocs/v2.php/apps/files/api/v1/openlocaleditor?format=json', { path: '/foobar.txt' })
		expect(ncDialogs.showError).toBeCalledTimes(0)
		expect(window.location.href).toBe('nc://open/test@localhost/foobar.txt?token=foobar')
	})

	test('Edit locally fails and show error', async () => {
		jest.spyOn(axios, 'post').mockImplementation(async () => ({}))
		jest.spyOn(ncDialogs, 'showError')

		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.UPDATE,
		})

		const exec = await action.exec(file, view, '/')

		// Silent action
		expect(exec).toBe(null)
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('http://localhost/ocs/v2.php/apps/files/api/v1/openlocaleditor?format=json', { path: '/foobar.txt' })
		expect(ncDialogs.showError).toBeCalledTimes(1)
		expect(ncDialogs.showError).toBeCalledWith('Failed to redirect to client')
		expect(window.location.href).toBe('http://localhost/')
	})
})
