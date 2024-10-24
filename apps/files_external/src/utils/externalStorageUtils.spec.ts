/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { File, Folder, Permission } from '@nextcloud/files'
import { describe, expect, test } from 'vitest'
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
