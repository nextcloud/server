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
import { expect } from '@jest/globals'
import { File, Folder, Permission } from '@nextcloud/files'
import { isNodeExternalStorage } from './externalStorageUtils'

describe('Is node an external storage', () => {
	test('A Folder with a backend and a valid scope is an external storage', () => {
		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			permissions: Permission.ALL,
			attributes: {
				scope: 'personal',
				backend: 'SFTP',
			},
		})
		expect(isNodeExternalStorage(folder)).toBe(true)
	})

	test('a File is not a valid storage', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})
		expect(isNodeExternalStorage(file)).toBe(false)
	})

	test('A Folder without a backend is not a storage', () => {
		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			permissions: Permission.ALL,
			attributes: {
				scope: 'personal',
			},
		})
		expect(isNodeExternalStorage(folder)).toBe(false)
	})

	test('A Folder without a scope is not a storage', () => {
		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			permissions: Permission.ALL,
			attributes: {
				backend: 'SFTP',
			},
		})
		expect(isNodeExternalStorage(folder)).toBe(false)
	})

	test('A Folder with an invalid scope is not a storage', () => {
		const folder = new Folder({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			permissions: Permission.ALL,
			attributes: {
				scope: 'null',
				backend: 'SFTP',
			},
		})
		expect(isNodeExternalStorage(folder)).toBe(false)
	})
})
