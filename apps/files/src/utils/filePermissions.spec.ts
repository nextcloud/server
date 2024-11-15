/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { NodeData } from '@nextcloud/files'

import { File, Permission } from '@nextcloud/files'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import { canCopy, canDownload, canMove } from './filePermissions'

import * as nextcloudSharing from '@nextcloud/sharing/public'
import * as nextcloudInitialState from '@nextcloud/initial-state'

vi.mock('@nextcloud/initial-state')
vi.mock('@nextcloud/sharing/public')

/**
 * Helper to create File instances for tests
 * @param data Optional overwrites for node data
 * @param name Optional overwrite filename
 */
function createFile(data: Partial<NodeData> = {}, name = 'file.txt'): File {
	return new File({
		mime: 'text/plain',
		owner: 'test',
		source: `http://nextcloud.local/remote.php/dav/files/admin/test/${name}`,
		root: '/files/admin',
		...data,
	})
}

describe('canDownload', () => {

	test('no download restrictions - no share attributes', () => {
		const file = createFile()
		expect(canDownload(file)).toBe(true)
	})

	test('no download restrictions - with random share attributes', () => {
		const file = createFile({
			attributes: {
				'share-attributes': '[{"scope": "foo","value":true}]',
			},
		})
		expect(canDownload(file)).toBe(true)
	})

	test('no download restrictions - with share attributes', () => {
		const file = createFile({
			attributes: {
				'share-attributes': '[{"scope": "permissions","key":"download","value":true}]',
			},
		})
		expect(canDownload(file)).toBe(true)
	})

	test('download restricted', () => {
		const file = createFile({
			attributes: {
				'share-attributes': '[{"scope": "permissions","key":"download","value":false}]',
			},
		})
		expect(canDownload(file)).toBe(false)
	})

	test('Empty attributes', () => {
		const file = createFile({
			attributes: {
				'share-attributes': '',
			},
		})
		expect(canDownload(file)).toBe(true)
	})

	test('no download restrictions - multiple files', () => {
		expect(
			canDownload([createFile(), createFile({}, 'other.txt')]),
		).toBe(true)
	})

	test('with some download restrictions - multiple files', () => {
		const file = createFile()
		const restricted = createFile({
			attributes: {
				'share-attributes': '[{"scope": "permissions","key":"download","value":false}]',
			},
		})
		expect(canDownload([file, restricted])).toBe(false)
	})

})

describe('canMove', () => {

	test('All permissions', () => {
		const file = createFile({
			permissions: Permission.ALL,
		})
		expect(canMove(file)).toBe(true)
	})

	test('Read + Delete permissions', () => {
		const file = createFile({
			permissions: Permission.READ | Permission.DELETE,
		})
		expect(canMove(file)).toBe(true)
	})

	test('Only read permissions', () => {
		const file = createFile({
			permissions: Permission.READ,
		})
		expect(canMove(file)).toBe(false)
	})

	test('Missing permissions', () => {
		const file = createFile()
		expect(canMove(file)).toBe(false)
	})

	test('Multiple files with permissions', () => {
		const file = createFile({ permissions: Permission.ALL })
		const file2 = createFile({ permissions: Permission.READ | Permission.DELETE })
		expect(canMove([file, file2])).toBe(true)
	})

	test('Multiple files without permissions', () => {
		const file = createFile({ permissions: Permission.ALL })
		const file2 = createFile({ permissions: Permission.READ | Permission.DELETE })
		const file3 = createFile({ permissions: Permission.READ | Permission.UPDATE })
		expect(canMove([file, file2, file3])).toBe(false)
	})

})

describe('canCopy', () => {

	beforeEach(() => {
		vi.restoreAllMocks()
	})

	test('No permissions', () => {
		const file = createFile({
			permissions: Permission.NONE,
		})
		expect(canCopy(file)).toBe(false)
	})

	test('All permissions', () => {
		const file = createFile({
			permissions: Permission.ALL,
		})
		expect(canCopy(file)).toBe(true)
	})

	test('Read permissions', () => {
		const file = createFile({
			permissions: Permission.READ,
		})
		expect(canCopy(file)).toBe(true)
	})

	test('All permissions but no download', () => {
		const file = createFile({
			permissions: Permission.ALL,
			attributes: {
				'share-attributes': '[{"scope": "permissions","key":"download","value":false}]',
			},
		})
		expect(canCopy(file)).toBe(false)
	})

	test('Public share but no create permission', () => {
		vi.spyOn(nextcloudInitialState, 'loadState')
			.mockImplementationOnce(() => Permission.READ)
		vi.spyOn(nextcloudSharing, 'isPublicShare').mockImplementationOnce(() => true)

		const file = createFile({
			permissions: Permission.READ | Permission.UPDATE | Permission.DELETE,
		})
		expect(canCopy(file)).toBe(false)
	})

	test('Public share with permissions', async () => {
		// Reset modules so we can change the initial-state
		vi.resetModules()
		const { canCopy } = await import('./filePermissions.ts')

		vi.spyOn(nextcloudInitialState, 'loadState')
			.mockImplementationOnce(() => (Permission.READ | Permission.CREATE))
		vi.spyOn(nextcloudSharing, 'isPublicShare').mockImplementationOnce(() => true)

		const file = createFile({
			permissions: Permission.READ,
		})
		expect(canCopy(file)).toBe(true)
	})
})
