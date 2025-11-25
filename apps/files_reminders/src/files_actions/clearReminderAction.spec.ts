/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { View } from '@nextcloud/files'

import { Folder } from '@nextcloud/files'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { action } from './clearReminderAction.ts'

describe('clearReminderAction', () => {
	const folder = new Folder({
		owner: 'user',
		source: 'https://example.com/remote.php/dav/files/user/folder',
		attributes: {
			'reminder-due-date': '2024-12-25T10:00:00Z',
		},
	})

	beforeEach(() => vi.resetAllMocks())

	it('should be enabled for one node with due date', () => {
		expect(action.enabled!([folder], {} as unknown as View)).toBe(true)
	})

	it('should be disabled with more than one node', () => {
		expect(action.enabled!([folder, folder], {} as unknown as View)).toBe(false)
	})

	it('should be disabled if no due date', () => {
		const node = folder.clone()
		delete node.attributes['reminder-due-date']
		expect(action.enabled!([node], {} as unknown as View)).toBe(false)
	})

	it('should have title based on due date', () => {
		expect(action.title!([folder], {} as unknown as View)).toMatchInlineSnapshot('"Clear reminder – Wednesday, December 25, 2024 at 10:00 AM"')
	})
})
