/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'

import { describe, expect, it, vi } from 'vitest'

const spawnDialog = vi.fn()
vi.mock('@nextcloud/vue/functions/dialog', () => ({ spawnDialog }))

const { newNodeName } = await import('./newNodeDialog.ts')

describe('newNodeName', () => {
	it('resolves with the name the dialog was closed with', async () => {
		spawnDialog.mockResolvedValueOnce('New folder (2)')

		await expect(newNodeName('New folder', [])).resolves.toBe('New folder (2)')
	})

	it('resolves with null when the dialog is dismissed', async () => {
		spawnDialog.mockResolvedValueOnce(null)

		await expect(newNodeName('New folder', [])).resolves.toBeNull()
	})

	it('passes the other node names so the dialog can suggest a unique one', async () => {
		spawnDialog.mockResolvedValueOnce(null)
		const content = [{ basename: 'foo.txt' }, { basename: 'bar' }] as INode[]

		await newNodeName('New folder', content, { isFolder: true })

		expect(spawnDialog).toHaveBeenCalledWith(
			expect.anything(),
			expect.objectContaining({
				defaultName: 'New folder',
				isFolder: true,
				otherNames: ['foo.txt', 'bar'],
			}),
		)
	})
})
