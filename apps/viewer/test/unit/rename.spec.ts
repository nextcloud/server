import { emit } from '@nextcloud/event-bus'
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { makeFile } from '../factories.ts'

const axios = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/axios', () => ({ default: axios }))
vi.mock('@nextcloud/event-bus')

const { renameFile } = await import('../../src/utils/rename.ts')

describe('renameFile', () => {
	beforeEach(() => {
		axios.mockReset()
		axios.mockResolvedValue({})
	})

	it('renames the node and issues a MOVE to the new destination', async () => {
		const file = makeFile({ basename: 'old.jpg' })
		const oldEncoded = file.encodedSource

		const result = await renameFile(file, 'new name.jpg')

		expect(result).toBe(true)
		expect(file.basename).toBe('new name.jpg')
		expect(axios).toHaveBeenCalledWith(expect.objectContaining({
			method: 'MOVE',
			url: oldEncoded,
			headers: expect.objectContaining({
				Destination: file.encodedSource,
				Overwrite: 'F',
			}),
		}))
		expect(emit).toHaveBeenCalledWith('files:node:renamed', file)
		expect(emit).toHaveBeenCalledWith('files:node:updated', file)
	})

	it('is a no-op when the name is unchanged', async () => {
		const file = makeFile({ basename: 'same.jpg' })
		expect(await renameFile(file, 'same.jpg')).toBe(false)
		expect(await renameFile(file, '  same.jpg  ')).toBe(false)
		expect(axios).not.toHaveBeenCalled()
	})

	it('rejects names containing a slash', async () => {
		const file = makeFile({ basename: 'old.jpg' })
		await expect(renameFile(file, 'a/b.jpg')).rejects.toThrow('/')
		expect(axios).not.toHaveBeenCalled()
		expect(file.basename).toBe('old.jpg')
	})

	it('rolls back the optimistic rename when the request fails', async () => {
		axios.mockRejectedValueOnce(new Error('network'))
		const file = makeFile({ basename: 'old.jpg' })

		await expect(renameFile(file, 'new.jpg')).rejects.toThrow()
		expect(file.basename).toBe('old.jpg')
		expect(emit).not.toHaveBeenCalled()
	})
})
