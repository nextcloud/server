/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { File, Folder, Permission } from '@nextcloud/files'
import { describe, expect, test } from 'vitest'
import { isNodeExternalStorage, pruneUnusedAuthMechanismOptions } from './externalStorageUtils.ts'

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

describe('Prune unused authentication mechanism options', () => {
	test('removes the previous mechanism options the new mechanism does not use', () => {
		const backendOptions: Record<string, unknown> = { user: 'alice', password: 'secret', client_id: 'abc' }
		pruneUnusedAuthMechanismOptions(
			backendOptions,
			{ user: {}, password: {} },
			[{ client_id: {}, client_secret: {} }, {}],
		)
		expect(backendOptions).toEqual({ client_id: 'abc' })
	})

	test('keeps backend options when only the mechanism changes', () => {
		const backendOptions: Record<string, unknown> = { host: 'h', root: '/r', user: 'alice', password: 'secret' }
		pruneUnusedAuthMechanismOptions(
			backendOptions,
			{ user: {}, password: {} },
			[{ token: {} }, { host: {}, root: {} }],
		)
		expect(backendOptions).toEqual({ host: 'h', root: '/r' })
	})

	test('keeps fields shared between the old and new mechanism', () => {
		const backendOptions: Record<string, unknown> = { configured: true, user: 'alice' }
		pruneUnusedAuthMechanismOptions(
			backendOptions,
			{ configured: {}, user: {} },
			[{ configured: {} }, {}],
		)
		expect(backendOptions).toEqual({ configured: true })
	})

	test('does nothing when there is no previous configuration', () => {
		const backendOptions: Record<string, unknown> = { user: 'alice' }
		pruneUnusedAuthMechanismOptions(backendOptions, undefined, [{}, {}])
		expect(backendOptions).toEqual({ user: 'alice' })
	})
})
