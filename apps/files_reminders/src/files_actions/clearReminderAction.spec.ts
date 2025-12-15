/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { View } from '@nextcloud/files'

import { Folder } from '@nextcloud/files'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { action } from './clearReminderAction.ts'

const view = {} as unknown as View

const root = new Folder({
	owner: 'user',
	source: 'https://example.com/remote.php/dav/files/user/',
	root: '/files/user',
})

describe('clearReminderAction', () => {
	const folder = new Folder({
		owner: 'user',
		source: 'https://example.com/remote.php/dav/files/user/folder',
		attributes: {
			'reminder-due-date': '2024-12-25T10:00:00Z',
		},
		root: '/files/user',
	})

	beforeEach(() => vi.resetAllMocks())

	it('should be enabled for one node with due date', () => {
		expect(action.enabled!({
			nodes: [folder],
			view,
			folder: root,
			contents: [],
		})).toBe(true)
	})

	it('should be disabled with more than one node', () => {
		expect(action.enabled!({
			nodes: [folder, folder],
			view,
			folder: root,
			contents: [],
		})).toBe(false)
	})

	it('should be disabled if no due date', () => {
		const node = folder.clone()
		delete node.attributes['reminder-due-date']
		expect(action.enabled!({
			nodes: [node],
			view,
			folder: root,
			contents: [],
		})).toBe(false)
	})

	it('should have title based on due date', () => {
		expect(action.title!({
			nodes: [folder],
			view,
			folder: root,
			contents: [],
		})).toMatchInlineSnapshot('"Clear reminder – Wednesday, December 25, 2024 at 10:00 AM"')
	})
})
