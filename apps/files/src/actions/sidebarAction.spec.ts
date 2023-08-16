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
import type { Navigation } from '../services/Navigation'

import { expect } from '@jest/globals'
import { File, Permission } from '@nextcloud/files'

import { action } from './sidebarAction'
import { FileAction } from '../services/FileAction'
import logger from '../logger'

const view = {
	id: 'files',
	name: 'Files',
} as Navigation

describe('Open sidebar action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('details')
		expect(action.displayName([], view)).toBe('Open details')
		expect(action.iconSvgInline([], view)).toBe('<svg>SvgMock</svg>')
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(-50)
	})
})

describe('Open sidebar action enabled tests', () => {
	test('Enabled for ressources within user root folder', () => {
		window.OCA = { Files: { Sidebar: {} } }

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

	test('Disabled without permissions', () => {
		window.OCA = { Files: { Sidebar: {} } }

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

	test('Disabled if more than one node', () => {
		window.OCA = { Files: { Sidebar: {} } }

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

	test('Disabled if no Sidebar', () => {
		window.OCA = {}

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Disabled for non-dav ressources', () => {
		window.OCA = { Files: { Sidebar: {} } }

		const file = new File({
			id: 1,
			source: 'https://domain.com/documents/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})
})

describe('Open sidebar action exec tests', () => {
	test('Open sidebar', async () => {
		const openMock = jest.fn()
		window.OCA = { Files: { Sidebar: { open: openMock } } }
		const goToRouteMock = jest.fn()
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		const exec = await action.exec(file, view, '/')
		// Silent action
		expect(exec).toBe(null)
		expect(openMock).toBeCalledWith('/foobar.txt')
		expect(goToRouteMock).toBeCalledWith(
			null,
			{ view: view.id, fileid: 1 },
			{ dir: '/' },
			true,
		)
	})

	test('Open sidebar fails', async () => {
		const openMock = jest.fn(() => { throw new Error('Mock error') })
		window.OCA = { Files: { Sidebar: { open: openMock } } }
		jest.spyOn(logger, 'error').mockImplementation(() => jest.fn())

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		const exec = await action.exec(file, view, '/')
		expect(exec).toBe(false)
		expect(openMock).toBeCalledTimes(1)
		expect(logger.error).toBeCalledTimes(1)
	})
})
