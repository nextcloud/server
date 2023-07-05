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
import { action } from './renameAction'
import { expect } from '@jest/globals'
import { File, Permission } from '@nextcloud/files'
import { FileAction } from '../services/FileAction'
import * as eventBus from '@nextcloud/event-bus'
import type { Navigation } from '../services/Navigation'

const view = {
	id: 'files',
	name: 'Files',
} as Navigation

describe('Rename action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('rename')
		expect(action.displayName([], view)).toBe('Rename')
		expect(action.iconSvgInline([], view)).toBe('<svg>SvgMock</svg>')
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(10)
	})
})

describe('Rename action enabled tests', () => {
	test('Enabled for node with UPDATE permission', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.UPDATE,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(true)
	})

	test('Disabled for node without UPDATE permission', () => {
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
})

describe('Rename action exec tests', () => {
	test('Rename', async () => {
		jest.spyOn(eventBus, 'emit')

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		const exec = await action.exec(file, view, '/')

		// Silent action
		expect(exec).toBe(null)
		expect(eventBus.emit).toBeCalledTimes(1)
		expect(eventBus.emit).toHaveBeenCalledWith('files:node:rename', file)
	})
})
