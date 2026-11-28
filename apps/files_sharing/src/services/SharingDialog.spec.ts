/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'

import { getCapabilities } from '@nextcloud/capabilities'
import { getShare, openSharingDialog } from '@nextcloud/sharing/dialog'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { isSharingDialogAvailable, openShareCreateDialog, openShareEditDialog } from './SharingDialog.ts'

vi.mock('@nextcloud/capabilities', () => ({ getCapabilities: vi.fn() }))
vi.mock('@nextcloud/sharing/dialog', () => ({
	getShare: vi.fn(),
	openSharingDialog: vi.fn(),
}))

const node = { path: '/a.txt' } as unknown as Node

describe('SharingDialog', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it.each([
		[{ sharing: { api_versions: ['1'] } }, true],
		[{ sharing: { api_versions: [] } }, false],
		[{ sharing: {} }, false],
		[{}, false],
	])('isSharingDialogAvailable with %j', (capabilities, expected) => {
		vi.mocked(getCapabilities).mockReturnValue(capabilities)
		expect(isSharingDialogAvailable()).toBe(expected)
	})

	it('openShareCreateDialog opens the dialog for the node', async () => {
		vi.mocked(openSharingDialog).mockResolvedValue('created')
		await expect(openShareCreateDialog(node)).resolves.toBe('created')
		expect(openSharingDialog).toHaveBeenCalledWith(node)
	})

	it('openShareEditDialog opens the dialog of the share', async () => {
		const showDialog = vi.fn().mockResolvedValue('edited')
		vi.mocked(getShare).mockResolvedValue({ showDialog } as never)
		await expect(openShareEditDialog(42, node)).resolves.toBe('edited')
		expect(getShare).toHaveBeenCalledWith('42')
		expect(showDialog).toHaveBeenCalledWith(node)
	})
})
