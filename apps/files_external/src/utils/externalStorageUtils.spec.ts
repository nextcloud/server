/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { File, Folder, Permission } from '@nextcloud/files'
import { describe, expect, test } from 'vitest'
import { appliesToAllAccounts, isNodeExternalStorage } from './externalStorageUtils.ts'

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
			root: '/files/admin',
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
			root: '/files/admin',
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
			root: '/files/admin',
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
			root: '/files/admin',
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
			root: '/files/admin',
		})
		expect(isNodeExternalStorage(folder)).toBe(false)
	})
})

describe('Does a storage apply to all accounts', () => {
	test('A storage without any applicable user or group applies to all accounts', () => {
		expect(appliesToAllAccounts([], [])).toBe(true)
	})

	test('Missing applicable lists apply to all accounts', () => {
		expect(appliesToAllAccounts(undefined, undefined)).toBe(true)
	})

	test('A storage restricted to a user does not apply to all accounts', () => {
		expect(appliesToAllAccounts(['alice'], [])).toBe(false)
	})

	test('A storage restricted to a group does not apply to all accounts', () => {
		expect(appliesToAllAccounts([], ['developers'])).toBe(false)
	})
})
