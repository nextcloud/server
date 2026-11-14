/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useGroupSearch } from './useGroupSearch.ts'

const searchGroups = vi.hoisted(() => vi.fn())

vi.mock('../../../service/groups.ts', () => ({ searchGroups }))

const options = { search: 'team', offset: 0, limit: 25 }

/**
 * A search that only settles when it is aborted, like axios does
 *
 * @param root0 The search options
 * @param root0.signal The abort signal
 */
function pendingUntilAborted({ signal }: { signal: AbortSignal }) {
	return new Promise((resolve, reject) => {
		signal.addEventListener('abort', () => reject(Object.assign(new Error('canceled'), { __CANCEL__: true })))
	})
}

describe('useGroupSearch', () => {
	beforeEach(() => {
		searchGroups.mockReset()
	})

	it('returns the found groups', async () => {
		searchGroups.mockResolvedValue([{ id: 'team' }])
		const search = useGroupSearch()

		await expect(search(options)).resolves.toEqual([{ id: 'team' }])
		expect(searchGroups).toHaveBeenCalledWith({ ...options, signal: expect.any(AbortSignal) })
	})

	it('aborts the running search when a new one starts', async () => {
		searchGroups.mockImplementationOnce(pendingUntilAborted).mockResolvedValueOnce([{ id: 'team' }])
		const search = useGroupSearch()

		const first = search({ ...options, search: 'te' })
		const second = search(options)

		await expect(first).resolves.toBeNull()
		await expect(second).resolves.toEqual([{ id: 'team' }])
	})

	it('drops the result of a replaced search that finished anyway', async () => {
		let finishFirst: (groups: unknown[]) => void = () => {}
		searchGroups.mockImplementationOnce(() => new Promise((resolve) => {
			finishFirst = resolve
		})).mockResolvedValueOnce([])
		const search = useGroupSearch()

		const first = search({ ...options, search: 'te' })
		const second = search(options)
		finishFirst([{ id: 'stale' }])

		await expect(first).resolves.toBeNull()
		await expect(second).resolves.toEqual([])
	})

	it('passes on errors of the newest search', async () => {
		searchGroups.mockRejectedValue(new Error('500'))
		const search = useGroupSearch()

		await expect(search(options)).rejects.toThrow('500')
	})
})
