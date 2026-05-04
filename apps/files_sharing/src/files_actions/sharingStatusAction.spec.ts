/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, IView } from '@nextcloud/files'

import { File, Permission } from '@nextcloud/files'
import { ShareType } from '@nextcloud/sharing'
import { beforeAll, describe, expect, test, vi } from 'vitest'
import { action } from './sharingStatusAction.ts'

vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: vi.fn(() => ({ uid: 'admin' })),
}))

vi.mock('@nextcloud/sharing/public', () => ({
	isPublicShare: vi.fn(() => false),
}))

const view = {
	id: 'files',
	name: 'Files',
} as IView

beforeAll(() => {
	(window as any)._oc_webroot = ''
})

describe('Sharing status action title tests', () => {
	test('Title does not double-escape special characters in owner display name', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/SharedFolder',
			owner: 'testuser',
			mime: 'httpd/unix-directory',
			permissions: Permission.ALL,
			root: '/files/admin',
			attributes: {
				'owner-display-name': 'bits & trees',
				'share-types': [ShareType.User],
			},
		})

		const title = action.title!({
			nodes: [file],
			view,
			folder: {} as IFolder,
			contents: [],
		})

		expect(title).toBe('Shared by bits & trees')
		expect(title).not.toContain('&amp;')
	})

	test('Title does not double-escape special characters in sharee display name', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/SharedFolder',
			owner: 'admin',
			mime: 'httpd/unix-directory',
			permissions: Permission.ALL | Permission.SHARE,
			root: '/files/admin',
			attributes: {
				'share-types': [ShareType.User],
				sharees: {
					sharee: [{ id: 'bob', 'display-name': 'Bob & Alice', type: ShareType.User }],
				},
			},
		})

		const title = action.title!({
			nodes: [file],
			view,
			folder: {} as IFolder,
			contents: [],
		})

		expect(title).toBe('Shared with Bob & Alice')
		expect(title).not.toContain('&amp;')
	})

	test('Title does not double-escape special characters in group display name', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/SharedFolder',
			owner: 'admin',
			mime: 'httpd/unix-directory',
			permissions: Permission.ALL | Permission.SHARE,
			root: '/files/admin',
			attributes: {
				'share-types': [ShareType.Group],
				sharees: {
					sharee: [{ id: 'dev-group', 'display-name': 'Dev & Ops', type: ShareType.Group }],
				},
			},
		})

		const title = action.title!({
			nodes: [file],
			view,
			folder: {} as IFolder,
			contents: [],
		})

		expect(title).toBe('Shared with group Dev & Ops')
		expect(title).not.toContain('&amp;')
	})
})
